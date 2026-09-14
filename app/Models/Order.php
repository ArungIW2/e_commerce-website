<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Order extends Model {
 public const STATUSES=['pending','processing','shipped','completed','cancelled']; public const PAYMENT_STATUSES=['unpaid','paid','failed','refunded'];
 protected $fillable=['user_id','order_number','status','subtotal','shipping_cost','shipping_method','shipping_courier','shipping_service','discount','coupon_code','total','payment_method','payment_status','payment_token','biteship_order_id','biteship_tracking_id','shipping_status','shipping_tracking_url','shipped_at','recipient_name','phone','address_line','city','state','postal_code','tracking_number','cancelled_at'];
 protected function casts():array{return ['subtotal'=>'decimal:2','shipping_cost'=>'decimal:2','discount'=>'decimal:2','total'=>'decimal:2','cancelled_at'=>'datetime','shipped_at'=>'datetime'];}
 public function user():BelongsTo{return $this->belongsTo(User::class);} public function items():HasMany{return $this->hasMany(OrderItem::class);} public function statusHistories():HasMany{return $this->hasMany(OrderStatusHistory::class);}
 public function recordStatusChange(string $status,string $source='system',?string $note=null,array $metadata=[]):void{$this->statusHistories()->create(['status'=>$status,'source'=>$source,'note'=>$note,'metadata'=>$metadata?:null]);}
}
