import {
    StaticPageLayout,
    StaticSection,
} from '@/front/Components/Support/StaticPageLayout';

/** 記載内容はデモ用のダミー */
export default function Privacy() {
    return (
        <StaticPageLayout
            title="プライバシーポリシー"
            description="お客様の個人情報の取得・利用目的・管理・第三者提供についての方針です。"
        >
            <p className="text-[12.5px] leading-[1.95] text-ink2">
                本サイトはデモンストレーション用のサンプルサイトです。以下の記載内容はすべて架空のものです。
            </p>

            <StaticSection heading="1. 取得する情報">
                <p>
                    当店は、会員登録・ご注文・お問い合わせの際に、お名前、フリガナ、メールアドレス、電話番号、配送先住所をお預かりします。
                    クレジットカード情報は取り扱いません。
                </p>
            </StaticSection>

            <StaticSection heading="2. 利用目的">
                <p>
                    お預かりした情報は、商品の発送、ご注文内容の確認、お問い合わせへの回答、および法令に基づく対応のために利用します。
                    これら以外の目的で利用することはありません。
                </p>
            </StaticSection>

            <StaticSection heading="3. 第三者への提供">
                <p>
                    法令に基づく場合を除き、お客様の同意なく第三者へ提供することはありません。
                    商品の配送に必要な範囲で、配送業者へ配送先情報を提供します。
                </p>
            </StaticSection>

            <StaticSection heading="4. 情報の管理">
                <p>
                    お預かりした情報への不正アクセス・紛失・改ざん・漏えいを防ぐため、必要かつ適切な安全管理措置を講じます。
                    パスワードは復元できない形式で保存します。
                </p>
            </StaticSection>

            <StaticSection heading="5. 開示・訂正・削除">
                <p>
                    お客様ご自身の情報の確認・訂正は、マイページの会員情報変更よりいつでも行えます。
                    その他のご請求はお問い合わせフォームよりご連絡ください。
                </p>
            </StaticSection>

            <StaticSection heading="6. 本ポリシーの変更">
                <p>
                    本ポリシーの内容は、法令の改正やサービス内容の変更に応じて予告なく変更する場合があります。
                    変更後の内容は本ページに掲載した時点から適用されます。
                </p>
            </StaticSection>
        </StaticPageLayout>
    );
}
