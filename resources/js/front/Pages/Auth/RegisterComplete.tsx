import { Link } from '@inertiajs/react';
import { FrontLayout } from '@/front/Layouts/FrontLayout';

export default function RegisterComplete() {
    return (
        <FrontLayout
            title="会員登録完了"
            description="Bianchi オンラインストアの会員登録が完了しました。"
        >
            <div className="flex justify-center px-[22px] pt-[70px] pb-[90px]">
                <div className="w-full max-w-[520px] text-center">
                    <span
                        aria-hidden="true"
                        className="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-teal text-white"
                    >
                        <svg
                            viewBox="0 0 24 24"
                            className="h-7 w-7"
                            fill="none"
                            stroke="currentColor"
                            strokeWidth="3"
                            strokeLinecap="round"
                            strokeLinejoin="round"
                        >
                            <path d="M4 12.5 9.5 18 20 6.5" />
                        </svg>
                    </span>
                    <h1 className="mt-5.5 text-[26px] font-black">
                        会員登録が完了しました
                    </h1>
                    <p className="mt-3 text-[13.5px] leading-[1.9] text-ink2">
                        ご登録のメールアドレスへ完了のご案内をお送りしました。
                        ログインしてお買い物をお楽しみください。
                    </p>

                    <div className="mt-6.5 flex flex-wrap justify-center gap-2.5">
                        <Link
                            href={route('login')}
                            className="rounded-full bg-brand px-6.5 py-3.5 text-sm font-bold text-white"
                        >
                            ログインする
                        </Link>
                        <Link
                            href={route('top')}
                            className="rounded-full border border-line px-6.5 py-3.5 text-sm font-bold"
                        >
                            TOPへ戻る
                        </Link>
                    </div>
                </div>
            </div>
        </FrontLayout>
    );
}
