import { PrivacyBody } from '@/front/Components/Support/PrivacyBody';
import { StaticPageLayout } from '@/front/Components/Support/StaticPageLayout';

export default function Privacy() {
    return (
        <StaticPageLayout
            title="プライバシーポリシー"
            description="お客様の個人情報の取得・利用目的・管理・第三者提供についての方針です。"
        >
            <PrivacyBody />
        </StaticPageLayout>
    );
}
