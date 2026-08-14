/** `uid` は行のReactキー用。保存時は `label` / `value` のみ送る */
export type SpecInput = { uid: string; label: string; value: string };

type SpecEditorProps = {
    specs: SpecInput[];
    onChange: (specs: SpecInput[]) => void;
};

const MAX_SPECS = 20;

let specUidCounter = 0;

export function newSpec(label = '', value = ''): SpecInput {
    specUidCounter += 1;

    return { uid: `spec-${specUidCounter}`, label, value };
}

export function SpecEditor({ specs, onChange }: SpecEditorProps) {
    const updateSpec = (index: number, changes: Partial<SpecInput>) => {
        onChange(
            specs.map((spec, current) =>
                current === index ? { ...spec, ...changes } : spec,
            ),
        );
    };

    return (
        <div className="rounded-xl border border-admin-line bg-white p-5">
            <div className="mb-3.5 flex items-baseline gap-2.5">
                <h2 className="text-[13px] font-extrabold text-admin-ink">
                    商品スペック表
                </h2>
                <p className="text-[11.5px] text-admin-ink-muted">
                    商品詳細の下部に表形式で掲載されます（最大{MAX_SPECS}項目）
                </p>
            </div>

            <ul className="flex flex-col gap-2">
                {specs.map((spec, index) => (
                    <li key={spec.uid} className="flex items-center gap-2">
                        <label
                            htmlFor={`spec-label-${index}`}
                            className="sr-only"
                        >
                            {`${index + 1}行目の項目名`}
                        </label>
                        <input
                            id={`spec-label-${index}`}
                            type="text"
                            value={spec.label}
                            placeholder="例：フレーム"
                            onChange={(event) =>
                                updateSpec(index, { label: event.target.value })
                            }
                            className="w-40 rounded-lg border border-admin-line px-3 py-2 text-base"
                        />
                        <label
                            htmlFor={`spec-value-${index}`}
                            className="sr-only"
                        >
                            {`${index + 1}行目の内容`}
                        </label>
                        <input
                            id={`spec-value-${index}`}
                            type="text"
                            value={spec.value}
                            placeholder="例：カーボンモノコック"
                            onChange={(event) =>
                                updateSpec(index, { value: event.target.value })
                            }
                            className="flex-1 rounded-lg border border-admin-line px-3 py-2 text-base"
                        />
                        <button
                            type="button"
                            aria-label={`${index + 1}行目を削除`}
                            className="text-[11.5px] font-bold text-admin-danger"
                            onClick={() =>
                                onChange(
                                    specs.filter(
                                        (_, current) => current !== index,
                                    ),
                                )
                            }
                        >
                            削除
                        </button>
                    </li>
                ))}
            </ul>

            {specs.length < MAX_SPECS ? (
                <button
                    type="button"
                    className="mt-3 rounded-lg border border-admin-line px-4 py-2 text-[12.5px] font-bold text-admin-ink"
                    onClick={() => onChange([...specs, newSpec()])}
                >
                    ＋ 項目を追加
                </button>
            ) : null}
        </div>
    );
}
