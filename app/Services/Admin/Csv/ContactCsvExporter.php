<?php

declare(strict_types=1);

namespace App\Services\Admin\Csv;

use App\Actions\Admin\Contact\BuildContactFilter;
use App\Models\Contact;
use Generator;

class ContactCsvExporter
{
    public function __construct(private readonly BuildContactFilter $buildFilter) {}

    /**
     * @return array<int, string>
     */
    public function header(): array
    {
        return [
            '問い合わせ番号', '受信日時', '種別', 'お名前', 'メールアドレス', '会員ID', '対象商品',
            '商品ID', 'お問い合わせ内容', 'ステータス', '対応メモ', '対応者', '対応日時',
        ];
    }

    /**
     * 商品IDは送信時に控えた `product_code` から出す（商品が削除された後も値が残る）。
     *
     * @param  array{tab?: string, status?: string|null, q?: string|null, from?: string|null, to?: string|null}  $filters
     * @return Generator<int, array<int, string>>
     */
    public function rows(array $filters): Generator
    {
        // オフセット走査（lazy）では、書き出し中に新しい問い合わせが着信すると行が重複する。
        // 受信日時は主キーと同じ順に増えるため、主キーの降順でキーセット走査する
        $contacts = ($this->buildFilter)($filters)
            ->with(['user:id,member_code', 'handledAdmin:id,name'])
            ->lazyByIdDesc();

        foreach ($contacts as $contact) {
            /** @var Contact $contact */
            yield [
                $contact->contact_number,
                $contact->created_at->format('Y-m-d H:i:s'),
                $contact->product_id === null ? '通常' : '商品',
                $contact->name,
                $contact->email,
                $contact->user?->member_code ?? '',
                $contact->product_name ?? '',
                $contact->product_code ?? '',
                $contact->body,
                $contact->status->label(),
                $contact->admin_note ?? '',
                $contact->handledAdmin?->name ?? '',
                $contact->handled_at?->format('Y-m-d H:i:s') ?? '',
            ];
        }
    }
}
