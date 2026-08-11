import { router } from '@inertiajs/react';
import { useRef, useState } from 'react';
import { ConfirmDialog } from '@/admin/Components/ConfirmDialog';

type CsvActionsProps = {
    exportUrl: string;
    importUrl: string;
    /** 確認モーダルで示す上書き対象の名称（例: 会員データ） */
    targetLabel: string;
};

/**
 * 一覧のヘッダーに置くCSVエクスポート／インポートの操作対。
 * インポートはファイル選択後に確認モーダルを経て送信する。
 */
export function CsvActions({
    exportUrl,
    importUrl,
    targetLabel,
}: CsvActionsProps) {
    const inputRef = useRef<HTMLInputElement>(null);
    const [pendingFile, setPendingFile] = useState<File | null>(null);

    const submit = () => {
        if (!pendingFile) {
            return;
        }

        const file = pendingFile;
        setPendingFile(null);

        router.post(importUrl, { file }, { forceFormData: true });
    };

    return (
        <div className="flex items-center gap-2">
            <a
                href={exportUrl}
                className="rounded-lg border border-admin-line bg-white px-3.5 py-2 text-[12.5px] font-bold whitespace-nowrap text-admin-ink"
            >
                CSVエクスポート
            </a>
            <button
                type="button"
                className="rounded-lg bg-admin-brand-deep px-3.5 py-2 text-[12.5px] font-bold whitespace-nowrap text-white"
                onClick={() => inputRef.current?.click()}
            >
                CSVインポート
            </button>

            <input
                ref={inputRef}
                type="file"
                accept=".csv,.txt,text/csv,text/plain"
                className="hidden"
                onChange={(event) => {
                    setPendingFile(event.target.files?.item(0) ?? null);
                    event.target.value = '';
                }}
            />

            <ConfirmDialog
                isOpen={pendingFile !== null}
                title="CSVインポートの実行"
                message={`「${pendingFile?.name ?? ''}」を取り込みます。既存の${targetLabel}が上書きされる場合があります。実行前にエクスポートによるバックアップを推奨します。`}
                confirmLabel="インポートする"
                onConfirm={submit}
                onCancel={() => setPendingFile(null)}
            />
        </div>
    );
}
