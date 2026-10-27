import { Link } from '@inertiajs/react';
import { useState } from 'react';
import { CategorySilhouette } from '@/front/Components/Product/CategorySilhouette';
import { useAutoSlide } from '@/front/hooks/useAutoSlide';
import { cn } from '@/lib/utils';

export type BannerData = {
    id: number;
    tag: string;
    title: string;
    subtitle: string | null;
    background: string;
    product_id: number | null;
    link_url: string | null;
    pc_image_url: string | null;
    sp_image_url: string | null;
};

type HeroSliderProps = {
    banners: BannerData[];
};

const SLIDE_INTERVAL_MS = 5200;

/**
 * 片方の幅だけ画像があるとき、画像の無い幅では下の配色を透かして見せるための1px透過GIF。
 * `<picture>` は幅ごとに「画像を出さない」指定ができないため、透過画像で代える。
 */
const TRANSPARENT_PIXEL =
    'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';

function linkOf(banner: BannerData): string {
    if (banner.product_id !== null) {
        return route('products.show', [banner.product_id]);
    }

    // `javascript:` 等や、外部ホストとして解釈される `//`・`/\` 始まりを弾くため、サイト内の絶対パスのみ遷移先に使う。
    // ブラウザは解析前にタブ・改行を取り除くため（`/\t/example.com` → `//example.com`）、制御文字を含む値も使わない
    return banner.link_url &&
        /^\/(?![/\\])[^\x00-\x1F\x7F]*$/.test(banner.link_url)
        ? banner.link_url
        : route('products.index');
}

type SlideImageProps = {
    banner: BannerData;
    isFirst: boolean;
};

function SlideImage({ banner, isFirst }: SlideImageProps) {
    const { pc_image_url: pcUrl, sp_image_url: spUrl } = banner;

    if (pcUrl === null && spUrl === null) {
        return null;
    }

    return (
        <>
            <picture>
                <source
                    media="(min-width: 64rem)"
                    srcSet={pcUrl ?? TRANSPARENT_PIXEL}
                    width={2100}
                    height={900}
                />
                <img
                    src={spUrl ?? TRANSPARENT_PIXEL}
                    alt=""
                    width={800}
                    height={1000}
                    fetchPriority={isFirst ? 'high' : undefined}
                    className="absolute inset-0 h-full w-full object-cover"
                />
            </picture>
            {/* 画像の上でも文字が読めるよう、画像のある幅にだけ暗いオーバーレイを敷く */}
            <div
                aria-hidden="true"
                className={cn(
                    'absolute inset-0 bg-gradient-to-t from-black/60 via-black/25 to-transparent',
                    spUrl === null && 'hidden lg:block',
                    pcUrl === null && 'lg:hidden',
                )}
            />
        </>
    );
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
                className="relative flex aspect-[4/5] items-end overflow-hidden p-8 lg:aspect-[21/9] lg:p-12"
                style={{ backgroundImage: current.background }}
            >
                {/* バナーごとの図案を持たないため、カテゴリに紐づかない汎用の車体を敷く */}
                <CategorySilhouette
                    categoryName=""
                    className="absolute top-1/2 right-[-6%] w-[70%] -translate-y-1/2 text-white/12 lg:right-[4%] lg:w-[38%]"
                />

                <SlideImage
                    key={current.id}
                    banner={current}
                    isFirst={index === 0}
                />

                <div className="relative max-w-[520px]">
                    <p className="mb-3.5 font-mono text-sm tracking-[.2em] text-white/75">
                        {current.tag}
                    </p>
                    <h2 className="text-[32px] leading-[1.25] font-black whitespace-pre-line text-white lg:text-[44px]">
                        {current.title}
                    </h2>
                    {current.subtitle ? (
                        <p className="mt-3.5 text-base text-white/85">
                            {current.subtitle}
                        </p>
                    ) : null}
                    <Link
                        href={linkOf(current)}
                        className="mt-5 inline-flex items-center gap-3 rounded-full bg-white px-5 py-3 text-base font-bold text-ink"
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
