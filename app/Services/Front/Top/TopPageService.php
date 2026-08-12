<?php

declare(strict_types=1);

namespace App\Services\Front\Top;

use App\Actions\Front\Product\BuildProductCard;
use App\Models\Banner;
use App\Models\Category;
use App\Models\News;
use App\Models\Notice;
use App\Models\Product;
use App\Models\ProductRanking;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * TOPページの7セクション分のPropsを組み立てる。
 */
class TopPageService
{
    private const RANKING_LIMIT = 4;

    /** 全体タブを含むタブ数の上限 */
    private const RANKING_TAB_LIMIT = 4;

    private const RECOMMEND_LIMIT = 4;

    private const HISTORY_LIMIT = 6;

    private const NEWS_LIMIT = 4;

    /** 集計対象月の翌月1日のこの時刻からランキングを公開する */
    private const RANKING_PUBLISH_HOUR = 7;

    public function __construct(private readonly BuildProductCard $buildCard) {}

    /**
     * @return array<string, mixed>
     */
    public function build(?User $user): array
    {
        $ranking = $this->ranking();

        return [
            'notice' => $this->notice(),
            'banners' => $this->banners(),
            'categoryEntries' => $this->categoryEntries(),
            'rankingTabs' => $ranking['tabs'],
            'rankings' => $ranking['items'],
            'rankingUpdatedAt' => $ranking['updated_at'],
            'rankingUpdatedAtIso' => $ranking['updated_at_iso'],
            'recommends' => $this->recommends(),
            'histories' => $user ? $this->histories($user) : [],
            'news' => $this->news(),
        ];
    }

    /**
     * @return array{id: int, title: string}|null
     */
    private function notice(): ?array
    {
        $notice = Notice::query()
            ->displayable()
            ->orderByDesc('display_start_on')
            ->orderByDesc('id')
            ->first();

        return $notice ? ['id' => $notice->id, 'title' => $notice->title] : null;
    }

