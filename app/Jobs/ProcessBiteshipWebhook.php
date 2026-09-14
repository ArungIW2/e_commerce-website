<?php
namespace App\Jobs;
use App\Models\BiteshipWebhookEvent;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;
class ProcessBiteshipWebhook implements ShouldQueue {
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public int $tries=3;
    public array $backoff=[10,30,90];
    public function __construct(public int $eventId){$this->onQueue('webhooks');}
    public function handle():void {
        $event=BiteshipWebhookEvent::find($this->eventId); if(!$event||$event->processed_at)return;
        try { DB::transaction(function()use($event){
            $event->refresh(); if($event->processed_at)return;
            $payload=$event->payload; $order=Order::where('biteship_order_id',$event->biteship_order_id)->lockForUpdate()->first();
            if(!$order){$event->forceFill(['processed_at'=>now(),'processing_error'=>'Order not found; event acknowledged.'])->save();return;}
            $status=$payload['status']??null;
            $normalizedStatus=is_string($status)?strtolower(preg_replace('/(?<!^)[A-Z]/','_$0',$status)):null;
            $waybill=$payload['courier_waybill_id']??data_get($payload,'courier.waybill_id');
            $trackingId=$payload['tracking_id']??data_get($payload,'courier.tracking_id');
            $trackingUrl=$payload['courier_link']??data_get($payload,'courier.link');
            $updates=array_filter(['shipping_status'=>$status,'tracking_number'=>$waybill,'biteship_tracking_id'=>$trackingId,'shipping_tracking_url'=>$trackingUrl],static fn($value)=>$value!==null&&$value!=='');
            if($normalizedStatus&&in_array($normalizedStatus,['picked','in_transit','dropping_off','delivered'],true))$updates['shipped_at']=$order->shipped_at??now();
            if($normalizedStatus==='delivered'&&$order->status!=='cancelled')$updates['status']='completed';
            elseif($normalizedStatus&&in_array($normalizedStatus,['picked','in_transit','dropping_off'],true)&&$order->status!=='completed')$updates['status']='shipped';
            elseif($normalizedStatus==='cancelled'&&$order->status!=='completed'){$updates['status']='cancelled';$updates['cancelled_at']=$order->cancelled_at??now();}
            $previousStatus=$order->status; $order->update($updates);
            if(isset($updates['status'])&&$updates['status']!==$previousStatus)$order->recordStatusChange($updates['status'],'biteship','Shipping webhook status update.');
            $event->forceFill(['processed_at'=>now(),'processing_error'=>null])->save();
        }); } catch(Throwable $e){Log::error('Biteship webhook processing failed.',['event_id'=>$this->eventId,'exception'=>$e]);throw $e;}
    }
}
