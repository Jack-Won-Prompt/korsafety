<?php

namespace App\Http\Controllers;

use App\Models\Product;

/**
 * 자동 처리 Agent 점검용 화면.
 *
 * 오류가 났을 때 Agent 가 원인을 찾아 고치고 배포까지 하는지 확인하려면
 * 운영에서 실제로 오류가 나야 한다. 고객이 쓰는 화면을 망가뜨릴 수는 없으므로
 * 아무 데서도 링크하지 않는 이 주소를 따로 둔다.
 *
 * 메뉴·사이트맵·RSS 어디에도 올리지 않는다. 점검이 끝나면 지운다.
 */
class AgentSelfTestController extends Controller
{
    public function __invoke()
    {
        $products = Product::query()
            ->where('is_active', true)
            ->latest('id')
            ->limit(5)
            ->get();

        if ($products->isEmpty()) {
            return response()->json(['상품수' => 0]);
        }

        $lengths = $products->map(fn (Product $p) => mb_strlen((string) $p->name));

        return response()->json([
            '상품수' => $products->count(),
            // Collection 에 없는 maxNameLength() 를 부르면 BadMethodCallException 이 나므로,
            // 바로 위에서 이미 구해 둔 $lengths 에서 최댓값을 가져온다.
            '가장긴이름' => $lengths->max(),
            '평균' => round($lengths->avg(), 1),
        ]);
    }
}
