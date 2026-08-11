import { Link, useForm } from '@inertiajs/react';
import type { FormEventHandler } from 'react';
import { FrontLayout } from '@/front/Layouts/FrontLayout';
import { Button } from '@/shared/Components/Button';
import { Checkbox } from '@/shared/Components/Checkbox';
import { TextInput } from '@/shared/Components/TextInput';

type LoginForm = {
    email: string;
    password: string;
    remember: boolean;
};

export default function Login() {
    const { data, setData, post, processing, errors, reset } =
        useForm<LoginForm>({
            email: '',
            password: '',
            remember: false,
        });

    const handleSubmit: FormEventHandler = (event) => {
        event.preventDefault();

        post(route('login.store'), {
            onFinish: () => reset('password'),
        });
    };

    return (
        <FrontLayout
            title="ログイン"
            description="Bianchi オンラインストアのログインページです。"
        >
            <div className="flex justify-center px-5 py-11 pb-16">
                <form onSubmit={handleSubmit} className="w-full max-w-[420px]">
                    <h1 className="text-center text-2xl font-black">
                        ログイン
                    </h1>
                    <p className="mt-2 text-center text-[13px] text-ink2">
                        ご購入には会員登録・ログインが必要です
                    </p>

                    <div className="mt-7 flex flex-col gap-4">
                        <TextInput
                            id="email"
                            label="メールアドレス"
                            type="email"
                            autoComplete="email"
                            required
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
                            autoComplete="current-password"
                            required
                            value={data.password}
                            error={errors.password}
                            placeholder="8文字以上"
                            onChange={(event) =>
                                setData('password', event.target.value)
                            }
                        />

                        <Checkbox
                            id="remember"
                            checked={data.remember}
                            onChange={(event) =>
                                setData('remember', event.target.checked)
                            }
                        >
                            この端末でログイン状態を保持する
                        </Checkbox>

                        <Button
                            type="submit"
                            disabled={processing}
                            className="mt-1.5 py-4 text-[15px]"
                        >
                            ログイン
                        </Button>

                        <Link
                            href={route('register')}
                            className="text-center text-[13px] font-bold text-brand"
                        >
                            新規会員登録はこちら →
                        </Link>
                    </div>
                </form>
            </div>
        </FrontLayout>
    );
}
