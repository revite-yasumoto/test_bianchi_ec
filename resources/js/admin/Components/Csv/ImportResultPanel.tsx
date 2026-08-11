import { cn } from '@/lib/utils';

type ImportResultPanelProps = {
    result: CsvImportResult | null;
    className?: string;
};

export function ImportResultPanel({
    result,
    className,
}: ImportResultPanelProps) {
    if (!result) {
        return null;
    }

    const hasErrors = result.errors.length > 0;

    return (
        <div
            className={cn(
                'rounded-xl border bg-white p-5',
                hasErrors ? 'border-admin-danger' : 'border-admin-line',
                className,
            )}
        >
            <h2 className="mb-2 text-[13px] font-extrabold text-admin-ink">
                インポート結果
            </h2>

            {hasErrors ? (
                <>
                    <p className="text-[12.5px] font-bold text-admin-danger">
                        {result.errors.length}
                        件のエラーがあるため、1件も取り込んでいません。
                    </p>
                    <ul className="mt-2 flex flex-col gap-1">
                        {result.errors.map((error, index) => (
                            <li
                                key={`${error.line}-${index}`}
                                className="text-[12px] text-admin-ink"
                            >
                                <span className="font-mono font-bold">
                                    {error.line}行目
                                </span>
                                <span className="ml-2 text-admin-ink-muted">
                                    {error.message}
                                </span>
                            </li>
                        ))}
                    </ul>
                </>
            ) : (
                <p className="text-[12.5px] text-admin-ink">
                    新規 {result.created}件 / 更新 {result.updated}件
                    を取り込みました。
                </p>
            )}
        </div>
    );
}
