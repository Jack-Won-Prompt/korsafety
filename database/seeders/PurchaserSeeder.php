<?php

namespace Database\Seeders;

use App\Models\Buyer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Purchaser;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PurchaserSeeder extends Seeder
{
    public function run(): void
    {
        // 데모 구매 대행자 (승인완료 + 승인대기)
        $purchaser = Purchaser::updateOrCreate(['slug' => 'safebuy'], [
            'name' => '세이프바이', 'status' => 'approved', 'cashback_rate' => 5,
            'owner_name' => '오구매', 'business_no' => '128-81-33445',
            'phone' => '02-333-7700', 'email' => 'buyer@partner.kr',
        ]);
        Purchaser::updateOrCreate(['slug' => 'goodbuy'], [
            'name' => '굿바이구매대행', 'status' => 'pending', 'cashback_rate' => 5,
            'owner_name' => '문대리', 'business_no' => '211-39-55112',
            'phone' => '031-500-1234', 'email' => 'goodbuy@partner.kr',
        ]);

        User::updateOrCreate(['email' => 'buyer@partner.kr'], [
            'name' => '세이프바이', 'role' => 'purchaser', 'purchaser_id' => $purchaser->id,
            'password' => Hash::make('1234'),
        ]);

        // 구매자(소매처)
        $buyersData = [
            ['현대안전마트', '김소매'], ['대성산업자재', '이대성'], ['안전나라', '박안전'],
            ['work-shop 공구상', '최공구'], ['튼튼안전용품', '정튼튼'],
        ];
        $buyers = [];
        foreach ($buyersData as [$shop, $name]) {
            $buyers[] = Buyer::updateOrCreate(
                ['purchaser_id' => $purchaser->id, 'shop_name' => $shop],
                ['name' => $name, 'phone' => '010-'.random_int(2000, 9999).'-'.random_int(1000, 9999),
                 'business_no' => random_int(100, 999).'-'.random_int(10, 99).'-'.random_int(10000, 99999)]
            );
        }

        // 구매 대행자가 등록한 주문 (캐쉬백 연동)
        $products = Product::whereNotNull('main_image')->whereNotNull('price')
            ->where('price', '>', 0)->inRandomOrder()->limit(200)->get();
        if ($products->isEmpty()) return;

        $old = Order::where('purchaser_id', $purchaser->id)->pluck('id');
        OrderItem::whereIn('order_id', $old)->delete();
        Order::whereIn('id', $old)->delete();

        for ($i = 0; $i < 24; $i++) {
            $when = now()->subDays(random_int(0, 29))->subMinutes(random_int(0, 1440));
            $buyer = $buyers[array_rand($buyers)];
            $status = [true, true, true, false][array_rand([0, 1, 2, 3])] ? 'paid' : 'pending';

            $order = Order::create([
                'order_no' => 'PB'.$when->format('ymd').Str::upper(Str::random(4)),
                'purchaser_id' => $purchaser->id, 'buyer_id' => $buyer->id,
                'customer_name' => $buyer->shop_name.' / '.$buyer->name, 'customer_phone' => $buyer->phone,
                'total' => 0, 'status' => $status, 'cashback_rate' => $purchaser->cashback_rate,
                'created_at' => $when, 'updated_at' => $when,
            ]);

            $total = 0;
            foreach ($products->random(random_int(1, 4)) as $p) {
                $price = $p->final_price ?: $p->price;
                $qty = random_int(2, 15);
                $line = $price * $qty;
                $total += $line;
                OrderItem::create([
                    'order_id' => $order->id, 'product_id' => $p->id, 'seller_id' => $p->seller_id,
                    'product_name' => $p->name, 'price' => $price, 'qty' => $qty, 'line_total' => $line,
                    'created_at' => $when, 'updated_at' => $when,
                ]);
            }
            $cashback = (int) round($total * $purchaser->cashback_rate / 100);
            $paidAt = ($status === 'paid' && random_int(0, 2) === 0) ? $when->copy()->addDays(3) : null;
            $order->update(['total' => $total, 'cashback_amount' => $cashback, 'cashback_paid_at' => $paidAt]);
        }

        $this->command->info('Purchaser seeded: purchasers='.Purchaser::count()
            .', buyers='.Buyer::where('purchaser_id', $purchaser->id)->count()
            .', purchaser_orders='.Order::where('purchaser_id', $purchaser->id)->count());
    }
}
