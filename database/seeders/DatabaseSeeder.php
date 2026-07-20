<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 순서 중요: 상품 → 마켓플레이스(계정·판매점) → 협력사 → 구매대행
        $this->call([
            CatalogSeeder::class,      // 상품·카테고리
            MarketplaceSeeder::class,  // 본사 직영·판매점 + 본사/판매점 계정 + 샘플주문
            AgentSeeder::class,        // 협력사 + 거래처 + 커미션 주문
            PurchaserSeeder::class,    // 구매대행자 + 구매자 + 캐쉬백 주문
        ]);
    }
}
