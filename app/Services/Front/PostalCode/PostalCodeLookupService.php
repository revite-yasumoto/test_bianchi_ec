<?php

declare(strict_types=1);

namespace App\Services\Front\PostalCode;

use App\Exceptions\PostalCodeLookupFailedException;
use App\Models\Prefecture;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * 郵便番号から住所を引く。外部サービスへの問い合わせと結果のキャッシュを隠蔽する。
 */
class PostalCodeLookupService
{
    /**
     * @param  string  $postalCode  ハイフンを含まない7桁の郵便番号
     * @return array{prefecture_id: int|null, prefecture_name: string, city: string, town: string}|null 該当する住所が無ければ null
     *
     * @throws PostalCodeLookupFailedException 外部サービスへ到達できない、または応答を解釈できない場合
     */
    public function lookup(string $postalCode): ?array
    {
        /** @var array{prefecture_id: int|null, prefecture_name: string, city: string, town: string}|null $cached */
        $cached = Cache::get($this->cacheKey($postalCode));

        if ($cached !== null) {
            return $cached;
        }

        $address = $this->fetch($postalCode);

        // 該当なしはキャッシュしない（新設・変更された郵便番号が保持期間いっぱい引けなくなるため）
        if ($address === null) {
            return null;
        }

        Cache::put(
            $this->cacheKey($postalCode),
            $address,
            now()->addDays((int) config('services.postal_code_lookup.cache_days')),
        );

        return $address;
    }

    private function cacheKey(string $postalCode): string
    {
        return "postal_code_lookup:{$postalCode}";
    }

    /**
     * @return array{prefecture_id: int|null, prefecture_name: string, city: string, town: string}|null
     *
     * @throws PostalCodeLookupFailedException
     */
    private function fetch(string $postalCode): ?array
    {
        try {
            $response = Http::timeout((int) config('services.postal_code_lookup.timeout'))
                ->connectTimeout((int) config('services.postal_code_lookup.connect_timeout'))
                ->retry(3, $this->retryDelay(...), when: $this->isRetryable(...))
                ->get((string) config('services.postal_code_lookup.base_url'), ['zipcode' => $postalCode])
                ->throw();
        } catch (ConnectionException|RequestException $exception) {
            Log::warning('郵便番号から住所を引けませんでした', [
                'postal_code' => $postalCode,
                'exception' => $exception->getMessage(),
            ]);

            throw new PostalCodeLookupFailedException;
        }

        return $this->toAddress($postalCode, $response->json());
    }

    /**
     * レート制限中の再送が相手の指定より早くならないよう、`Retry-After` があればその値を待つ。
     */
    private function retryDelay(int $attempt, Throwable $exception): int
    {
        $retryAfter = $exception instanceof RequestException
            ? (int) $exception->response->header('Retry-After')
            : 0;

        return $retryAfter > 0 ? $retryAfter * 1000 : $attempt * 200;
    }

    /**
     * 再送しても結果が変わらない 4xx（429 を除く）は繰り返さない。
     */
    private function isRetryable(Throwable $exception): bool
    {
        if ($exception instanceof ConnectionException) {
            return true;
        }

        if (! $exception instanceof RequestException) {
            return false;
        }

        return $exception->response->status() === 429 || $exception->response->serverError();
    }

    /**
     * @return array{prefecture_id: int|null, prefecture_name: string, city: string, town: string}|null
     *
     * @throws PostalCodeLookupFailedException
     */
    private function toAddress(string $postalCode, mixed $body): ?array
    {
        if (! is_array($body)) {
            $this->abort($postalCode, '応答を配列として読めない');
        }

        $status = $body['status'] ?? null;

        // 外部サービスは HTTP 200 のまま本文の status でエラーを知らせるため、該当なしと区別する
        if ($status !== null && (int) $status !== 200) {
            $this->abort($postalCode, "外部サービスが status {$status} を返した");
        }

        $results = $body['results'] ?? null;

        if ($results === null) {
            return null;
        }

        // 1つの郵便番号に複数の町域が紐づく場合があり、どれが正しいかは応答から決められないため先頭を採る
        $result = is_array($results) ? ($results[0] ?? null) : null;

        if (! is_array($result)) {
            $this->abort($postalCode, '検索結果の形式が想定と異なる');
        }

        $prefectureName = $result['address1'] ?? null;
        $city = $result['address2'] ?? null;
        $town = $result['address3'] ?? null;

        if (! is_string($prefectureName) || ! is_string($city) || ! is_string($town)) {
            $this->abort($postalCode, '住所の項目が文字列ではない');
        }

        $prefectureId = Prefecture::query()->where('name', $prefectureName)->value('id');

        return [
            'prefecture_id' => $prefectureId === null ? null : (int) $prefectureId,
            'prefecture_name' => $prefectureName,
            'city' => $city,
            'town' => $town,
        ];
    }

    /**
     * @throws PostalCodeLookupFailedException
     */
    private function abort(string $postalCode, string $reason): never
    {
        Log::warning('郵便番号の検索結果を解釈できませんでした', [
            'postal_code' => $postalCode,
            'reason' => $reason,
        ]);

        throw new PostalCodeLookupFailedException;
    }
}
