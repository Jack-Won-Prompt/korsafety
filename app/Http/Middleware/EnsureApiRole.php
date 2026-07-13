<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApiRole
{
    /**
     * 토큰 사용자의 role 및 승인 상태를 검증한다 (JSON 응답).
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => '인증이 필요합니다.'], 401);
        }

        if ($roles && ! in_array($user->role, $roles, true)) {
            return response()->json(['message' => '접근 권한이 없습니다.'], 403);
        }

        if ($user->role === 'seller' && (! $user->seller || $user->seller->status !== 'approved')) {
            return response()->json(['message' => '판매점 승인이 완료되지 않았거나 정지된 계정입니다.'], 403);
        }

        if ($user->role === 'agent' && (! $user->agent || $user->agent->status !== 'approved')) {
            return response()->json(['message' => '협력사 승인이 완료되지 않았거나 정지된 계정입니다.'], 403);
        }

        return $next($request);
    }
}
