<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Models\Cart;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    private const SHIPPING_METHODS=['standard'=>['label'=>'Standard (2–5 hari)','cost'=>15000],'express'=>['label'=>'Express (1–2 hari)','cost'=>30000],'pickup'=>['label'=>'Ambil di toko','cost'=>0]];
    private const PAYMENT_METHODS=['midtrans'=>'Midtrans (QRIS, e-wallet, VA, kartu)','cod'=>'Cash on Delivery (COD)','manual'=>'Transfer bank / pembayaran manual'];

    public function show(Request $request): View|RedirectResponse {
        $cart=Cart::where('user_id',Auth::id())->with('items.product','items.variant.attributeValues.attribute')->first();
        $items=$cart?->items->filter(fn($i)=>$i->product?->is_active&&($i->variant?->stock??$i->product->stock)>0)->values()??collect();
        if($items->isEmpty())return redirect()->route('cart')->withErrors(['cart'=>'Keranjang masih kosong.']);
        $items->each(fn($i)=>$i->quantity=min($i->quantity,$i->variant?->stock??$i->product->stock));
        $subtotal=$items->sum(fn($i)=>(float)($i->variant?->price??$i->product->price)*$i->quantity);
        $addresses=Auth::user()->addresses()->latest('is_default')->latest()->get();$shippingMethods=self::SHIPPING_METHODS;$paymentMethods=self::PAYMENT_METHODS;
        return view('store.checkout',compact('items','subtotal','addresses','shippingMethods','paymentMethods'));
    }

    public function store(Request $request): RedirectResponse {
        $validated=$request->validate(['address_id'=>['nullable','integer','exists:addresses,id'],'label'=>['nullable','string','max:50'],'recipient_name'=>['nullable','string','max:255'],'phone'=>['nullable','string','max:30'],'address_line'=>['nullable','string'],'city'=>['nullable','string','max:255'],'state'=>['nullable','string','max:255'],'postal_code'=>['nullable','string','max:10'],'shipping_method'=>['required','in:'.implode(',',array_keys(self::SHIPPING_METHODS))],'payment_method'=>['required','in:'.implode(',',array_keys(self::PAYMENT_METHODS))],'coupon_code'=>['nullable','string','max:50']]);
        $order=DB::transaction(function()use($request,$validated){
            $user=$request->user();$address=!empty($validated['address_id'])?$user->addresses()->findOrFail($validated['address_id']):null;
            if(!$address){$data=collect($validated)->only(['label','recipient_name','phone','address_line','city','state','postal_code'])->all();foreach(['recipient_name','phone','address_line','city','postal_code']as$f)if(empty($data[$f]))abort(422,'Data alamat belum lengkap.');$data['label']=$data['label']??'Rumah';$data['user_id']=$user->id;$data['is_default']=!$user->addresses()->exists();if($data['is_default'])$user->addresses()->update(['is_default'=>false]);$address=Address::create($data);}
            $cart=Cart::where('user_id',$user->id)->with('items')->firstOrFail();if($cart->items->isEmpty())abort(422,'Keranjang kosong.');$orderLines=[];$subtotal=0;
            foreach($cart->items as$cartItem){$product=Product::whereKey($cartItem->product_id)->lockForUpdate()->first();$variant=$cartItem->product_variant_id?ProductVariant::whereKey($cartItem->product_variant_id)->lockForUpdate()->first():null;if(!$product||!$product->is_active||($cartItem->product_variant_id&&(!$variant||$variant->product_id!==$product->id||!$variant->is_active))||($variant?$variant->stock:$product->stock)<$cartItem->quantity)abort(422,'Stok produk/varian tidak mencukupi.');$price=(float)($variant?->price??$product->price);$lineTotal=$price*$cartItem->quantity;$subtotal+=$lineTotal;$orderLines[]=compact('product','variant','cartItem','lineTotal');}
            $coupon=null;$discount=0;if(!empty($validated['coupon_code'])){$coupon=Coupon::where('code',Str::upper(trim($validated['coupon_code'])))->lockForUpdate()->first();if(!$coupon||!$coupon->isValidFor((float)$subtotal))abort(422,'Kupon tidak valid, sudah habis, atau tidak memenuhi syarat.');$discount=$coupon->calculateDiscount((float)$subtotal);}
            $shipping=self::SHIPPING_METHODS[$validated['shipping_method']]['cost'];$total=max(0,$subtotal+$shipping-$discount);
            $order=Order::create(['user_id'=>$user->id,'order_number'=>'TMP-'.Str::upper(Str::random(16)),'status'=>'pending','subtotal'=>$subtotal,'shipping_cost'=>$shipping,'shipping_method'=>$validated['shipping_method'],'discount'=>$discount,'coupon_code'=>$coupon?->code,'total'=>$total,'payment_method'=>$validated['payment_method'],'payment_status'=>'unpaid','recipient_name'=>$address->recipient_name,'phone'=>$address->phone,'address_line'=>$address->address_line,'city'=>$address->city,'state'=>$address->state,'postal_code'=>$address->postal_code]);
            $order->update(['order_number'=>'ORD-'.now()->format('Ymd').'-'.str_pad((string)$order->id,6,'0','0')]);
            foreach($orderLines as$line){$p=$line['product'];$v=$line['variant'];$q=$line['cartItem']->quantity;$order->items()->create(['product_id'=>$p->id,'product_variant_id'=>$v?->id,'product_name'=>$p->name,'sku'=>$v?->sku??$p->sku,'variant_name'=>$v?->displayName(),'price'=>$v?->price??$p->price,'quantity'=>$q,'line_total'=>$line['lineTotal']]);if($v)$v->decrement('stock',$q);else$p->decrement('stock',$q);}
            if($coupon)$coupon->increment('used_count');$cart->items()->delete();return$order;
        });
        if($order->payment_method==='midtrans') return redirect()->route('payments.show',$order)->with('status','Pesanan dibuat. Lanjutkan pembayaran melalui Midtrans.');
        return redirect()->route('orders.show',$order)->with('status','Pesanan berhasil dibuat.');
    }
}
