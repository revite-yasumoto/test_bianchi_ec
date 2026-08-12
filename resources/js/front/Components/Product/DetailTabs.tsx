import { useId, useState } from 'react';
import { cn } from '@/lib/utils';

type DetailTabsProps = {
    description: string | null;
    specs: { label: string; value: string }[];
};

const TABS = [
    { key: 'description', label: '商品説明' },
    { key: 'spec', label: 'スペック' },
] as const;

type TabKey = (typeof TABS)[number]['key'];

export function DetailTabs({ description, specs }: DetailTabsProps) {
    const [currentTab, setCurrentTab] = useState<TabKey>('description');
    const baseId = useId();

    return (
        <section
            aria-labelledby={`${baseId}-heading`}
            className="mt-12 border-t border-line"
        >
            <h2 id={`${baseId}-heading`} className="sr-only">
                商品情報
            </h2>
            <div role="tablist" aria-label="商品情報" className="flex">
                {TABS.map((tab) => (
                    <button
                        key={tab.key}
                        type="button"
                        role="tab"
                        id={`${baseId}-tab-${tab.key}`}
                        aria-selected={currentTab === tab.key}
                        aria-controls={`${baseId}-panel-${tab.key}`}
                        onClick={() => setCurrentTab(tab.key)}
                        className={cn(
                            'border-b-2 px-5 py-4 text-sm font-bold',
                            currentTab === tab.key
                                ? 'border-brand text-ink'
                                : 'border-transparent text-ink2',
                        )}
                    >
                        {tab.label}
                    </button>
                ))}
            </div>

            {/* aria-controls の参照先を常に存在させるため、非選択のパネルは hidden で残す */}
            <div
                role="tabpanel"
                id={`${baseId}-panel-description`}
                aria-labelledby={`${baseId}-tab-description`}
                hidden={currentTab !== 'description'}
            >
                <p className="max-w-[760px] py-6 text-sm leading-8 text-ink2 whitespace-pre-line">
                    {description ?? '商品説明は登録されていません。'}
                </p>
            </div>
            <div
                role="tabpanel"
                id={`${baseId}-panel-spec`}
                aria-labelledby={`${baseId}-tab-spec`}
                hidden={currentTab !== 'spec'}
            >
                {specs.length > 0 ? (
                    <dl className="max-w-[760px] py-5">
                        {specs.map((spec) => (
                            <div
                                key={spec.label}
                                className="grid grid-cols-[minmax(120px,180px)_1fr] gap-4 border-b border-line py-3"
                            >
                                <dt className="text-[13px] font-bold text-ink2">
                                    {spec.label}
                                </dt>
                                <dd className="text-[13px]">{spec.value}</dd>
                            </div>
                        ))}
                    </dl>
                ) : (
                    <p className="py-6 text-sm text-ink2">
                        スペックは登録されていません。
                    </p>
                )}
            </div>
        </section>
    );
}
