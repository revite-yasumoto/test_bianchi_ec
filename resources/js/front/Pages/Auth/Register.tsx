import { Link, useForm } from '@inertiajs/react';
import type { FormEventHandler } from 'react';
import { FrontLayout } from '@/front/Layouts/FrontLayout';
import { Button } from '@/shared/Components/Button';
import { Checkbox } from '@/shared/Components/Checkbox';
import { TextInput } from '@/shared/Components/TextInput';

type RegisterForm = {
    name: string;
    name_kana: string;
    email: string;
    password: string;
    password_confirmation: string;
    agree: boolean;
};

export default function Register() {
    const { data, setData, post, processing, errors, reset } =
        useForm<RegisterForm>({
            name: '',
            name_kana: '',
            email: '',
            password: '',
            password_confirmation: '',
            agree: false,
        });

    const handleSubmit: FormEventHandler = (event) => {
        event.preventDefault();

        post(route('register.store'), {
            onFinish: () => reset('password', 'password_confirmation'),
        });
    };

    return (
        <FrontLayout
            title="会員登録"
            description="Bianchi オンラインストアの会員登録ページです。"
        >
            <div className="flex justify-center px-5 py-11 pb-16">
                <form onSubmit={handleSubmit} className="w-full max-w-[520px]">
                    <h1 className="text-center text-2xl font-black">
                        会員登録
                    </h1>

                    <div className="mt-7 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <TextInput
                            id="name"
                            label="お名前"
                            autoComplete="name"
                            required
                            value={data.name}
                            error={errors.name}
                            placeholder="山田 太郎"
                            onChange={(event) =>
                                setData('name', event.target.value)
                            }
                        />
                        <TextInput
                            id="name_kana"
                            label="お名前（カナ）"
                            value={data.name_kana}
                            error={errors.name_kana}
                            placeholder="ヤマダ タロウ"
                            onChange={(event) =>
                                setData('name_kana', event.target.value)
                            }
                        />
                        <TextInput
                            id="email"
                            label="メールアドレス"
                            type="email"
                            autoComplete="email"
                            required
                            className="sm:col-span-2"
                            value={data.email}
                            error={errors.email}
                            placeholder="you@example.com"
                            onChange={(event) =>
                                setData('email', event.target.value)
                            }
                        />
                        <TextInput
                            id="password"
                            label="パスワード"
                            type="password"
                            autoComplete="new-password"
                            required
                            className="sm:col-span-2"
                            value={data.password}
                            error={errors.password}
                            placeholder="8文字以上"
                            onChange={(event) =>
                                setData('password', event.target.value)
                            }
                        />
                        <TextInput
                            id="password_confirmation"
                            label="パスワード（確認）"
                            type="password"
                            autoComplete="new-password"
                            required
                            className="sm:col-span-2"
                            value={data.password_confirmation}
                            placeholder="もう一度入力してください"
                            onChange={(event) =>
                                setData(
                                    'password_confirmation',
                                    event.target.value,
                                )
                            }
                        />
                    </div>

                    <Checkbox
                        id="agree"
                        className="mt-6"
                        checked={data.agree}
                        error={errors.agree}
                        onChange={(event) =>
                            setData('agree', event.target.checked)
                        }
                    >
                        <Link
                            href={route('legal.terms')}
                            className="font-bold text-brand underline"
                        >
                            利用規約
                        </Link>
                        {' および '}
                        <Link
                            href={route('legal.privacy')}
                            className="font-bold text-brand underline"
                        >
                            プライバシーポリシー
                        </Link>
                        {' に同意します'}
                    </Checkbox>

                    <Button
                        type="submit"
                        disabled={processing}
                        className="mt-5 w-full py-4 text-[15px]"
                    >
                        登録する
                    </Button>

                    <p className="mt-5 text-center text-[13px] text-ink2">
                        すでにアカウントをお持ちの方は
                        <Link
                            href={route('login')}
                            className="ml-1 font-bold text-brand"
                        >
                            ログイン
                        </Link>
                    </p>
                </form>
            </div>
        </FrontLayout>
    );
}
