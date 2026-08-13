import { Link } from '@inertiajs/react';
import { OrderCard } from '@/front/Components/MyPage/OrderCard';
import type { OrderSummary } from '@/front/Components/MyPage/OrderCard';
import { Pagination } from '@/front/Components/Pagination';
import { MyPageLayout } from '@/front/Layouts/MyPageLayout';

type Props = {
    orders: Paginated<OrderSummary>;
};

export default function Orders({ orders }: Props) {
    return (
        <MyPageLayout
            title="注文履歴"
            description="ご注文の履歴と内容の確認、発送前のご注文のキャンセルができます。"
            heading="注文履歴"
        >
            {orders.data.length === 0 ? (
                <div className="rounded-[20px] bg-bg2 px-5 py-[60px] text-center">
                    <p className="mb-3.5 text-[15px] font-bold">
                        ご注文はまだありません
                    </p>
                    <Link
                        href={route('products.index')}
                        className="inline-block rounded-full bg-brand px-6.5 py-3.5 text-sm font-bold text-white"
                    >
                        商品を探す
                    </Link>
                </div>
            ) : (
                <>
                    <ul className="flex flex-col gap-3.5">
                        {orders.data.map((order) => (
                            <li key={order.id}>
                                <OrderCard order={order} />
                            </li>
                        ))}
                    </ul>
                    <Pagination links={orders.links} />
                </>
            )}
        </MyPageLayout>
    );
}
