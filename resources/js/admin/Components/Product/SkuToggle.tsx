import { cn } from '@/lib/utils';

type SkuToggleProps = {
    checked: boolean;
    onChange: (checked: boolean) => void;
};

export function SkuToggle({ checked, onChange }: SkuToggleProps) {
    return (
        <button
            type="button"
            role="switch"
            aria-checked={checked}
            aria-label="SKU（バリエーション）の有無"
            className="ml-auto flex items-center gap-2"
            onClick={() => onChange(!checked)}
        >
            <span
                className={cn(
                    'relative block h-[22px] w-[38px] rounded-full transition-colors',
                    checked ? 'bg-admin-brand' : 'bg-admin-line',
                )}
            >
                <span
                    className={cn(
                        'absolute top-0.5 h-[18px] w-[18px] rounded-full bg-white transition-[left]',
                        checked ? 'left-[18px]' : 'left-0.5',
                    )}
                />
            </span>
            <span className="text-xs font-bold text-admin-ink">
                {checked ? 'SKUあり' : 'SKUなし'}
            </span>
        </button>
    );
}
