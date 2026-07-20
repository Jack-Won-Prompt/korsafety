<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Seller;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class YkSeeder extends Seeder
{
    private const OFFSET = 9000000; // yktst product_no 충돌 방지용 external_no 오프셋

    public function run(): void
    {
        $file = database_path('seeders/data/yk_products.json');
        if (! file_exists($file)) {
            $this->command->warn('yk_products.json 없음 — 스크래퍼(node scripts/yk-scrape.mjs)를 먼저 실행하세요.');
            return;
        }
        $products = json_decode(file_get_contents($file), true) ?? [];

        // 유한킴벌리 카테고리 (없으면 생성, 맨 뒤 정렬)
        $cat = Category::firstOrCreate(
            ['slug' => 'yuhankimberly'],
            ['name' => '유한킴벌리', 'sort' => (int) Category::max('sort') + 1]
        );

        $hq = Seller::where('is_hq', true)->first();

        // 기존 유한킴벌리 상품 정리 (재실행 대비)
        $oldIds = Product::where('external_no', '>=', self::OFFSET)->pluck('id');
        DB::table('product_category')->whereIn('product_id', $oldIds)->delete();
        ProductImage::whereIn('product_id', $oldIds)->delete();
        Product::whereIn('id', $oldIds)->delete();

        $count = 0; $imgCount = 0;
        foreach ($products as $p) {
            $slug = Str::slug($p['name']);
            if ($slug === '') $slug = 'yk'.$p['no'];

            $product = Product::create([
                'external_no' => self::OFFSET + $p['no'],
                'seller_id' => $hq?->id,
                'category_id' => $cat->id,
                'name' => mb_substr($p['name'], 0, 250),
                'slug' => Str::limit($slug, 120, ''),
                'brand' => '유한킴벌리',
                'price' => $p['price'] ?: null,
                'sale_price' => null,
                'is_soldout' => false,
                'main_image' => $p['images']['main'] ?? null,
            ]);
            $product->categories()->syncWithoutDetaching([$cat->id]);

            $sort = 0;
            foreach (($p['images']['gallery'] ?? []) as $g) {
                ProductImage::create(['product_id' => $product->id, 'path' => $g, 'type' => 'gallery', 'sort' => $sort++]);
                $imgCount++;
            }
            $count++;
        }

        $this->command->info("유한킴벌리 시드 완료: 카테고리 1, 상품 {$count}, 갤러리이미지 {$imgCount}");
    }
}
