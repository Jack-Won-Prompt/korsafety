<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = Auth::user();
        if (! $user) {
            return redirect()->route('manage.login');
        }
        if (! in_array($user->role, $roles, true)) {
            abort(403, '접근 권한이 없습니다.');
        }
        // 판매점 / 협력사 계정은 승인 상태여야 함
        if ($user->role === 'seller') {
            $s = $user->seller;
            if (! $s || $s->status !== 'approved') {
                return $this->blocked($request, '판매점 승인이 완료되지 않았거나 정지된 계정입니다.');
            }
        }
        if ($user->role === 'agent') {
            $a = $user->agent;
            if (! $a || $a->status !== 'approved') {
                return $this->blocked($request, '협력사 승인이 완료되지 않았거나 정지된 계정입니다.');
            }
        }
        if ($user->role === 'purchaser') {
            $p = $user->purchaser;
            if (! $p || $p->status !== 'approved') {
                return $this->blocked($request, '구매 대행자 승인이 완료되지 않았거나 정지된 계정입니다.');
            }
        }
        return $next($request);
    }

    private function blocked(Request $request, string $msg): Response
    {
        Auth::logout();
        $request->session()->invalidate();
        return redirect()->route('manage.login')->withErrors(['email' => $msg]);
    }
}