    /**
     * @return array<int, array{id: int, tag: string, title: string, subtitle: string|null, background: string, link_url: string|null}>
     */
    private function banners(): array
    {
        return Banner::query()
            ->active()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (Banner $banner): array => [
                'id' => $banner->id,
                'tag' => $banner->tag,
                'title' => $banner->title,
                'subtitle' => $banner->subtitle,
                'background' => $banner->background,
                'link_url' => $banner->link_url,
            ])
            ->all();
    }

    /**
     * @return array<int, array{id: int, name: string, product_count: int}>
     */
    private function categoryEntries(): array
    {
        return Category::query()
            ->withCount(['products as product_count' => fn (Builder $query) => $query->where('is_published', true)])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (Category $category): array => [
                'id' => $category->id,
                'name' => $category->name,
                'product_count' => (int) $category->product_count,
            ])
            ->all();
    }

    /**
     * @return array{tabs: array<int, array{key: string, label: string, category_id: int|null}>, items: array<array-key, array<int, array<string, mixed>>>, updated_at: string|null, updated_at_iso: string|null}
     */
    private function ranking(): array
    {
        $yearMonth = $this->publishedYearMonth();

        if ($yearMonth === null) {
            return ['tabs' => [], 'items' => [], 'updated_at' => null, 'updated_at_iso' => null];
        }

        $rankings = ProductRanking::query()
            ->where('target_year_month', $yearMonth)
            ->orderBy('rank_position')
            ->orderBy('id')
            ->get();

        $products = $this->cardsOf($rankings->pluck('product_id')->all());

        /** @var array<array-key, array<int, array<string, mixed>>> $items */
        $items = [];

        foreach ($rankings as $ranking) {
            $card = $products[$ranking->product_id] ?? null;

            // 集計後に非公開・削除された商品は表示しない
            if ($card === null || count($items[$this->tabKey($ranking->category_id)] ?? []) >= self::RANKING_LIMIT) {
                continue;
            }

            $items[$this->tabKey($ranking->category_id)][] = [
                'rank_position' => $ranking->rank_position,
                ...$card,
            ];
        }

        $tabs = $this->rankingTabs($items);
        $aggregatedAt = $rankings->first()?->aggregated_at;

        return [
            'tabs' => $tabs,
            // タブ数の上限で切り捨てたカテゴリの商品は描画されないため送らない
            'items' => array_intersect_key($items, array_flip(array_column($tabs, 'key'))),
            'updated_at' => $aggregatedAt?->format('Y.m.d H:i'),
            // <time> の datetime 属性に入れるため、併記する表示テキストと壁時計時刻がずれるUTC表記にしない
            'updated_at_iso' => $aggregatedAt?->toIso8601String(),
        ];
    }

    /**
     * 公開開始日時（集計対象月の翌月1日7:00）を過ぎている最新の集計月を選ぶ。
     */
    private function publishedYearMonth(): ?string
    {
        $now = CarbonImmutable::now();

        $yearMonths = ProductRanking::query()
            ->select('target_year_month')
            ->distinct()
            ->orderByDesc('target_year_month')
            ->pluck('target_year_month');

        foreach ($yearMonths as $yearMonth) {
            if ($this->publishAt($yearMonth)->lessThanOrEqualTo($now)) {
                return $yearMonth;
            }
        }

        return null;
    }

    private function publishAt(string $yearMonth): CarbonImmutable
    {
        return CarbonImmutable::createFromFormat('Y-m-d H:i:s', $yearMonth.'-01 00:00:00')
            ->addMonthNoOverflow()
            ->setTime(self::RANKING_PUBLISH_HOUR, 0);
    }

    private function tabKey(?int $categoryId): string
    {
        return $categoryId === null ? 'all' : (string) $categoryId;
    }

    /**
     * @param  array<array-key, array<int, array<string, mixed>>>  $items
     * @return array<int, array{key: string, label: string, category_id: int|null}>
     */
    private function rankingTabs(array $items): array
    {
        if ($items === []) {
            return [];
        }

        $tabs = isset($items['all'])
            ? [['key' => 'all', 'label' => '全体ランキング', 'category_id' => null]]
            : [];

        // 数値文字列のキーはPHPの配列で整数に正規化されるため、両方の型を受ける
        $categoryIds = array_values(array_filter(
            array_keys($items),
            fn (int|string $key): bool => (string) $key !== 'all',
        ));

        $categories = Category::query()
            ->whereIn('id', $categoryIds)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        foreach ($categories as $category) {
            $tabs[] = [
                'key' => (string) $category->id,
                'label' => $category->name,
                'category_id' => $category->id,
            ];
        }

        return array_slice($tabs, 0, self::RANKING_TAB_LIMIT);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function recommends(): array
    {
        return $this->buildCard
            ->scope(Product::query()->where('is_published', true))
            ->orderByDesc('id')
            ->limit(self::RECOMMEND_LIMIT)
            ->get()
            ->map($this->buildCard)
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function histories(User $user): array
    {
        // 非公開商品の除外はSQL側で行う（取得後に落とすと表示件数が目減りするため）
        $productIds = $user->browsingHistories()
            ->whereHas('product', fn (Builder $query) => $query->where('is_published', true))
            ->orderByDesc('viewed_at')
            ->orderByDesc('id')
            ->limit(self::HISTORY_LIMIT)
            ->pluck('product_id');

        $cards = $this->cardsOf($productIds->all());

        // 閲覧の新しい順を保つ
        return $productIds
            ->map(fn (int $productId): ?array => $cards[$productId] ?? null)
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @param  array<int, int>  $productIds
     * @return array<int, array<string, mixed>> 商品IDをキーとするカード表示データ
     */
    private function cardsOf(array $productIds): array
    {
        if ($productIds === []) {
            return [];
        }

        /** @var Collection<int, Product> $products */
        $products = $this->buildCard
            ->scope(Product::query()->where('is_published', true))
            ->whereIn('id', $productIds)
            ->get();

        return $products
            ->mapWithKeys(fn (Product $product): array => [$product->id => ($this->buildCard)($product)])
            ->all();
    }

    /**
     * @return array<int, array{id: int, published_on: string, published_on_iso: string, category: string, category_tone: array{fg: string, bg: string}, title: string}>
     */
    private function news(): array
    {
        return News::query()
            ->published()
            ->orderByDesc('published_on')
            ->orderByDesc('id')
            ->limit(self::NEWS_LIMIT)
            ->get()
            ->map(function (News $news): array {
                [$foreground, $background] = $news->category->color();

                return [
                    'id' => $news->id,
                    'published_on' => $news->published_on->format('Y.m.d'),
                    'published_on_iso' => $news->published_on->toDateString(),
                    'category' => $news->category->value,
                    'category_tone' => ['fg' => $foreground, 'bg' => $background],
                    'title' => $news->title,
                ];
            })
            ->all();
    }
}
