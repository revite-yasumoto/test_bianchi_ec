import { useForm } from '@inertiajs/react';
import type { FormEventHandler } from 'react';
import { Modal } from '@/shared/Components/Modal';
import type { NewsRow } from './NewsManager';

type NewsEditorModalProps = {
    /** 編集対象。新規作成のときは `null` */
    row: NewsRow | null;
    categoryOptions: string[];
    onClose: () => void;
    onSaved: () => void;
};

type NewsForm = {
    published_on: string;
    category: string;
    title: string;
    body: string;
    is_published: boolean;
};

const LABEL_CLASS = 'mb-1.5 block text-[11.5px] font-bold text-admin-ink';

const FIELD_CLASS =
    'w-full rounded-lg border border-admin-line px-3 py-2 text-base';

const ERROR_CLASS = 'mt-1 text-[11px] font-bold text-admin-danger';

export function NewsEditorModal({
    row,
    categoryOptions,
    onClose,
    onSaved,
}: NewsEditorModalProps) {
    const { data, setData, post, put, processing, errors } = useForm<NewsForm>({
        published_on: row?.published_on_input ?? '',
        category: row?.category ?? categoryOptions[0] ?? '',
        title: row?.title ?? '',
        body: row?.body ?? '',
        is_published: row?.is_published ?? true,
    });

    const handleSubmit: FormEventHandler = (event) => {
        event.preventDefault();

        const options = { preserveScroll: true, onSuccess: onSaved };

        if (row) {
            put(route('admin.news.update', [row.id]), options);

            return;
        }

        post(route('admin.news.store'), options);
    };

    return (
        <Modal
            isOpen
            title={row ? 'ニュースを編集' : 'ニュースを作成'}
            onClose={onClose}
            className="max-w-[520px] rounded-xl p-6"
        >
            <form onSubmit={handleSubmit} className="flex flex-col gap-3">
                <div>
                    <label htmlFor="news_title" className={LABEL_CLASS}>
                        タイトル
                    </label>
                    <input
                        id="news_title"
                        type="text"
                        value={data.title}
                        placeholder="タイトルを入力"
                        className={FIELD_CLASS}
                        aria-invalid={errors.title !== undefined}
                        onChange={(event) =>
                            setData('title', event.target.value)
                        }
                    />
                    {errors.title ? (
                        <p className={ERROR_CLASS}>{errors.title}</p>
                    ) : null}
                </div>

                <div className="grid grid-cols-2 gap-2.5">
                    <div>
                        <label
                            htmlFor="news_published_on"
                            className={LABEL_CLASS}
                        >
                            掲載日
                        </label>
                        <input
                            id="news_published_on"
                            type="date"
                            value={data.published_on}
                            className={FIELD_CLASS}
                            aria-invalid={errors.published_on !== undefined}
                            onChange={(event) =>
                                setData('published_on', event.target.value)
                            }
                        />
                        {errors.published_on ? (
                            <p className={ERROR_CLASS}>{errors.published_on}</p>
                        ) : null}
                    </div>

                    <div>
                        <label htmlFor="news_category" className={LABEL_CLASS}>
                            種別
                        </label>
                        <select
                            id="news_category"
                            value={data.category}
                            className={FIELD_CLASS}
                            aria-invalid={errors.category !== undefined}
                            onChange={(event) =>
                                setData('category', event.target.value)
                            }
                        >
                            {categoryOptions.map((option) => (
                                <option key={option} value={option}>
                                    {option}
                                </option>
                            ))}
                        </select>
                        {errors.category ? (
                            <p className={ERROR_CLASS}>{errors.category}</p>
                        ) : null}
                    </div>
                </div>

                <div>
                    <label htmlFor="news_is_published" className={LABEL_CLASS}>
                        公開状態
                    </label>
                    <select
                        id="news_is_published"
                        value={data.is_published ? 'published' : 'draft'}
                        className={FIELD_CLASS}
                        onChange={(event) =>
                            setData(
                                'is_published',
                                event.target.value === 'published',
                            )
                        }
                    >
                        <option value="published">公開</option>
                        <option value="draft">非公開</option>
                    </select>
                    <p className="mt-1.5 text-[11px] text-admin-ink-muted">
                        非公開にするとフロント側の新着ニュースに表示されません。掲載日は表示用の日付で、公開の制御には使いません。
                    </p>
                </div>

                <div>
                    <label htmlFor="news_body" className={LABEL_CLASS}>
                        本文
                    </label>
                    <textarea
                        id="news_body"
                        rows={5}
                        value={data.body}
                        className={FIELD_CLASS}
                        aria-invalid={errors.body !== undefined}
                        onChange={(event) =>
                            setData('body', event.target.value)
                        }
                    />
                    {errors.body ? (
                        <p className={ERROR_CLASS}>{errors.body}</p>
                    ) : null}
                </div>

                <button
                    type="submit"
                    disabled={processing}
                    className="rounded-lg bg-admin-brand py-3 text-[13px] font-extrabold text-white disabled:opacity-60"
                >
                    保存する
                </button>
            </form>
        </Modal>
    );
}
