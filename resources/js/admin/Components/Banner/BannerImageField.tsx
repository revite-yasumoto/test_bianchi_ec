import { useRef } from 'react';
import { useFilePreview } from '@/admin/hooks/useFilePreview';
import { IMAGE_ACCEPT } from '@/admin/lib/imageUpload';
import { cn } from '@/lib/utils';

type BannerImageFieldProps = {
    id: string;
    label: string;
    /** プレビュー枠の幅と縦横比（Tailwind の w・aspect クラス） */
    previewClassName: string;
    /** 登録済みの画像。削除を指定した後は表示しない */
    currentUrl: string | null;
    file: File | null;
    isRemoved: boolean;
    error?: string;
    onSelect: (file: File | null) => void;
    onRemove: () => void;
};

export function BannerImageField({
    id,
    label,
    previewClassName,
    currentUrl,
    file,
    isRemoved,
    error,
    onSelect,
    onRemove,
}: BannerImageFieldProps) {
    const inputRef = useRef<HTMLInputElement>(null);
    const previewUrl = useFilePreview(file);
    const shownUrl = previewUrl ?? (isRemoved ? null : currentUrl);

    const handleRemove = () => {
        // 選択済みのファイル名が入力欄に残ると、削除したのに送られるように見えるため空にする
        if (inputRef.current) {
            inputRef.current.value = '';
        }

        onRemove();
    };

    return (
        <div>
            <label
                htmlFor={id}
                className="mb-1.5 block text-sm font-bold text-admin-ink"
            >
                {label}
            </label>
            <div className="flex items-start gap-3">
                <div
                    className={cn(
                        'shrink-0 overflow-hidden rounded-md border border-admin-line bg-admin-bg',
                        previewClassName,
                    )}
                >
                    {shownUrl ? (
                        <img
                            src={shownUrl}
                            alt={
                                previewUrl
                                    ? '選択した画像のプレビュー'
                                    : '登録済みの画像'
                            }
                            className="h-full w-full object-cover"
                        />
                    ) : null}
                </div>
                <div className="flex min-w-0 flex-col gap-2">
                    <input
                        ref={inputRef}
                        id={id}
                        type="file"
                        accept={IMAGE_ACCEPT}
                        aria-invalid={error !== undefined}
                        className="text-sm"
                        onChange={(event) =>
                            onSelect(event.target.files?.[0] ?? null)
                        }
                    />
                    {shownUrl ? (
                        <button
                            type="button"
                            className="self-start text-sm font-bold text-admin-danger"
                            onClick={handleRemove}
                        >
                            画像を削除
                        </button>
                    ) : null}
                </div>
            </div>
            {error ? (
                <p className="mt-1 text-sm font-bold text-admin-danger">
                    {error}
                </p>
            ) : null}
        </div>
    );
}
