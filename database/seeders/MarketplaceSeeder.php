<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Seller;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class MarketplaceSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('order_items')->truncate();
        DB::table('orders')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // 1) 본사 직영 스토어
        $hq = Seller::updateOrCreate(['slug' => 'hq'], [
            'name' => '본사 직영', 'is_hq' => true, 'status' => 'approved',
            'owner_name' => '임현규', 'business_no' => '101-86-83744',
            'phone' => '1588-0000', 'email' => 'hq@korsafety.kr', 'commission_rate' => 0,
        ]);

        // 2) 기존 상품 전부 본사 직영 소유로
        Product::whereNull('seller_id')->update(['seller_id' => $hq->id]);

        // 3) 데모 입점 판매점 (승인완료 2곳 + 승인대기 1곳)
        $delta = Seller::updateOrCreate(['slug' => 'delta'], [
            'name' => '델타 세이프티', 'status' => 'approved', 'owner_name' => '김철수',
            'business_no' => '214-88-11223', 'phone' => '02-1234-5678',
            'email' => 'delta@partner.kr', 'commission_rate' => 12,
        ]);
        $workpro = Seller::updateOrCreate(['slug' => 'workpro'], [
            'name' => '워크프로', 'status' => 'approved', 'owner_name' => '이영희',
            'business_no' => '312-77-99001', 'phone' => '031-987-6543',
            'email' => 'workpro@partner.kr', 'commission_rate' => 10,
        ]);
        Seller::updateOrCreate(['slug' => 'newsafety'], [
            'name' => '뉴세이프티', 'status' => 'pending', 'owner_name' => '박민준',
            'business_no' => '556-33-44557', 'phone' => '051-222-3333',
            'email' => 'newsafety@partner.kr', 'commission_rate' => 10,
        ]);

        // 데모용으로 일부 상품을 판매점 소유로 재배정
        $ww = Category::where('slug', 'workwear')->first();
        $ss = Category::where('slug', 'safety-shoes')->first();
        if ($ww) {
            Product::where('category_id', $ww->id)->whereNotNull('main_image')
                ->inRandomOrder()->limit(45)->update(['seller_id' => $delta->id]);
        }
        if ($ss) {
            Product::where('category_id', $ss->id)->whereNotNull('main_image')
                ->inRandomOrder()->limit(45)->update(['seller_id' => $workpro->id]);
        }

        // 4) 계정
        User::updateOrCreate(['email' => 'admin@korsafety.kr'], [
            'name' => '본사 관리자', 'role' => 'hq_admin', 'seller_id' => $hq->id,
            'password' => Hash::make('korsafety2013'),
        ]);
        User::updateOrCreate(['email' => 'delta@partner.kr'], [
            'name' => '델타 세이프티', 'role' => 'seller', 'seller_id' => $delta->id,
            'password' => Hash::make('seller123'),
        ]);
        User::updateOrCreate(['email' => 'workpro@partner.kr'], [
            'name' => '워크프로', 'role' => 'seller', 'seller_id' => $workpro->id,
            'password' => Hash::make('seller123'),
        ]);

        // 5) 샘플 주문 (최근 30일) — 매출 대시보드용
        $products = Product::whereNotNull('main_image')->whereNotNull('price')
            ->inRandomOrder()->limit(300)->get();
        $names = ['김안전', '이현장', '박작업', '최공사', '정건설', '강산업', '조현대', '윤대우'];
        $orderCount = 80;
        for ($i = 0; $i < $orderCount; $i++) {
            $when = now()->subDays(random_int(0, 29))->subMinutes(random_int(0, 1440));
            $order = Order::create([
                'order_no' => 'KS'.$when->format('ymd').Str::upper(Str::random(4)),
                'customer_name' => $names[array_rand($names)],
                'customer_phone' => '010-'.random_int(1000, 9999).'-'.random_int(1000, 9999),
                'total' => 0,
                'status' => ['paid', 'shipped', 'done'][array_rand([0, 1, 2])],
                'created_at' => $when, 'updated_at' => $when,
            ]);
            $total = 0;
            $lineN = random_int(1, 3);
            $chosen = $products->random(min($lineN, $products->count()));
            foreach ($chosen as $p) {
                $price = $p->final_price ?: $p->price ?: 10000;
                $qty = random_int(1, 4);
                $line = $price * $qty;
                $total += $line;
                OrderItem::create([
                    'order_id' => $order->id, 'product_id' => $p->id, 'seller_id' => $p->seller_id,
                    'product_name' => $p->name, 'price' => $price, 'qty' => $qty, 'line_total' => $line,
                    'created_at' => $when, 'updated_at' => $when,
                ]);
            }
            $order->update(['total' => $total]);
        }

        $this->command->info('Marketplace seeded: sellers='.Seller::count()
            .', hq_products='.Product::where('seller_id', $hq->id)->count()
            .', orders='.Order::count());
    }
}
