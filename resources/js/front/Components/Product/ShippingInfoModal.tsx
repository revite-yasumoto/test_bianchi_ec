import { Modal } from '@/shared/Components/Modal';
import { yen } from '@/shared/lib/yen';

export type ShippingTableRow = {
    prefecture_name: string;
    fee: number;
    delivery_days: number;
};

type ShippingInfoModalProps = {
    isOpen: boolean;
    onClose: () => void;
    shippingTable: ShippingTableRow[];
    ecSetting: { free_shipping_threshold: number; cod_fee: number };
};

export function ShippingInfoModal({
    isOpen,
    onClose,
    shippingTable,
    ecSetting,
}: ShippingInfoModalProps) {
    return (
        <Modal
            isOpen={isOpen}
            onClose={onClose}
            title="送料・お支払い方法・発送日数"
            className="max-h-[80dvh] max-w-[520px] overflow-y-auto"
        >
            <h3 className="text-[13px] font-extrabold">送料</h3>
            <p className="mt-1.5 text-[12.5px] leading-[1.9] text-ink2">
                送料はお届け先の都道府県ごとに異なります（下表）。
                {yen(ecSetting.free_shipping_threshold)}
                （税込）以上のご購入で送料無料です。
            </p>

            <h3 className="mt-4 text-[13px] font-extrabold">お支払い方法</h3>
            <p className="mt-1.5 text-[12.5px] leading-[1.9] text-ink2">
                銀行振込（前払い・ご入金確認後の発送）／代金引換（手数料{' '}
                {yen(ecSetting.cod_fee)}）
            </p>

            <h3 className="mt-4 text-[13px] font-extrabold">
                送料・発送日数の目安
            </h3>
            <table className="mt-2 w-full border-t border-line text-[12.5px]">
                <caption className="sr-only">
                    都道府県ごとの送料と発送日数
                </caption>
                <thead>
                    <tr className="border-b border-line text-left text-ink2">
                        <th scope="col" className="py-2 font-bold">
                            都道府県
                        </th>
                        <th scope="col" className="py-2 text-right font-bold">
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
        </Modal>
    );
}
