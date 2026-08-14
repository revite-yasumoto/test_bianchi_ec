type QuantityStepperProps = {
    quantity: number;
    /** 在庫数と数量上限のうち小さいほう。これを超える数量は選べない */
    max: number;
    disabled: boolean;
    onChange: (quantity: number) => void;
};

/** マイナス / 数量 / プラス の丸型ステッパー */
export function QuantityStepper({
    quantity,
    max,
    disabled,
    onChange,
}: QuantityStepperProps) {
    return (
        <div className="flex items-center overflow-hidden rounded-full border border-line">
            <button
                type="button"
                aria-label="数量を1つ減らす"
                disabled={disabled || quantity <= 1}
                onClick={() => onChange(quantity - 1)}
                className="h-8 w-8 text-[15px] disabled:cursor-not-allowed disabled:opacity-40"
            >
                −
            </button>
            <span className="min-w-[26px] text-center font-mono text-[13px]">
                {quantity}
            </span>
            <button
                type="button"
                aria-label="数量を1つ増やす"
                disabled={disabled || quantity >= max}
                onClick={() => onChange(quantity + 1)}
                className="h-8 w-8 text-[15px] disabled:cursor-not-allowed disabled:opacity-40"
            >
                ＋
            </button>
        </div>
    );
}
