import { Link, useForm } from '@inertiajs/react';
import { MyPageLayout } from '@/front/Layouts/MyPageLayout';
import { Button } from '@/shared/Components/Button';
import { Checkbox } from '@/shared/Components/Checkbox';
import { TextInput } from '@/shared/Components/TextInput';

type WithdrawalForm = {
    password: string;
    agree: boolean;
};

const NOTICES = [
    'ログインできなくなります。',
    '同じメールアドレスでの再登録はできません。',
    'ご注文の履歴は保持されます。',
    '退会後の取り消しはできません。',
];

export default function Withdrawal() {
    const { data, setData, post, processing, errors } = useForm<WithdrawalForm>(
        { password: '', agree: false },
    );

    return (
        <MyPageLayout
            title="退会手続き"
            description="Bianchi オンラインストアの退会手続きのページです。"
            heading="退会手続き"
        >
            <form
                onSubmit={(event) => {
                    event.preventDefault();
                    post(route('mypage.withdrawal.store'));
                }}
                className="flex max-w-[560px] flex-col gap-4"
            >
                <div className="rounded-2xl border border-coral/40 bg-coral/5 p-5">
                    <h2 className="text-[13px] font-extrabold text-coral">
                        退会すると以下のとおりになります
                    </h2>
                    <ul className="mt-2.5 flex list-disc flex-col gap-1 pl-5 text-[12.5px] leading-[1.8] text-ink2">
                        {NOTICES.map((notice) => (
                            <li key={notice}>{notice}</li>
                        ))}
                    </ul>
                </div>

                <TextInput
                    id="withdrawal-password"
                    label="パスワード"
                    type="password"
                    autoComplete="current-password"
                    required
                    value={data.password}
                    error={errors.password}
                    placeholder="ご本人確認のため入力してください"
                    onChange={(event) =>
                        setData('password', event.target.value)
                    }
                />

                <Checkbox
                    id="withdrawal-agree"
                    checked={data.agree}
                    error={errors.agree}
                    onChange={(event) => setData('agree', event.target.checked)}
                >
                    上記の内容を確認しました
                </Checkbox>

                <div className="mt-1 flex flex-wrap items-center gap-3">
                    <Button
                        type="submit"
                        variant="cta"
                        disabled={processing || !data.agree}
                        className="px-8 py-3.5"
                    >
                        退会する
                    </Button>
                    <Link
                        href={route('mypage.profile')}
                        className="text-[13px] font-bold text-ink2 underline"
                    >
                        戻る
                    </Link>
                </div>
            </form>
        </MyPageLayout>
    );
}
