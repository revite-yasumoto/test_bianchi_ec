<?php

declare(strict_types=1);

namespace App\Services\Admin\Csv;

use App\Actions\Admin\AdminUser\GenerateAdminCode;
use App\Models\Admin;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AdminUserCsvImporter
{
    public const HEADER = ['管理者ID', '氏名', 'メールアドレス', '初期パスワード'];

    public function __construct(private readonly GenerateAdminCode $generateAdminCode) {}

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
            'admin_code' => $columns[0] ?? '',
            'name' => $columns[1] ?? '',
            'email' => $columns[2] ?? '',
            'password' => $columns[3] ?? '',
        ];
    }

    /**
     * @param  array<string, string>  $row
     */
    private function findExisting(array $row): ?Admin
    {
        if ($row['admin_code'] !== '') {
            return Admin::query()->where('admin_code', $row['admin_code'])->first();
        }

        return Admin::query()->where('email', $row['email'])->first();
    }

    /**
     * @param  array<string, string>  $row
     * @param  array<int, string>  $seenEmails
     * @return array<int, string>
     */
    private function validateRow(array $row, ?Admin $existing, array $seenEmails): array
    {
        $validator = Validator::make($row, [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:191'],
            // 既存レコードの更新ではパスワードを変更しないため、新規のときだけ必須にする
            'password' => [$existing === null ? 'required' : 'nullable', 'string', 'min:8'],
        ], [], [
            'name' => '氏名',
            'email' => 'メールアドレス',
            'password' => '初期パスワード',
        ]);

        $validator->after(function ($validator) use ($row, $existing, $seenEmails): void {
            if (in_array($row['email'], $seenEmails, true)) {
                $validator->errors()->add('email', 'メールアドレスがファイル内で重複しています。');

                return;
            }

            $duplicated = Admin::query()
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
                    'email' => $row['email'],
                ];

                if ($existing === null) {
                    $attributes['admin_code'] = $row['admin_code'] !== ''
                        ? $row['admin_code']
                        : ($this->generateAdminCode)();
                    $attributes['password'] = $row['password'];

                    Admin::query()->create($attributes);
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
