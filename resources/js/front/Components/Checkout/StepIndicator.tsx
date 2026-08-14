import { cn } from '@/lib/utils';

const STEPS = ['カート', '購入手続き', '注文確認'];

type StepIndicatorProps = {
    /** 1オリジンの現在ステップ */
    current: number;
};

export function StepIndicator({ current }: StepIndicatorProps) {
    return (
        <nav aria-label="購入手続きの進捗" className="mb-5.5">
            <ol className="flex flex-wrap items-center gap-2.5">
                {STEPS.map((label, index) => {
                    const step = index + 1;
                    const isDone = step <= current;

                    return (
                        <li key={label} className="flex items-center gap-2">
                            <span
                                aria-hidden="true"
                                className={cn(
                                    'flex h-6 w-6 items-center justify-center rounded-full font-mono text-[11px] font-bold',
                                    isDone
                                        ? 'bg-brand text-white'
                                        : 'bg-line text-ink2',
                                )}
                            >
                                {step}
                            </span>
                            <span
                                aria-current={
                                    step === current ? 'step' : undefined
                                }
                                className={cn(
                                    'text-[12.5px] font-bold whitespace-nowrap',
                                    isDone ? 'text-ink' : 'text-ink2',
                                )}
                            >
                                {label}
                            </span>
                            {step < STEPS.length ? (
                                <span aria-hidden="true" className="text-line">
                                    —
                                </span>
                            ) : null}
                        </li>
                    );
                })}
            </ol>
        </nav>
    );
}
