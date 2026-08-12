import { AdminLayout } from '@/admin/Layouts/AdminLayout';
import {
    LatestOrderTable,
    type LatestOrderRow,
} from '@/admin/Components/Dashboard/LatestOrderTable';
import {
    SalesBarChart,
    type SalesChartBar,
} from '@/admin/Components/Dashboard/SalesBarChart';
import { SummaryCard } from '@/admin/Components/Dashboard/SummaryCard';
import { yen } from '@/shared/lib/yen';

type Props = {
    summary: {
        today_sales: number;
        today_sales_note: string;
        month_sales: number;
        month_sales_note: string;
        new_order_count: number;
        awaiting_payment_count: number;
    };
    chart: SalesChartBar[];
    latestOrders: LatestOrderRow[];
};

const CARD_CLASS = 'rounded-xl border border-admin-line bg-white p-5';

const CARD_TITLE_CLASS = 'mb-4 text-[13px] font-extrabold text-admin-ink';

export default function Index({ summary, chart, latestOrders }: Props) {
    return (
        <AdminLayout title="ダッシュボード">
            <div className="grid grid-cols-[repeat(auto-fit,minmax(180px,1fr))] gap-3.5">
                <SummaryCard
                    label="本日の売上"
                    value={yen(summary.today_sales)}
                    note={summary.today_sales_note}
                />
                <SummaryCard
                    label="今月の売上"
                    value={yen(summary.month_sales)}
                    note={summary.month_sales_note}
                />
                <SummaryCard
                    label="新規注文"
                    value={`${summary.new_order_count}件`}
                    note="本日 受付分"
                    tone="brand"
                />
                <SummaryCard
                    label="入金待ち"
                    value={`${summary.awaiting_payment_count}件`}
                    note="要確認"
                    tone="danger"
                />
            </div>

            <div className="mt-4 grid grid-cols-[repeat(auto-fit,minmax(320px,1fr))] gap-4">
                <section className={CARD_CLASS}>
                    <h2 className={CARD_TITLE_CLASS}>売上推移（直近7日間）</h2>
                    <SalesBarChart bars={chart} />
                </section>

                <section className={CARD_CLASS}>
                    <h2 className={CARD_TITLE_CLASS}>最新の注文</h2>
                    <LatestOrderTable orders={latestOrders} />
                </section>
            </div>
        </AdminLayout>
    );
}
