import { Link } from '@inertiajs/react';
import {
    StaticPageLayout,
    StaticSection,
} from '@/front/Components/Support/StaticPageLayout';
import type { ShippingTableRow } from '@/front/Components/Product/ShippingInfoModal';
import { yen } from '@/shared/lib/yen';

type Props = {
    shippingTable: ShippingTableRow[];
    ecSetting: { free_shipping_threshold: number; cod_fee: number };
};

export default function Guide({ shippingTable, ecSetting }: Props) {
    return (
        <StaticPageLayout
            title="買い物ガイド"
            description="送料・お支払い方法・発送日数・返品交換についてのご案内です。"
        >
            <StaticSection heading="お支払い方法">
                <p>
                    銀行振込（前払い）と代金引換をご利用いただけます。クレジットカード決済は取り扱っておりません。
                </p>
                <ul className="list-disc pl-5">
                    <li>
                        銀行振込：ご注文後にお振込先をご案内します。ご入金の確認後に発送します。
                    </li>
                    <li>
                        代金引換：商品のお受け取り時にお支払いください。手数料{' '}
                        {yen(ecSetting.cod_fee)}
                        を頂戴します。
                    </li>
                </ul>
            </StaticSection>

            <StaticSection heading="送料">
                <p>
                    送料はお届け先の都道府県ごとに異なります。
                    {yen(ecSetting.free_shipping_threshold)}
                    （税込）以上のご購入で送料無料です。
                </p>
                <div className="overflow-x-auto">
                    <table className="w-full min-w-[320px] border-t border-line text-[12.5px]">
                        <caption className="sr-only">
                            都道府県ごとの送料とお届け日数
                        </caption>
                        <thead>
                            <tr className="border-b border-line text-left text-ink2">
                                <th scope="col" className="py-2 font-bold">
                                    都道府県
                                </th>
                                <th
                                    scope="col"
                                    className="py-2 text-right font-bold"
                                >
                                    送料 / 日数
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            {shippingTable.map((row) => (
                                <tr
                                    key={row.prefecture_name}
                                    className="border-b border-line"
                                >
                                    <th
                                        scope="row"
                                        className="py-2 text-left font-normal"
                                    >
                                        {row.prefecture_name}
                                    </th>
                                    <td className="py-2 text-right font-mono">
                                        {yen(row.fee)} / {row.delivery_days}日
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </StaticSection>

            <StaticSection heading="発送とお届け">
                <p>
                    上表のお届け日数は、銀行振込の場合はご入金確認後、代金引換の場合はご注文確定後からの日数です。
                    在庫状況により前後する場合があります。海外への発送は承っておりません。
                </p>
            </StaticSection>

            <StaticSection heading="返品・交換">
                <p>
                    商品の不良・誤配送の場合は、商品到着後7日以内にお問い合わせフォームよりご連絡ください。
                    送料当店負担にて交換または返金を承ります。
                </p>
                <p>
                    お客様のご都合による返品は、未使用かつ商品到着後7日以内のものに限り承ります。返送料はお客様のご負担となります。
                    （本ページはデモサイトのため、記載の条件は架空のものです。）
                </p>
            </StaticSection>

            <StaticSection heading="お問い合わせ">
                <p>
                    ご不明な点は
                    <Link
                        href={route('contact')}
                        className="mx-1 font-bold text-brand"
                    >
                        お問い合わせフォーム
                    </Link>
                    よりご連絡ください。3営業日以内にご返信いたします。
                </p>
            </StaticSection>
        </StaticPageLayout>
    );
}
