import { StaticPageLayout } from '@/front/Components/Support/StaticPageLayout';

/** 記載内容はデモ用のダミー。会社名・住所・連絡先はすべて架空値 */
const ENTRIES: { term: string; description: string }[] = [
    { term: '販売業者', description: '株式会社アルキメデス商会（架空）' },
    { term: '運営統括責任者', description: '架空 太郎' },
    {
        term: '所在地',
        description: '〒000-0000 東京都架空区架空町0-0-0 架空ビル0F',
    },
    {
        term: '電話番号',
        description: '000-0000-0000（受付時間 平日10:00〜17:00）',
    },
    { term: 'メールアドレス', description: 'support@example.test' },
    {
        term: '販売価格',
        description:
            '各商品ページに表示された価格（すべて税込表示）に、送料および代引き手数料を加えた金額です。',
    },
    {
        term: '商品代金以外の必要料金',
        description:
            '送料（お届け先の都道府県により異なります）、代金引換をご利用の場合の代引き手数料、銀行振込の振込手数料。',
    },
    {
        term: 'お支払い方法',
        description: '銀行振込（前払い）、代金引換の2種類です。',
    },
    {
        term: 'お支払い期限',
        description:
            '銀行振込の場合はご注文日から7日以内にお振込みください。代金引換の場合は商品お受け取り時にお支払いください。',
    },
    {
        term: '引渡し時期',
        description:
            '銀行振込の場合はご入金確認後、代金引換の場合はご注文確定後に発送します。お届け日数は買い物ガイドをご覧ください。',
    },
    {
        term: '返品・交換について',
        description:
            '商品の不良・誤配送の場合は商品到着後7日以内にご連絡ください。お客様のご都合による返品は未使用かつ商品到着後7日以内のものに限り承ります（返送料はお客様のご負担）。',
    },
];

export default function Tokushoho() {
    return (
        <StaticPageLayout
            title="特定商取引法に基づく表記"
            description="販売業者・所在地・お支払い方法・引渡し時期など、特定商取引法に基づく表記です。"
        >
            <p className="text-[12.5px] leading-[1.95] text-ink2">
                本サイトはデモンストレーション用のサンプルサイトです。以下の記載内容はすべて架空のものであり、実在の企業・個人とは関係ありません。
            </p>

            <dl className="border-t border-line">
                {ENTRIES.map((entry) => (
                    <div
                        key={entry.term}
                        className="grid gap-1 border-b border-line py-3.5 sm:grid-cols-[180px_minmax(0,1fr)] sm:gap-4"
                    >
                        <dt className="text-[13px] font-bold">{entry.term}</dt>
                        <dd className="text-[12.5px] leading-[1.95] text-ink2">
                            {entry.description}
                        </dd>
                    </div>
                ))}
            </dl>
        </StaticPageLayout>
    );
}
