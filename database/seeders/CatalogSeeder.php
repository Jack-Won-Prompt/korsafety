<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $dir = database_path('seeders/data');
        $categories = json_decode(file_get_contents("$dir/categories.json"), true) ?? [];
        $products = json_decode(file_get_contents("$dir/products.json"), true) ?? [];
        $memberships = file_exists("$dir/memberships.json")
            ? json_decode(file_get_contents("$dir/memberships.json"), true) : [];

        // Reverse index: product_no -> [slug, ...] in category display order
        $prodCats = [];
        foreach ($categories as $c) {
            foreach (($memberships[$c['slug']] ?? []) as $no) {
                $prodCats[$no][] = $c['slug'];
            }
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('product_category')->truncate();
        DB::table('product_images')->truncate();
        DB::table('products')->truncate();
        DB::table('categories')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // Categories
        $catId = [];
        $now = now();
        foreach ($categories as $i => $c) {
            $id = DB::table('categories')->insertGetId([
                'slug' => $c['slug'], 'name' => $c['name'], 'sort' => $i,
                'created_at' => $now, 'updated_at' => $now,
            ]);
            $catId[$c['slug']] = $id;
        }

        // Products (batched)
        $rows = [];
        foreach ($products as $p) {
            $slug = Str::slug($p['name']);
            if ($slug === '') $slug = 'p'.$p['no'];
            // Primary category: first membership in display order, else scraped fallback.
            $primary = $prodCats[$p['no']][0] ?? $p['category'];
            $rows[] = [
                'external_no' => $p['no'],
                'category_id' => $catId[$primary] ?? null,
                'name' => mb_substr($p['name'], 0, 250),
                'slug' => Str::limit($slug, 120, ''),
                'brand' => $p['brand'] ? mb_substr($p['brand'], 0, 120) : null,
                'price' => $p['price'] ?: null,
                'sale_price' => $p['sale'] ?: null,
                'is_soldout' => false, // source flag unreliable (template text on every page)
                'main_image' => $p['images']['main'] ?? null,
                'created_at' => $now, 'updated_at' => $now,
            ];
        }
        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('products')->insert($chunk);
        }

        // Map external_no -> id
        $idMap = DB::table('products')->pluck('id', 'external_no');

        // Images (batched)
        $imgRows = [];
        foreach ($products as $p) {
            $pid = $idMap[$p['no']] ?? null;
            if (! $pid) continue;
            $sort = 0;
            foreach (($p['images']['gallery'] ?? []) as $g) {
                $imgRows[] = ['product_id' => $pid, 'path' => $g, 'type' => 'gallery', 'sort' => $sort++];
            }
            $sort = 0;
            foreach (($p['images']['detail'] ?? []) as $d) {
                $imgRows[] = ['product_id' => $pid, 'path' => $d, 'type' => 'detail', 'sort' => $sort++];
            }
        }
        foreach (array_chunk($imgRows, 1000) as $chunk) {
            DB::table('product_images')->insert($chunk);
        }

        // Pivot: product <-> category (many-to-many)
        $pivot = [];
        foreach ($products as $p) {
            $pid = $idMap[$p['no']] ?? null;
            if (! $pid) continue;
            $slugs = $prodCats[$p['no']] ?? [$p['category']];
            foreach (array_unique($slugs) as $slug) {
                if (isset($catId[$slug])) {
                    $pivot[] = ['product_id' => $pid, 'category_id' => $catId[$slug]];
                }
            }
        }
        foreach (array_chunk($pivot, 1000) as $chunk) {
            DB::table('product_category')->insert($chunk);
        }

        $this->command->info('Seeded '.count($categories).' categories, '.count($rows).' products, '.count($imgRows).' images, '.count($pivot).' category links.');
    }
}
