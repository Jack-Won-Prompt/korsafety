@extends('manage.layout')
@section('title', '회원 상세')
@section('page', $member->name.' 회원')
@section('crumb', '가입 정보 · 주문 이력 · 로그인 이력')
@section('actions')
    <a href="{{ route('admin.members') }}" class="btn btn-sm">← 회원 목록</a>
@endsection

@push('styles')
<style>
    .mb-dl{display:grid;grid-template-columns:120px 1fr;gap:10px 14px;font-size:13.5px;align-items:center}
    .mb-dl dt{color:#8a90a0;font-weight:700;margin:0}
    .mb-dl dd{margin:0;word-break:break-all}
</style>
@endpush

@section('content')
{{-- 가입 정보 --}}
<div class="panel">
    <div class="panel-h">
        <div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap">
            @if($member->suspended_at)<span class="badge off" style="font-size:13px;padding:6px 14px">이용 정지</span>
            @else<span class="badge ok" style="font-size:13px;padding:6px 14px">이용중</span>@endif
            <div>
                <h2 style="margin:0">{{ $member->name }}</h2>
                <div class="sub">{{ $member->email }}</div>
            </div>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap">
            <form method="post" action="{{ route('admin.members.reset-link', $member) }}"
                  onsubmit="return confirm('{{ $member->email }} 로 비밀번호 재설정 링크를 보낼까요?')">@csrf
                <button class="btn btn-sm">비밀번호 재설정 링크 보내기</button>
            </form>
            <form method="post" action="{{ route('admin.members.suspend', $member) }}"
                  onsubmit="return confirm('{{ $member->suspended_at ? '이용 정지를 해제할까요?' : '이 회원을 이용 정지할까요? 로그인할 수 없게 됩니다.' }}')">@csrf
                <button class="btn btn-sm {{ $member->suspended_at ? '' : 'btn-danger' }}">{{ $member->suspended_at ? '정지 해제' : '이용 정지' }}</button>
            </form>
            @if($member->role === 'customer' && $summary['orders'] === 0)
                <form method="post" action="{{ route('admin.members.destroy', $member) }}"
                      onsubmit="return confirm('이 회원을 삭제할까요? 되돌릴 수 없습니다.')">@csrf @method('DELETE')
                    <button class="btn btn-sm btn-danger">회원 삭제</button>
                </form>
            @endif
        </div>
    </div>
    <div class="panel-b">
        <dl class="mb-dl">
            <dt>구분</dt><dd>{{ \App\Http\Controllers\Admin\MemberController::ROLES[$member->role] ?? ($member->role ?: '일반 회원') }}</dd>
            <dt>가입일</dt><dd>{{ optional($member->created_at)->format('Y.m.d H:i') }}</dd>
            <dt>가입 승인</dt>
            <dd>
                @if($member->approval_status === 'pending')
                    <span class="badge warn">승인 대기</span>
                @elseif($member->approval_status === 'approved')
                    <span class="badge ok">승인 완료</span>{{ $member->approved_at ? ' · '.$member->approved_at->format('Y.m.d H:i') : '' }}
                @elseif($member->approval_status === 'rejected')
                    <span class="badge off">반려</span>
                @else
                    <span class="t-sub">승인 절차 없이 가입</span>
                @endif
                @if($member->approval_status !== 'none')
                    <form method="post" action="{{ route('admin.members.approval', $member) }}" style="display:inline-flex;gap:6px;margin-left:8px">@csrf
                        <select class="input" name="approval_status" style="height:32px;width:120px;font-size:12.5px">
                            <option value="pending" @selected($member->approval_status === 'pending')>승인 대기</option>
                            <option value="approved" @selected($member->approval_status === 'approved')>승인 완료</option>
                            <option value="rejected" @selected($member->approval_status === 'rejected')>반려</option>
                        </select>
                        <input class="input" name="reason" placeholder="반려 사유 (반려일 때만)" style="height:32px;width:220px;font-size:12.5px">
                        <button class="btn btn-sm">저장</button>
                    </form>
                @endif
            </dd>
            <dt>휴대전화</dt><dd>{{ $member->phone ?: '-' }}</dd>
            <dt>주소</dt><dd>{{ trim(($member->postcode ? '('.$member->postcode.') ' : '').$member->address1.' '.$member->address2) ?: '-' }}</dd>
            @if($member->suspended_at)
                <dt>정지 일시</dt><dd>{{ $member->suspended_at->format('Y.m.d H:i') }}</dd>
            @endif
            <dt>주문</dt><dd><b>{{ number_format($summary['orders']) }}건</b> · {{ number_format($summary['amount']) }}원
                @if($summary['last_order_at']) · 최근 {{ \Illuminate\Support\Carbon::parse($summary['last_order_at'])->format('Y.m.d') }}@endif
            </dd>
            @if($member->seller)<dt>소속 판매점</dt><dd>{{ $member->seller->name }}</dd>@endif
        </dl>
    </div>
</div>

{{-- 협력사 회원 정보 --}}
@if($member->partnerProfile)
    @php $p = $member->partnerProfile; @endphp
    <div class="panel">
        <div class="panel-h">
            <div style="display:flex;gap:12px;align-items:center">
                <span class="badge {{ $p->status_badge }}" style="font-size:13px;padding:6px 14px">{{ $p->status_label }}</span>
                <div>
                    <h2 style="margin:0">협력사 정보</h2>
                    <div class="sub">
                        승인해야 협력사 할인가로 구매할 수 있습니다
                        @if($p->approved_at) · {{ $p->approved_at->format('Y.m.d H:i') }} {{ $p->approver->name ?? '' }} 승인@endif
                    </div>
                </div>
            </div>
            <a href="{{ route('admin.members.license', $p) }}" class="btn btn-sm">사업자등록증 보기</a>
        </div>
        <div class="panel-b">
            <dl class="mb-dl">
                <dt>회사명</dt><dd>{{ $p->company_name }}</dd>
                <dt>대표자</dt><dd>{{ $p->owner_name }}</dd>
                <dt>회사 전화</dt><dd>{{ $p->company_phone }}@if($p->company_fax) · 팩스 {{ $p->company_fax }}@endif</dd>
                <dt>사업장 주소</dt><dd>{{ $p->business_address }}</dd>
                <dt>가입자 연락처</dt><dd>{{ $member->phone ?: '-' }}</dd>
                @if($p->reject_reason)<dt>반려 사유</dt><dd>{{ $p->reject_reason }}</dd>@endif
            </dl>

            <form method="post" action="{{ route('admin.members.partner-status', $p) }}" style="margin-top:16px;display:flex;gap:8px;flex-wrap:wrap;align-items:center">@csrf
                <select class="input" name="status" style="height:38px;flex:0 0 150px">
                    @foreach(\App\Models\PartnerProfile::STATUSES as $k => $v)
                        <option value="{{ $k }}" @selected($p->status === $k)>{{ $v }}</option>
                    @endforeach
                </select>
                <input class="input" name="reject_reason" value="{{ $p->reject_reason }}" placeholder="반려 사유 (반려일 때만)" style="height:38px;flex:1 1 240px">
                <button class="btn btn-sm btn-accent">승인 상태 저장</button>
            </form>
        </div>
    </div>
@endif

{{-- 주문 이력 --}}
<div class="panel">
    <div class="panel-h"><div><h2>최근 주문</h2><div class="sub">최대 10건</div></div></div>
    <table class="table">
        <thead><tr><th style="width:150px">주문번호</th><th style="width:130px">주문일</th><th>받는 분</th><th style="width:120px">금액</th><th style="width:90px">상태</th></tr></thead>
        <tbody>
        @forelse($orders as $o)
            <tr>
                <td class="t-name">{{ $o->order_no }}</td>
                <td class="t-sub">{{ optional($o->created_at)->format('Y.m.d H:i') }}</td>
                <td class="t-sub">{{ $o->receiver_name ?: $o->customer_name }}</td>
                <td>{{ number_format((int) $o->total) }}원</td>
                <td class="t-sub">{{ $o->status }}</td>
            </tr>
        @empty
            <tr><td colspan="5" class="empty">주문 이력이 없습니다.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

{{-- 로그인 이력 --}}
<div class="panel">
    <div class="panel-h"><div><h2>최근 로그인</h2><div class="sub">최대 10건 · 실패 기록 포함</div></div></div>
    <table class="table">
        <thead><tr><th style="width:150px">일시</th><th style="width:80px">결과</th><th style="width:130px">IP</th><th>비고 / 접속 환경</th></tr></thead>
        <tbody>
        @forelse($logins as $l)
            <tr>
                <td class="t-sub">{{ optional($l->created_at)->format('Y.m.d H:i:s') }}</td>
                <td>@if($l->status === 'success')<span class="badge ok">성공</span>@else<span class="badge off">실패</span>@endif</td>
                <td class="t-sub">{{ $l->ip_address }}</td>
                <td class="t-sub">{{ $l->note }} @if($l->user_agent)<span style="font-size:11px">· {{ \Illuminate\Support\Str::limit($l->user_agent, 60) }}</span>@endif</td>
            </tr>
        @empty
            <tr><td colspan="4" class="empty">로그인 기록이 없습니다.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
