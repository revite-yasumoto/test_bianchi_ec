<?php

declare(strict_types=1);

namespace App\Http\Requests\Concerns;

use Carbon\CarbonImmutable;
use Illuminate\Validation\Validator;

/**
 * 年・月・日を別々のプルダウンで受け取る生年月日の検証と組み立て。
 * 会員登録と会員情報変更で同じ規則を使うため（[docs/front/auth.md] が正本）、片方だけが変わらないようここに置く。
 */
trait ValidatesBirthdayParts
{
    /**
     * @return array<string, array<int, mixed>>
     */
    protected function birthdayPartRules(): array
    {
        return [
            'birth_year' => ['nullable', 'integer'],
            'birth_month' => ['nullable', 'integer', 'between:1,12'],
            'birth_day' => ['nullable', 'integer', 'between:1,31'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function birthdayPartAttributes(): array
    {
        return [
            'birth_year' => '生年月日（年）',
            'birth_month' => '生年月日（月）',
            'birth_day' => '生年月日（日）',
        ];
    }

    /**
     * 3項目は揃っているか全て未選択かのどちらかのみ許し、揃っている場合は実在する過去日であることを確かめる。
     * エラーは画面の並び順の先頭である `birth_year` にまとめて返す。
     */
    protected function validateBirthdayParts(Validator $validator): void
    {
        if ($validator->errors()->hasAny(['birth_year', 'birth_month', 'birth_day'])) {
            return;
        }

        $parts = $this->birthdayParts();
        $selected = array_filter($parts, fn (?int $part): bool => $part !== null);

        if ($selected === []) {
            return;
        }

        if (count($selected) < 3) {
            $validator->errors()->add('birth_year', '生年月日は年・月・日をすべて選択してください。');

            return;
        }

        if (! checkdate($parts['month'], $parts['day'], $parts['year'])) {
            $validator->errors()->add('birth_year', '生年月日に存在しない日付が指定されています。');

            return;
        }

        if ($this->birthday()?->isFuture() === true) {
            $validator->errors()->add('birth_year', '生年月日は本日以前の日付を選択してください。');
        }
    }

    /** 検証を通った入力から生年月日を組み立てる。未選択なら `null` */
    public function birthday(): ?CarbonImmutable
    {
        $parts = $this->birthdayParts();

        if (in_array(null, $parts, true)) {
            return null;
        }

        return CarbonImmutable::create($parts['year'], $parts['month'], $parts['day'])->startOfDay();
    }

    /**
     * @return array{year: int|null, month: int|null, day: int|null}
     */
    private function birthdayParts(): array
    {
        return [
            'year' => $this->integerOrNull('birth_year'),
            'month' => $this->integerOrNull('birth_month'),
            'day' => $this->integerOrNull('birth_day'),
        ];
    }

    private function integerOrNull(string $key): ?int
    {
        $value = $this->input($key);

        return $value === null || $value === '' ? null : (int) $value;
    }
}
