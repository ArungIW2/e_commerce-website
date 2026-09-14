<?php
namespace App\Http\Controllers;
use App\Jobs\ProcessBiteshipWebhook;
use App\Models\BiteshipWebhookEvent;
use App\Models\Order;
use App\Services\BiteshipShippingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Throwable;
class ShippingOrderController extends Controller {
 public function create(Request $request,Order $order,BiteshipShippingService $shipping):RedirectResponse{abort_unless($request->user()->role==='admin',403);if($order->payment_status!=='paid'&&$order->payment_method!=='cod')return back()->withErrors(['shipping'=>'Pembayaran pesanan harus sudah lunas sebelum pengiriman dibuat.']);if($order->biteship_order_id)return back()->with('status','Pengiriman Biteship sudah dibuat untuk pesanan ini.');try{$shipping->syncOrderResponse($order,$shipping->createOrder($order));}catch(Throwable $e){Log::error('Biteship order creation failed.',['order_id'=>$order->id,'exception'=>$e]);return back()->withErrors(['shipping'=>'Pengiriman Biteship gagal dibuat. Silakan coba lagi.']);}return back()->with('status','Pengiriman Biteship berhasil dibuat.');}
 public function webhook(Request $request):JsonResponse{$signatureKey=config('services.biteship.webhook_signature_key');$signatureSecret=config('services.biteship.webhook_signature_secret');if(!$signatureKey||!$signatureSecret){Log::critical('Biteship webhook rejected because signature configuration is missing.');return response()->json(['message'=>'Webhook authentication is not configured.'],503);} $provided=(string)$request->header($signatureKey);if(!$provided||!hash_equals($signatureSecret,$provided))return response()->json(['message'=>'Invalid webhook signature.'],401);$payload=$request->all();$eventType=data_get($payload,'event')??data_get($payload,'event_type')??$request->header('X-Biteship-Event');$biteshipOrderId=data_get($payload,'order_id')??data_get($payload,'id');$explicitEventId=$request->header('X-Biteship-Event-Id')?:data_get($payload,'event_id');$eventId=(string)($explicitEventId?:hash('sha256',$request->getContent()));try{$now=now();$inserted=DB::table('biteship_webhook_events')->insertOrIgnore(['event_id'=>$eventId,'event_type'=>is_string($eventType)?$eventType:null,'biteship_order_id'=>is_string($biteshipOrderId)?$biteshipOrderId:null,'payload'=>json_encode($payload,JSON_THROW_ON_ERROR),'created_at'=>$now,'updated_at'=>$now]);$event=BiteshipWebhookEvent::where('event_id',$eventId)->firstOrFail();}catch(Throwable $e){Log::error('Biteship webhook event persistence failed.',['event_id'=>$eventId,'exception'=>$e]);return response()->json(['message'=>'Webhook could not be accepted.'],503);}if($event->processed_at)return response()->json(['status'=>'already_processed']);if($inserted===0)return response()->json(['status'=>'already_queued']);Queue::push(new ProcessBiteshipWebhook($event->id));return response()->json(['status'=>'accepted']);}
}
