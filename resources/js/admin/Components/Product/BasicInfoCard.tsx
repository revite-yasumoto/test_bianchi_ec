type CategoryOption = { id: number; name: string };

type BasicInfoFields = {
    product_code: string;
    name: string;
    category_id: string;
    price: string;
    description: string;
    is_published: boolean;
};

type BasicInfoCardProps = {
    values: BasicInfoFields;
    categories: CategoryOption[];
    errors: Record<string, string>;
    onChange: (field: keyof BasicInfoFields, value: string | boolean) => void;
};

const FIELD_CLASS =
    'w-full rounded-lg border border-admin-line px-3 py-2 text-base';

function FieldError({ message }: { message?: string }) {
    if (!message) {
        return null;
    }

    return (
        <p className="mt-1 text-[11px] font-bold text-admin-danger">
            {message}
        </p>
    );
}

export function BasicInfoCard({
    values,
    categories,
    errors,
    onChange,
}: BasicInfoCardProps) {
    return (
        <div className="rounded-xl border border-admin-line bg-white p-5">
            <h2 className="mb-3.5 text-[13px] font-extrabold text-admin-ink">
                基本情報
            </h2>

            <div className="grid grid-cols-[repeat(auto-fit,minmax(150px,1fr))] gap-3">
                <div>
                    <label
                        htmlFor="product_code"
                        className="mb-1.5 block text-[11.5px] font-bold text-admin-ink"
                    >
                        商品ID（ユーザー入力）
                    </label>
                    <input
                        id="product_code"
                        type="text"
                        value={values.product_code}
                        placeholder="RC7-105"
                        className={FIELD_CLASS}
                        onChange={(event) =>
                            onChange('product_code', event.target.value)
                        }
                    />
                    <FieldError message={errors.product_code} />
                </div>

                <div>
                    <label
                        htmlFor="price"
                        className="mb-1.5 block text-[11.5px] font-bold text-admin-ink"
                    >
                        価格（税込）
                    </label>
                    <input
                        id="price"
                        type="number"
                        min={0}
                        value={values.price}
                        placeholder="398000"
                        className={FIELD_CLASS}
                        onChange={(event) =>
                            onChange('price', event.target.value)
                        }
                    />
                    <FieldError message={errors.price} />
                </div>

                <div className="col-span-full">
                    <label
                        htmlFor="name"
                        className="mb-1.5 block text-[11.5px] font-bold text-admin-ink"
                    >
                        商品名
                    </label>
                    <input
                        id="name"
                        type="text"
                        value={values.name}
                        className={FIELD_CLASS}
                        onChange={(event) =>
                            onChange('name', event.target.value)
                        }
                    />
                    <FieldError message={errors.name} />
                </div>

                <div>
                    <label
                        htmlFor="category_id"
                        className="mb-1.5 block text-[11.5px] font-bold text-admin-ink"
                    >
                        カテゴリ
                    </label>
                    <select
                        id="category_id"
                        value={values.category_id}
                        className={FIELD_CLASS}
                        onChange={(event) =>
                            onChange('category_id', event.target.value)
                        }
                    >
                        <option value="">選択してください</option>
                        {categories.map((category) => (
                            <option key={category.id} value={category.id}>
                                {category.name}
                            </option>
                        ))}
                    </select>
                    <FieldError message={errors.category_id} />
                </div>

                <div>
                    <label
                        htmlFor="is_published"
                        className="mb-1.5 block text-[11.5px] font-bold text-admin-ink"
                    >
                        公開状態
                    </label>
                    <select
                        id="is_published"
                        value={values.is_published ? 'published' : 'draft'}
                        className={FIELD_CLASS}
                        onChange={(event) =>
                            onChange(
                                'is_published',
                                event.target.value === 'published',
                            )
                        }
                    >
                        <option value="published">公開</option>
                        <option value="draft">非公開</option>
                    </select>
                    <FieldError message={errors.is_published} />
                </div>

                <div className="col-span-full">
                    <label
                        htmlFor="description"
                        className="mb-1.5 block text-[11.5px] font-bold text-admin-ink"
                    >
                        商品説明
                    </label>
                    <textarea
                        id="description"
                        rows={4}
                        value={values.description}
                        className={FIELD_CLASS}
                        onChange={(event) =>
                            onChange('description', event.target.value)
                        }
                    />
                    <FieldError message={errors.description} />
                </div>
            </div>
        </div>
    );
}
