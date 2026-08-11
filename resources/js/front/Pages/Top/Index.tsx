import { usePage } from '@inertiajs/react';
import { FrontLayout } from '@/front/Layouts/FrontLayout';

export default function Index() {
    const { auth } = usePage<FrontSharedProps>().props;

    return (
        <FrontLayout
            title="TOP"
            description="Bianchi オンラインストアのデモサイトです。"
        >
            <div className="mx-auto max-w-[720px] px-5 py-16">
                <h1 className="text-2xl font-black">
                    Bianchi オンラインストア
                </h1>
                <p className="mt-4 text-sm leading-relaxed text-ink2">
                    {auth.user
                        ? `${auth.user.name} さん、ログイン中です。`
                        : 'ご購入には会員登録・ログインが必要です。'}
                </p>
                <p className="mt-6 rounded-2xl border border-line bg-bg2 p-6 text-sm leading-relaxed text-ink2">
                    TOPページの各セクション（ヒーロー・カテゴリ・ランキング・新着ニュース等）は単位14で実装します。
                    本ページは共通レイアウトとログイン後の遷移先を確認するための暫定表示です。
                </p>
            </div>
        </FrontLayout>
    );
}
