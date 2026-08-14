<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Ranking\RankingAggregator;
use Illuminate\Console\Command;

class AggregateProductRankings extends Command
{
    protected $signature = 'rankings:aggregate';

    protected $description = '前月の販売実績から商品ランキングを集計する';

    public function handle(RankingAggregator $aggregator): int
    {
        $yearMonth = $aggregator->aggregate();

        $this->info($yearMonth.' のランキングを集計しました。');

        return self::SUCCESS;
    }
}
