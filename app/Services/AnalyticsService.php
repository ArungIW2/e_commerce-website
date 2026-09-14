<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Collection;

class AnalyticsService
{
    public function dailySales($from, $to): Collection
    {
        return Order::query()->whereBetween('created_at', [$from, $to])->where('status', 'completed')
            ->selectRaw('DATE(created_at) as day, COUNT(*) as orders, SUM(total) as revenue')
            ->groupByRaw('DATE(created_at)')->orderBy('day')->get();
    }
}
