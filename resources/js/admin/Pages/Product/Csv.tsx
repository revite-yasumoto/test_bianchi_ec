import { router, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { AdminLayout } from '@/admin/Layouts/AdminLayout';
import { ConfirmDialog } from '@/admin/Components/ConfirmDialog';
import { DropZone } from '@/admin/Components/Csv/DropZone';
import { ImportResultPanel } from '@/admin/Components/Csv/ImportResultPanel';

type Props = { columns: string[] };

const CARD_CLASS = 'rounded-xl border border-admin-line bg-white p-5';

const HEADING_CLASS = 'mb-3.5 text-[13px] font-extrabold text-admin-ink';

/** 各列の必須条件。列名は `ProductCsvImporter::HEADER` と対応する */
const REQUIREMENTS: Record<string, string> = {
    商品ID: '必須',
    商品名: '必須',
    カテゴリ: '必須（登録済みのカテゴリ名）',
    '価格（税込）': '必須（整数）',
    SKU有無: '必須（あり / なし）',
    枝番: 'SKU有無が「あり」のとき必須',
    在庫数: '必須（整数）',
    公開状態: '任意（公開 / 非公開。既定は非公開）',
};

export default function Csv({ columns }: Props) {
    const { flash } = usePage<AdminSharedProps>().props;
    const { data, setData, post, processing, errors } = useForm<{
        file: File | null;
    }>({ file: null });
    const [isConfirmOpen, setIsConfirmOpen] = useState(false);

    const submit = () => {
        setIsConfirmOpen(false);

        post(route('admin.products.csv.import'), { forceFormData: true });
    };

    return (
        <AdminLayout title="商品CSV登録">
            <div className="grid grid-cols-[repeat(auto-fit,minmax(300px,1fr))] items-start gap-4">
                <div className="flex flex-col gap-4">
                    <div className={CARD_CLASS}>
                        <h2 className={HEADING_CLASS}>CSVインポート</h2>

                        <DropZone
                            file={data.file}
                            error={errors.file}
                            onSelect={(file) => setData('file', file)}
                        />

                        <div className="mt-3.5 flex gap-2.5">
                            <button
                                type="button"
                                disabled={data.file === null || processing}
                                className="flex-1 rounded-lg bg-admin-brand py-3 text-[13px] font-extrabold text-white disabled:opacity-50"
                                onClick={() => setIsConfirmOpen(true)}
                            >
                                インポート実行
                            </button>
                            <button
                                type="button"
                                className="rounded-lg border border-admin-line bg-white px-4 py-3 text-[12.5px] font-bold whitespace-nowrap text-admin-ink"
                                onClick={() =>
                                    router.get(
                                        route('admin.products.csv.template'),
                                    )
                                }
                            >
                                テンプレートDL
                            </button>
                        </div>

                        <p className="mt-3 text-[11.5px] leading-relaxed text-admin-ink-muted">
                            1行でもエラーがある場合は1件も取り込みません。文字コードは
                            UTF-8 と Shift_JIS のどちらでも取り込めます。
                        </p>
                    </div>

                    <ImportResultPanel result={flash.importResult} />
                </div>

                <div className="flex flex-col gap-4">
                    <div className={CARD_CLASS}>
                        <h2 className={HEADING_CLASS}>フォーマット</h2>
                        <div className="overflow-x-auto">
                            <table className="w-full text-left text-sm">
                                <thead className="border-b border-admin-line text-admin-ink-muted">
                                    <tr>
                                        <th
                                            scope="col"
                                            className="py-2 font-bold"
                                        >
                                            列
                                        </th>
                                        <th
                                            scope="col"
                                            className="py-2 font-bold"
                                        >
                                            項目
                                        </th>
                                        <th
                                            scope="col"
                                            className="py-2 font-bold"
                                        >
                                            必須
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {columns.map((column, index) => (
                                        <tr
                                            key={column}
                                            className="border-b border-admin-line last:border-b-0"
                                        >
                                            <td className="py-2 font-mono text-[11.5px]">
                                                {String.fromCharCode(
                                                    65 + index,
                                                )}
                                            </td>
                                            <td className="py-2 text-[12.5px] font-bold">
                                                {column}
                                            </td>
                                            <td className="py-2 text-[11.5px] text-admin-ink-muted">
                                                {REQUIREMENTS[column] ?? ''}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>

                        <p className="mt-3 text-[11.5px] leading-relaxed text-admin-ink-muted">
                            SKUあり商品は同じ商品IDの行を並べ、行ごとに枝番と在庫数を指定します。サイズ・カラーはCSVでは設定できません。
                        </p>
                    </div>

                    <div className={CARD_CLASS}>
                        <h2 className={HEADING_CLASS}>CSVエクスポート</h2>
                        <p className="mb-3 text-[12.5px] text-admin-ink-muted">
                            登録済みの全商品をCSVで書き出します。SKUあり商品はバリエーションごとの行になります。
                        </p>
                        <a
                            href={route('admin.products.csv.export')}
                            className="inline-block rounded-lg border border-admin-line bg-white px-4 py-2.5 text-[12.5px] font-bold text-admin-ink"
                        >
                            CSVエクスポート
                        </a>
                    </div>
                </div>
            </div>

            <ConfirmDialog
                isOpen={isConfirmOpen}
                title="CSVインポートの実行"
                message={`「${data.file?.name ?? ''}」を取り込みます。既存の商品データが上書きされる場合があります。実行前にエクスポートによるバックアップを推奨します。`}
                confirmLabel="インポートする"
                onConfirm={submit}
                onCancel={() => setIsConfirmOpen(false)}
            />
        </AdminLayout>
    );
}
