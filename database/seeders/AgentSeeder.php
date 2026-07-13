<?php

namespace Database\Seeders;

use App\Models\Agent;
use App\Models\Client;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AgentSeeder extends Seeder
{
    public function run(): void
    {
        // 데모 협력사 (승인완료 + 승인대기)
        $agent = Agent::updateOrCreate(['slug' => 'medipartner'], [
            'name' => '메디파트너', 'status' => 'approved', 'commission_rate' => 10,
            'owner_name' => '정영업', 'business_no' => '220-81-55667',
            'phone' => '02-555-1200', 'email' => 'agent@partner.kr',
        ]);
        Agent::updateOrCreate(['slug' => 'caresales'], [
            'name' => '케어세일즈', 'status' => 'pending', 'commission_rate' => 10,
            'owner_name' => '한지원', 'business_no' => '133-45-77889',
            'phone' => '031-700-3000', 'email' => 'caresales@partner.kr',
        ]);

        User::updateOrCreate(['email' => 'agent@partner.kr'], [
            'name' => '메디파트너', 'role' => 'agent', 'agent_id' => $agent->id,
            'password' => Hash::make('agent123'),
        ]);

        // 거래처 (기업/병원)
        $clientsData = [
            ['서울아산병원', 'hospital', '김간호'], ['현대건설 안전팀', 'company', '이현장'],
            ['강남세브란스병원', 'hospital', '박원무'], ['삼성물산 건설부문', 'company', '최소장'],
            ['분당서울대병원', 'hospital', '정수간'], ['GS건설', 'company', '한부장'],
        ];
        $clients = [];
        foreach ($clientsData as [$n, $t, $c]) {
            $clients[] = Client::updateOrCreate(
                ['agent_id' => $agent->id, 'name' => $n],
                ['type' => $t, 'contact_name' => $c, 'phone' => '02-'.random_int(200, 999).'-'.random_int(1000, 9999),
                 'business_no' => random_int(100, 999).'-'.random_int(10, 99).'-'.random_int(10000, 99999)]
            );
        }

        // 협력사가 등록한 주문 (커미션 연동)
        $products = Product::whereNotNull('main_image')->whereNotNull('price')
            ->where('price', '>', 0)->inRandomOrder()->limit(200)->get();
        if ($products->isEmpty()) return;

        // 기존 데모 협력사 주문 정리 후 재생성
        $old = Order::where('agent_id', $agent->id)->pluck('id');
        OrderItem::whereIn('order_id', $old)->delete();
        Order::whereIn('id', $old)->delete();

        for ($i = 0; $i < 26; $i++) {
            $when = now()->subDays(random_int(0, 29))->subMinutes(random_int(0, 1440));
            $client = $clients[array_rand($clients)];
            // 대부분 결제완료(적립), 일부 접수(적립대기)
            $status = [true, true, true, false][array_rand([0, 1, 2, 3])] ? 'paid' : 'pending';

            $order = Order::create([
                'order_no' => 'AG'.$when->format('ymd').Str::upper(Str::random(4)),
                'agent_id' => $agent->id, 'client_id' => $client->id,
                'customer_name' => $client->name, 'customer_phone' => $client->phone,
                'total' => 0, 'status' => $status, 'commission_rate' => $agent->commission_rate,
                'created_at' => $when, 'updated_at' => $when,
            ]);

            $total = 0;
            foreach ($products->random(random_int(1, 4)) as $p) {
                $price = $p->final_price ?: $p->price;
                $qty = random_int(2, 20); // B2B 대량
                $line = $price * $qty;
                $total += $line;
                OrderItem::create([
                    'order_id' => $order->id, 'product_id' => $p->id, 'seller_id' => $p->seller_id,
                    'product_name' => $p->name, 'price' => $price, 'qty' => $qty, 'line_total' => $line,
                    'created_at' => $when, 'updated_at' => $when,
                ]);
            }
            $commission = (int) round($total * $agent->commission_rate / 100);
            // 결제완료 주문 중 일부는 지급완료 처리
            $paidAt = ($status === 'paid' && random_int(0, 2) === 0) ? $when->copy()->addDays(3) : null;
            $order->update(['total' => $total, 'commission_amount' => $commission, 'commission_paid_at' => $paidAt]);
        }

        $this->command->info('Agent seeded: agents='.Agent::count()
            .', clients='.Client::where('agent_id', $agent->id)->count()
            .', agent_orders='.Order::where('agent_id', $agent->id)->count());
    }
}
