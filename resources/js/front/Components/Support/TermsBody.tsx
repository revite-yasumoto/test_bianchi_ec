import { StaticSection } from '@/front/Components/Support/StaticPageLayout';

type TermsBodyProps = {
    /** モーダル内ではダイアログの見出し（h2）の下位に入るため 3 を渡す */
    headingLevel?: 2 | 3;
};

/** 記載内容はデモ用のダミー。利用規約ページと会員登録画面のモーダルで共用する */
export function TermsBody({ headingLevel }: TermsBodyProps) {
    return (
        <div className="flex flex-col gap-7">
            <p className="text-[15px] leading-[1.95] text-ink2">
                本サイトはデモンストレーション用のサンプルサイトです。以下の記載内容はすべて架空のものです。
            </p>

            <StaticSection heading="第1条（適用）" headingLevel={headingLevel}>
                <p>
                    本規約は、当店が提供するオンラインストア（以下「本サービス」）の利用条件を定めるものです。
                    ご利用のお客様は本規約に同意したものとみなします。
                </p>
            </StaticSection>

            <StaticSection
                heading="第2条（会員登録）"
                headingLevel={headingLevel}
            >
                <p>
                    本サービスでのご購入には会員登録が必要です。登録情報は正確かつ最新の内容をご入力ください。
                    登録内容に虚偽があった場合、当店は会員資格を停止することがあります。
                </p>
            </StaticSection>

            <StaticSection
                heading="第3条（アカウントの管理）"
                headingLevel={headingLevel}
            >
                <p>
                    パスワードの管理はお客様の責任で行ってください。第三者による利用で生じた損害について、当店は責任を負いません。
                </p>
            </StaticSection>

            <StaticSection
                heading="第4条（注文と契約の成立）"
                headingLevel={headingLevel}
            >
                <p>
                    ご注文は当店が注文内容を確認し、注文完了画面を表示した時点で成立します。
                    在庫の状況により、成立後にご注文をお受けできない場合があります。
                </p>
            </StaticSection>

            <StaticSection
                heading="第5条（キャンセル）"
                headingLevel={headingLevel}
            >
                <p>
                    ご注文のキャンセルは、ステータスが「注文受付」「入金待ち」の間はマイページから行えます。
                    それ以降のキャンセルはお問い合わせフォームよりご連絡ください。
                </p>
            </StaticSection>

            <StaticSection
                heading="第6条（禁止事項）"
                headingLevel={headingLevel}
            >
                <p>
                    転売を目的とした購入、虚偽の情報による登録、本サービスの運営を妨げる行為、他のお客様や第三者の権利を侵害する行為を禁止します。
                </p>
            </StaticSection>

            <StaticSection
                heading="第7条（免責事項）"
                headingLevel={headingLevel}
            >
                <p>
                    天災・通信回線の障害など当店の責によらない事由により本サービスを提供できない場合、当店は責任を負いません。
                </p>
            </StaticSection>

            <StaticSection
                heading="第8条（規約の変更）"
                headingLevel={headingLevel}
            >
                <p>
                    当店は必要と判断した場合、お客様への予告なく本規約を変更できるものとします。
                    変更後の規約は本ページに掲載した時点から効力を生じます。
                </p>
            </StaticSection>
        </div>
    );
}
