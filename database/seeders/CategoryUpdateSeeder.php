<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategoryUpdateSeeder extends Seeder
{
    /** slug => [새 이름, 정렬] — 최종 9개 카테고리 */
    private const MAP = [
        'safety-shoes'  => ['안전화', 0],
        'safety-gear'   => ['개인보호구', 1],
        'harness'       => ['고소작업안전용품', 2],
        'road-safety'   => ['도로안전용품', 3],
        'facilities'    => ['안전시설용품', 4],
        'workwear'      => ['작업복·보호복', 5],
        'fire-rescue'   => ['소방,비상구호용품', 6],
        'yuhankimberly' => ['유한킴벌리', 7],
        'seasonal'      => ['시즌상품', 8],
    ];

    public function run(): void
    {
        // clean-safe(클린&세이프) → 작업복·보호복(workwear)로 병합
        $clean = Category::where('slug', 'clean-safe')->first();
        $workwear = Category::where('slug', 'workwear')->first();
        if ($clean && $workwear) {
            DB::statement('INSERT IGNORE INTO product_category (product_id, category_id) SELECT product_id, ? FROM product_category WHERE category_id = ?', [$workwear->id, $clean->id]);
            DB::table('product_category')->where('category_id', $clean->id)->delete();
            DB::table('products')->where('category_id', $clean->id)->update(['category_id' => $workwear->id]);
            $clean->delete();
        }

        // 명칭·순서 갱신
        foreach (self::MAP as $slug => [$name, $sort]) {
            Category::where('slug', $slug)->update(['name' => $name, 'sort' => $sort]);
        }

        $this->command->info('카테고리 갱신 완료: '.Category::count().'개 (clean-safe 병합)');
    }
}
