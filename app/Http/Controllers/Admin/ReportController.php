<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $from = $request->date('from')?->startOfDay() ?? now()->startOfMonth();
        $to = $request->date('to')?->endOfDay() ?? now()->endOfDay();

        $orders = Order::query()->whereBetween('created_at', [$from, $to]);
        $completed = (clone $orders)->where('status', 'completed');

        $sales = (clone $completed)->sum('total');
        $orderCount = (clone $orders)->count();
        $completedCount = (clone $completed)->count();
        $averageOrder = $completedCount > 0 ? $sales / $completedCount : 0;

        $daily = (clone $orders)
            ->selectRaw('DATE(created_at) as day, COUNT(*) as orders, SUM(total) as revenue')
            ->groupByRaw('DATE(created_at)')
            ->orderBy('day')
            ->get();

        $topProducts = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereBetween('orders.created_at', [$from, $to])
            ->where('orders.status', 'completed')
            ->selectRaw('order_items.product_name, SUM(order_items.quantity) as quantity, SUM(order_items.line_total) as revenue')
            ->groupBy('order_items.product_name')
            ->orderByDesc('quantity')
            ->limit(10)
            ->get();

        $statusBreakdown = (clone $orders)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->orderByDesc('total')
            ->get();

        return view('admin.reports.index', compact(
            'from', 'to', 'sales', 'orderCount', 'completedCount', 'averageOrder',
            'daily', 'topProducts', 'statusBreakdown'
        ));
    }
}
