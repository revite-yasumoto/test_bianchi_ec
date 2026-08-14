import type { ProductCardData } from '@/front/Components/Product/ProductCard';
import { CategoryEntries } from '@/front/Components/Top/CategoryEntries';
import type { CategoryEntryData } from '@/front/Components/Top/CategoryEntries';
import { HeroSlider } from '@/front/Components/Top/HeroSlider';
import type { BannerData } from '@/front/Components/Top/HeroSlider';
import { HistorySection } from '@/front/Components/Top/HistorySection';
import { NewsSection } from '@/front/Components/Top/NewsSection';
import type { NewsRow } from '@/front/Components/Top/NewsSection';
import { NoticeBar } from '@/front/Components/Top/NoticeBar';
import { RankingSection } from '@/front/Components/Top/RankingSection';
import type {
    RankingItem,
    RankingTab,
} from '@/front/Components/Top/RankingSection';
import { RecommendSection } from '@/front/Components/Top/RecommendSection';
import { FrontLayout } from '@/front/Layouts/FrontLayout';

type Props = {
    notice: { id: number; title: string } | null;
    banners: BannerData[];
    categoryEntries: CategoryEntryData[];
    rankingTabs: RankingTab[];
    rankings: Record<string, RankingItem[]>;
    rankingUpdatedAt: string | null;
    rankingUpdatedAtIso: string | null;
    recommends: ProductCardData[];
    histories: ProductCardData[];
    news: NewsRow[];
};

export default function Index({
    notice,
    banners,
    categoryEntries,
    rankingTabs,
    rankings,
    rankingUpdatedAt,
    rankingUpdatedAtIso,
    recommends,
    histories,
    news,
}: Props) {
    return (
        <FrontLayout
            title="TOP"
            description="Bianchi オンラインストア。ロードバイク・MTB・eバイクからパーツ・アパレルまで取り扱っています。"
        >
            {/* 視覚的な見出しはメインビジュアルが担うため、ページの主題は読み上げ用に置く */}
            <h1 className="sr-only">Bianchi オンラインストア</h1>
            {notice ? <NoticeBar notice={notice} /> : null}
            <HeroSlider banners={banners} />
            <CategoryEntries entries={categoryEntries} />
            <RankingSection
                tabs={rankingTabs}
                rankings={rankings}
                updatedAt={rankingUpdatedAt}
                updatedAtIso={rankingUpdatedAtIso}
            />
            <RecommendSection products={recommends} />
            <HistorySection products={histories} />
            <NewsSection news={news} />
        </FrontLayout>
    );
}
