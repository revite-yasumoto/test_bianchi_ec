import { cn } from '@/lib/utils';
import { FIELD_CONTROL_CLASS } from '@/shared/Components/FormField';

type BirthdayFieldProps = {
    id: string;
    /** 未選択は空文字 */
    year: string;
    month: string;
    day: string;
    /** 3項目をまとめて検証するため、エラーは1つだけ受け取る */
    error?: string;
    className?: string;
    onChange: (part: 'year' | 'month' | 'day', value: string) => void;
};

/** 生年月日は遠い年を選ぶ操作が多いため、日付入力欄ではなく年・月・日のプルダウンで受け取る */
const YEAR_RANGE = 100;

function rangeOptions(from: number, to: number): number[] {
    return Array.from({ length: to - from + 1 }, (_, index) => from + index);
}

export function BirthdayField({
    id,
    year,
    month,
    day,
    error,
    className,
    onChange,
}: BirthdayFieldProps) {
    const thisYear = new Date().getFullYear();
    const years = rangeOptions(thisYear - YEAR_RANGE, thisYear).reverse();
    const controlClass = cn(FIELD_CONTROL_CLASS, error && 'border-coral');
    const describedBy = error ? `${id}-error` : undefined;

    return (
        <fieldset className={className}>
            <legend className="mb-1.5 block text-sm font-bold text-ink">
                生年月日
            </legend>
            <div className="flex items-center gap-1.5">
                <select
                    id={`${id}-year`}
                    aria-label="生年月日（年）"
                    aria-invalid={error ? true : undefined}
                    aria-describedby={describedBy}
                    value={year}
                    className={controlClass}
                    onChange={(event) => onChange('year', event.target.value)}
                >
                    <option value="">--</option>
                    {years.map((value) => (
                        <option key={value} value={value}>
                            {value}
                        </option>
                    ))}
                </select>
                <span className="text-base text-ink2">年</span>
                <select
                    id={`${id}-month`}
                    aria-label="生年月日（月）"
                    aria-invalid={error ? true : undefined}
                    aria-describedby={describedBy}
                    value={month}
                    className={controlClass}
                    onChange={(event) => onChange('month', event.target.value)}
                >
                    <option value="">--</option>
                    {rangeOptions(1, 12).map((value) => (
                        <option key={value} value={value}>
                            {value}
                        </option>
                    ))}
                </select>
                <span className="text-base text-ink2">月</span>
                <select
                    id={`${id}-day`}
                    aria-label="生年月日（日）"
                    aria-invalid={error ? true : undefined}
                    aria-describedby={describedBy}
                    value={day}
                    className={controlClass}
                    onChange={(event) => onChange('day', event.target.value)}
                >
                    <option value="">--</option>
                    {rangeOptions(1, 31).map((value) => (
                        <option key={value} value={value}>
                            {value}
                        </option>
                    ))}
                </select>
                <span className="text-base text-ink2">日</span>
            </div>
            {error ? (
                <p
                    id={`${id}-error`}
                    className="mt-1.5 text-sm font-bold text-coral"
                >
                    {error}
                </p>
            ) : null}
        </fieldset>
    );
}
