<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;

/** 검색엔진 수집용 사이트맵 — 노출 중인 카테고리·상품만 담는다 */
class SitemapController extends Controller
{
    public function index()
    {
        $urls = [];

        // 주요 고정 페이지
        foreach ([['/', '1.0', 'daily'], ['/about', '0.6', 'monthly'],
                  ['/terms', '0.2', 'yearly'], ['/privacy', '0.2', 'yearly']] as [$path, $priority, $freq]) {
            $urls[] = ['loc' => url($path), 'priority' => $priority, 'changefreq' => $freq];
        }

        // 노출 카테고리 (대·중·소 전부)
        foreach (Category::active()->orderBy('sort')->orderBy('name')->get() as $cat) {
            $urls[] = [
                'loc' => route('category.show', $cat),
                'lastmod' => optional($cat->updated_at)->toAtomString(),
                'priority' => '0.8',
                'changefreq' => 'weekly',
            ];
        }

        // 노출 상품 (이미지가 있는 것만 — 검색결과 품질)
        Product::visible()->whereNotNull('main_image')
            ->orderByDesc('id')
            ->chunk(500, function ($chunk) use (&$urls) {
                foreach ($chunk as $p) {
                    $urls[] = [
                        'loc' => route('product.show', $p),
                        'lastmod' => optional($p->updated_at)->toAtomString(),
                        'priority' => '0.6',
                        'changefreq' => 'weekly',
                        'image' => $p->main_image ? asset($p->main_image) : null,
                        'title' => $p->name,
                    ];
                }
            });

        return response()
            ->view('sitemap', ['urls' => $urls])
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }
}
