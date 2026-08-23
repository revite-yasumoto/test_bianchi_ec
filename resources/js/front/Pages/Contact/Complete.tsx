import { Link } from '@inertiajs/react';
import { FrontLayout } from '@/front/Layouts/FrontLayout';

type Props = {
    /** 送信直後の遷移でのみ渡る問い合わせ番号（`INQ-YYMM-NNNN`） */
    contactNumber: string;
};

export default function Complete({ contactNumber }: Props) {
    return (
        <FrontLayout
            title="お問い合わせ完了"
            description="お問い合わせを受け付けました。3営業日以内にご返信いたします。"
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
                        お問い合わせを送信しました
                    </h1>
                    <p className="mt-3 text-[13.5px] leading-[1.9] text-ink2">
                        3営業日以内にご返信いたします。
                        ご入力のメールアドレスへ控えをお送りしました。
                    </p>

                    <dl className="mt-6.5 rounded-2xl bg-bg2 p-5.5">
                        <dt className="text-[11.5px] font-bold text-ink2">
                            問い合わせ番号
                        </dt>
                        <dd className="mt-1.5 font-mono text-[22px] font-bold">
                            {contactNumber}
                        </dd>
                    </dl>

                    <div className="mt-6.5 flex justify-center">
                        <Link
                            href={route('top')}
                            className="rounded-full bg-brand px-6.5 py-3.5 text-sm font-bold text-white"
                        >
                            TOPへ戻る
                        </Link>
                    </div>
                </div>
            </div>
        </FrontLayout>
    );
}
