import { StaticPageLayout } from '@/front/Components/Support/StaticPageLayout';
import { TermsBody } from '@/front/Components/Support/TermsBody';

export default function Terms() {
    return (
        <StaticPageLayout
            title="利用規約"
            description="本サイトのご利用にあたっての会員登録・ご注文・禁止事項などの取り決めです。"
        >
            <TermsBody />
        </StaticPageLayout>
    );
}
