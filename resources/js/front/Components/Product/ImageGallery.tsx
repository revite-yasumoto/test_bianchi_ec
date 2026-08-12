import { useState } from 'react';
import { cn } from '@/lib/utils';

type ImageGalleryProps = {
    images: { url: string; sort_order: number }[];
    productName: string;
    productCode: string;
    tint: string;
};

export function ImageGallery({
    images,
    productName,
    productCode,
    tint,
}: ImageGalleryProps) {
    const [currentIndex, setCurrentIndex] = useState(0);
    const current = images[currentIndex];

    return (
        <div>
            <div
                className="flex aspect-square items-end overflow-hidden rounded-[20px] p-5"
                style={{ backgroundImage: tint }}
            >
                {current ? (
                    <img
                        src={current.url}
                        alt={`${productName}の商品画像 ${currentIndex + 1}枚目`}
                        className="h-full w-full object-cover"
                    />
                ) : (
                    <span className="font-mono text-[11px] tracking-[.14em] text-white/80">
                        {productCode}
                    </span>
                )}
            </div>

            {images.length > 1 ? (
                <ul className="mt-2.5 grid grid-cols-5 gap-2">
                    {images.map((image, index) => (
                        <li key={image.url}>
                            <button
                                type="button"
                                aria-label={`商品画像 ${index + 1}枚目を表示`}
                                aria-pressed={index === currentIndex}
                                onClick={() => setCurrentIndex(index)}
                                className={cn(
                                    'aspect-square w-full overflow-hidden rounded-[9px] border-2',
                                    index === currentIndex
                                        ? 'border-brand'
                                        : 'border-transparent',
                                )}
                            >
                                <img
                                    src={image.url}
                                    alt=""
                                    loading="lazy"
                                    className="h-full w-full object-cover"
                                />
                            </button>
                        </li>
                    ))}
                </ul>
            ) : null}
        </div>
    );
}
