<?php
namespace App\Http\Controllers\Api;
use App\Http\Resources\OrderResource; use App\Models\Order; use Illuminate\Http\Request;
class OrderApiController extends ApiController { public function index(Request $request){return $this->success(OrderResource::collection($request->user()->orders()->latest()->paginate(15)));} public function show(Request $request,Order $order){if($order->user_id!==$request->user()->id)return $this->error('Order not found.',404);$order->load('items');return $this->success(new OrderResource($order));} }
