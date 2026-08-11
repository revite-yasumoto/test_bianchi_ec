type VariationEditorProps = {
    label: string;
    values: string[];
    options: string[];
    onAdd: (value: string) => void;
    onRemove: (value: string) => void;
};

/**
 * カラー／サイズのタグ編集。追加できる値は規格管理に登録済みのものに限る
 * （[docs/admin/spec-option.md] の規格値がそのまま選択肢になる）。
 */
export function VariationEditor({
    label,
    values,
    options,
    onAdd,
    onRemove,
}: VariationEditorProps) {
    const selectable = options.filter((option) => !values.includes(option));

    return (
        <div>
            <p className="mb-2 text-xs font-bold text-admin-ink">{label}</p>
            <ul className="flex flex-wrap items-center gap-1.5">
                {values.map((value) => (
                    <li
                        key={value}
                        className="inline-flex items-center gap-2 rounded-full border border-admin-line py-1.5 pr-2 pl-3 text-xs font-semibold"
                    >
                        {value}
                        <button
                            type="button"
                            aria-label={`${label}から${value}を外す`}
                            className="text-[13px] text-admin-ink-muted"
                            onClick={() => onRemove(value)}
                        >
                            ×
                        </button>
                    </li>
                ))}
                {values.length === 0 ? (
                    <li className="text-[11.5px] text-admin-ink-muted">
                        未選択
                    </li>
                ) : null}
            </ul>

            <div className="mt-2 flex flex-wrap items-center gap-1.5">
                <span className="text-[11px] text-admin-ink-muted">
                    規格管理から追加：
                </span>
                {selectable.length === 0 ? (
                    <span className="text-[11px] text-admin-ink-muted">
                        追加できる{label}がありません
                    </span>
                ) : (
                    selectable.map((option) => (
                        <button
                            key={option}
                            type="button"
                            className="rounded-full border border-dashed border-admin-line px-2.5 py-1 text-[11.5px] font-semibold text-admin-ink"
                            onClick={() => onAdd(option)}
                        >
                            ＋ {option}
                        </button>
                    ))
                )}
            </div>
        </div>
    );
}
