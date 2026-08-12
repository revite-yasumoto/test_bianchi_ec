import { cn } from '@/lib/utils';
import type { VariantOption } from '@/front/hooks/useVariantSelection';

type VariantSelectorProps = {
    label: string;
    options: VariantOption[];
    onSelect: (name: string) => void;
    minWidthClassName?: string;
};

export function VariantSelector({
    label,
    options,
    onSelect,
    minWidthClassName,
}: VariantSelectorProps) {
    if (options.length === 0) {
        return null;
    }

    return (
        <fieldset>
            <legend className="mb-2 text-[13px] font-bold">{label}</legend>
            <div className="flex flex-wrap gap-2">
                {options.map((option) => (
                    <button
                        key={option.name}
                        type="button"
                        disabled={option.disabled}
                        aria-pressed={option.selected}
                        onClick={() => onSelect(option.name)}
                        className={cn(
                            'rounded-[10px] border-[1.5px] px-4 py-2.5 text-[13px] font-semibold',
                            minWidthClassName,
                            option.selected
                                ? 'border-brand bg-[#E7F0F4]'
                                : 'border-line bg-white',
                            option.disabled
                                ? 'cursor-not-allowed text-[#B6BCC2] line-through'
                                : 'text-ink',
                        )}
                    >
                        {option.name}
                    </button>
                ))}
            </div>
        </fieldset>
    );
}
