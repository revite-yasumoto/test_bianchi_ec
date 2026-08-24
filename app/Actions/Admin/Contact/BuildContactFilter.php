<?php

declare(strict_types=1);

namespace App\Actions\Admin\Contact;

use App\Enums\ContactStatus;
use App\Models\Contact;
use Carbon\CarbonImmutable;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class BuildContactFilter
{
    public const TAB_GENERAL = 'general';

    public const TAB_PRODUCT = 'product';

    /** キーワード検索の対象列。列名は固定のリテラルのみを置く */
    private const KEYWORD_COLUMNS = ['name', 'email', 'product_name', 'body'];

    /**
     * @param  array{tab?: string, status?: string|null, q?: string|null, from?: string|null, to?: string|null}  $filters
     * @return Builder<Contact>
     */
    public function __invoke(array $filters): Builder
    {
        return Contact::query()
            ->when(
                ($filters['tab'] ?? self::TAB_GENERAL) === self::TAB_PRODUCT,
                fn (Builder $query) => $query->whereNotNull('product_id'),
                fn (Builder $query) => $query->whereNull('product_id'),
            )
            ->when(
                ($filters['status'] ?? 'all') !== 'all',
                fn (Builder $query) => $query->where('status', $filters['status']),
            )
            // 真偽で判定すると検索語が "0" のときに条件が落ちるため、null との比較で判定する
            ->when(
                ($filters['q'] ?? null) !== null,
                // 4列の OR を1つの括弧にまとめる。囲まないとタブ・ステータス・受信日の条件を飲み込む
                fn (Builder $query) => $query->where(
                    function (Builder $inner) use ($filters): void {
                        $pattern = '%'.$this->escapeLike((string) $filters['q']).'%';

                        foreach (self::KEYWORD_COLUMNS as $column) {
                            // ESCAPE を明示しないと、エスケープ文字を既定で持たないDB製品では
                            // 検索語に含まれる `%` `_` がワイルドカードのまま残る
                            $inner->orWhereRaw($column.' LIKE ? ESCAPE ?', [$pattern, '\\']);
                        }
                    },
                ),
            )
            // 日付関数を左辺に置くと index(status, created_at) が使えなくなるため境界を明示して比較する
            ->when(
                $this->dateOf($filters['from'] ?? null),
                fn (Builder $query, CarbonImmutable $from) => $query->where('created_at', '>=', $from),
            )
            ->when(
                $this->dateOf($filters['to'] ?? null),
                fn (Builder $query, CarbonImmutable $to) => $query->where('created_at', '<', $to->addDay()),
            )
            ->orderByDesc('created_at')
            ->orderByDesc('id');
    }

    /**
     * 一覧とCSVエクスポートで同じ絞り込みを適用するため、リクエストの正規化も本クラスが持つ。
     *
     * @return array{tab: string, status: string, q: string|null, from: string|null, to: string|null}
     */
    public function filtersOf(Request $request): array
    {
        $tab = $request->input('tab');
        $status = $request->input('status');
        $allowedStatuses = array_map(fn (ContactStatus $case): string => $case->value, ContactStatus::cases());

        return [
            'tab' => is_string($tab) && $tab === self::TAB_PRODUCT ? self::TAB_PRODUCT : self::TAB_GENERAL,
            'status' => is_string($status) && in_array($status, $allowedStatuses, true) ? $status : 'all',
            'q' => $this->stringOf($request, 'q'),
            'from' => $this->stringOf($request, 'from'),
            'to' => $this->stringOf($request, 'to'),
        ];
    }

    private function stringOf(Request $request, string $key): ?string
    {
        $value = $request->input($key);

        return is_string($value) && $value !== '' ? $value : null;
    }

    private function dateOf(?string $value): ?CarbonImmutable
    {
        if ($value === null) {
            return null;
        }

        try {
            // 先頭の `!` で時刻を 00:00:00 に固定する
            return CarbonImmutable::createFromFormat('!Y-m-d', $value) ?: null;
        } catch (InvalidFormatException) {
            return null;
        }
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $value);
    }
}
