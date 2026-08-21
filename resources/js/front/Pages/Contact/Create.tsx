import { useForm } from '@inertiajs/react';
import { FrontLayout } from '@/front/Layouts/FrontLayout';
import { TextInput } from '@/shared/Components/TextInput';
import { TextareaInput } from '@/shared/Components/TextareaInput';

type Props = {
    defaults: {
        /** ログイン中は会員の氏名、未ログインは空 */
        name: string;
        email: string;
        /** 対象商品が確定していないときの初期値 */
        product_name: string;
    };
    /** クエリ `?product_id=` で公開商品を引き当てられたときのみ値が入る */
    product: { id: number; name: string } | null;
};

export default function Create({ defaults, product }: Props) {
    const { data, setData, post, processing, errors, reset } = useForm({
        name: defaults.name,
        email: defaults.email,
        product_id: product?.id ?? null,
        product_name: defaults.product_name,
        body: '',
    });

    return (
        <FrontLayout
            title="お問い合わせ"
            description="商品・ご注文・配送についてのお問い合わせを受け付けています。"
            canonical={route('contact')}
        >
            <div className="mx-auto max-w-[560px] px-[22px] py-[26px] pb-16">
                <h1 className="text-[26px] font-black">お問い合わせ</h1>
                <p className="mt-2.5 text-[13px] leading-[1.9] text-ink2">
                    商品・ご注文・配送についてのご質問を承ります。3営業日以内にご返信いたします。
                </p>

                <form
                    onSubmit={(event) => {
                        event.preventDefault();
                        post(route('contact.store'), {
                            preserveScroll: true,
                            onSuccess: () => reset('body'),
                        });
                    }}
                    className="mt-6 flex flex-col gap-3.5"
                >
                    <TextInput
                        id="contact-name"
                        label="お名前"
                        required
                        autoComplete="name"
                        value={data.name}
                        error={errors.name}
                        placeholder="山田 太郎"
                        onChange={(event) =>
                            setData('name', event.target.value)
                        }
                    />
                    <TextInput
                        id="contact-email"
                        label="メールアドレス"
                        type="email"
                        required
                        autoComplete="email"
                        value={data.email}
                        error={errors.email}
                        placeholder="taro@example.com"
                        onChange={(event) =>
                            setData('email', event.target.value)
                        }
                    />
                    {product ? (
                        // 対象商品が確定している問い合わせは書き換えさせない（商品名はサーバー側で引き直す）
                        <dl>
                            <dt className="mb-1.5 text-[12.5px] font-bold">
                                対象商品
                            </dt>
                            <dd className="text-[13px] leading-[1.9]">
                                {product.name}
                            </dd>
                        </dl>
                    ) : (
                        <TextInput
                            id="contact-product-name"
                            label="対象商品"
                            value={data.product_name}
                            error={errors.product_name}
                            placeholder="商品名（任意）"
                            onChange={(event) =>
                                setData('product_name', event.target.value)
                            }
                        />
                    )}
                    <TextareaInput
                        id="contact-body"
                        label="お問い合わせ内容"
                        required
                        rows={7}
                        value={data.body}
                        error={errors.body}
                        placeholder="10文字以上でご記入ください"
                        onChange={(event) =>
                            setData('body', event.target.value)
                        }
                    />

                    <button
                        type="submit"
                        disabled={processing}
                        className="mt-1 rounded-full bg-brand py-3.5 text-sm font-extrabold text-white disabled:opacity-60"
                    >
                        送信する
                    </button>
                </form>
            </div>
        </FrontLayout>
    );
}
