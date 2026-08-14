<?php

declare(strict_types=1);

namespace App\Services\Admin\Csv;

use App\Actions\Front\Auth\GenerateMemberCode;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class MemberCsvImporter
{
    public const HEADER = [
        '会員ID', '氏名', '氏名カナ', 'メールアドレス', '電話番号', 'ステータス', '初期パスワード',
    ];

    public function __construct(private readonly GenerateMemberCode $generateMemberCode) {}

    /**
     * @param  array<int, array<int, string>>  $rows
     */
    public function import(array $rows): ImportResult
    {
        $errors = [];
        $parsed = [];
        $seenEmails = [];

        foreach ($rows as $line => $columns) {
            $row = $this->toAssoc($columns);
            $existing = $this->findExisting($row);

            foreach ($this->validateRow($row, $existing, $seenEmails) as $message) {
                $errors[] = ['line' => $line, 'message' => $message];
            }

            $seenEmails[] = $row['email'];
            $parsed[] = $row;
        }

        if ($errors !== []) {
            return ImportResult::failed($errors);
        }

        return $this->persist($parsed);
    }

    /**
     * @param  array<int, string>  $columns
     * @return array<string, string>
     */
    private function toAssoc(array $columns): array
    {
        return [
            'member_code' => $columns[0] ?? '',
            'name' => $columns[1] ?? '',
            'name_kana' => $columns[2] ?? '',
            'email' => $columns[3] ?? '',
            'tel' => $columns[4] ?? '',
            'status' => $columns[5] ?? '',
            'password' => $columns[6] ?? '',
        ];
    }

    /**
     * 会員IDが指定されていればそれを、無ければメールアドレスで既存レコードを引く。
     *
     * @param  array<string, string>  $row
     */
    private function findExisting(array $row): ?User
    {
        if ($row['member_code'] !== '') {
            return User::query()->where('member_code', $row['member_code'])->first();
        }

        return User::query()->where('email', $row['email'])->first();
    }

    /**
     * @param  array<string, string>  $row
     * @param  array<int, string>  $seenEmails
     * @return array<int, string>
     */
    private function validateRow(array $row, ?User $existing, array $seenEmails): array
    {
        $validator = Validator::make($row, [
            'name' => ['required', 'string', 'max:100'],
            'name_kana' => ['nullable', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:191'],
            'tel' => ['nullable', 'string', 'max:20'],
            'status' => ['nullable', 'in:有効,休会'],
            // 既存レコードの更新ではパスワードを変更しないため、新規のときだけ必須にする
            'password' => [$existing === null ? 'required' : 'nullable', 'string', 'min:8'],
        ], [], [
            'name' => '氏名',
            'name_kana' => '氏名カナ',
            'email' => 'メールアドレス',
            'tel' => '電話番号',
            'status' => 'ステータス',
            'password' => '初期パスワード',
        ]);

        $validator->after(function ($validator) use ($row, $existing, $seenEmails): void {
            if (in_array($row['email'], $seenEmails, true)) {
                $validator->errors()->add('email', 'メールアドレスがファイル内で重複しています。');

                return;
            }

            $duplicated = User::query()
                ->where('email', $row['email'])
                ->when($existing !== null, fn ($query) => $query->whereKeyNot($existing->id))
                ->exists();

            if ($duplicated) {
                $validator->errors()->add('email', 'このメールアドレスは既に登録されています。');
            }
        });

        return $validator->errors()->all();
    }

    /**
     * @param  array<int, array<string, string>>  $rows
     */
    private function persist(array $rows): ImportResult
    {
        $created = 0;
        $updated = 0;

        DB::transaction(function () use ($rows, &$created, &$updated): void {
            foreach ($rows as $row) {
                $existing = $this->findExisting($row);

                $attributes = [
                    'name' => $row['name'],
                    'name_kana' => $row['name_kana'] === '' ? null : $row['name_kana'],
                    'email' => $row['email'],
                    'tel' => $row['tel'] === '' ? null : $row['tel'],
                    'status' => $row['status'] === '休会' ? UserStatus::Suspended : UserStatus::Active,
                ];

                if ($existing === null) {
                    $attributes['member_code'] = $row['member_code'] !== ''
                        ? $row['member_code']
                        : ($this->generateMemberCode)();
                    $attributes['password'] = $row['password'];

                    User::query()->create($attributes);
                    $created++;

                    continue;
                }

                $existing->update($attributes);
                $updated++;
            }
        });

        return ImportResult::success($created, $updated);
    }
}
