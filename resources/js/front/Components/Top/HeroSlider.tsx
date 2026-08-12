import { Link } from '@inertiajs/react';
import { useState } from 'react';
import { useAutoSlide } from '@/front/hooks/useAutoSlide';
import { cn } from '@/lib/utils';

export type BannerData = {
    id: number;
    tag: string;
    title: string;
    subtitle: string | null;
    background: string;
    link_url: string | null;
};

type HeroSliderProps = {
    banners: BannerData[];
};

const SLIDE_INTERVAL_MS = 5200;

/** `javascript:` 等を弾くため、サイト内の絶対パスのみ遷移先に使う */
function linkOf(url: string | null): string {
    return url && url.startsWith('/') ? url : route('products.index');
}

export function HeroSlider({ banners }: HeroSliderProps) {
    // 読んでいる間に切り替わらないよう、ポインタ・フォーカスが乗っている間は自動送りを止める
    const [isPaused, setIsPaused] = useState(false);
    const { index, select } = useAutoSlide(
        banners.length,
        SLIDE_INTERVAL_MS,
        isPaused,
    );
    const current = banners[index];

    if (!current) {
        return null;
    }

    return (
        <section
            aria-label="メインビジュアル"
            className="relative bg-bg2"
            onMouseEnter={() => setIsPaused(true)}
            onMouseLeave={() => setIsPaused(false)}
            onFocusCapture={() => setIsPaused(true)}
            onBlurCapture={() => setIsPaused(false)}
        >
            <div
                className="flex aspect-[4/5] items-end p-8 lg:aspect-[21/9] lg:p-12"
                style={{ backgroundImage: current.background }}
            >
                <div className="max-w-[520px]">
                    <p className="mb-3.5 font-mono text-[11px] tracking-[.2em] text-white/75">
                        {current.tag}
                    </p>
                    <h2 className="text-[32px] leading-[1.25] font-black whitespace-pre-line text-white lg:text-[44px]">
                        {current.title}
                    </h2>
                    {current.subtitle ? (
                        <p className="mt-3.5 text-sm text-white/85">
                            {current.subtitle}
                        </p>
                    ) : null}
                    <Link
                        href={linkOf(current.link_url)}
                        className="mt-5 inline-flex items-center gap-3 rounded-full bg-white px-5 py-3 text-sm font-bold text-ink"
                    >
                        見る<span className="font-mono">→</span>
                    </Link>
                </div>
            </div>

            {banners.length > 1 ? (
                <ul className="absolute right-6 bottom-4 flex gap-1.5">
                    {banners.map((banner, slideIndex) => (
                        <li key={banner.id}>
                            <button
                                type="button"
                                aria-label={`${slideIndex + 1}枚目のバナーを表示`}
                                aria-current={slideIndex === index}
                                onClick={() => select(slideIndex)}
                                className={cn(
                                    'block h-2 rounded-full transition-all duration-300',
                                    slideIndex === index
                                        ? 'w-6.5 bg-white'
                                        : 'w-2 bg-white/45',
                                )}
                            />
                        </li>
                    ))}
                </ul>
            ) : null}
        </section>
    );
}
