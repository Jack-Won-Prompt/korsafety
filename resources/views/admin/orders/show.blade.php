@extends('manage.layout')
@section('title', '주문 상세 · '.$order->order_no)
@section('page', '주문 상세')
@section('crumb', $order->order_no)
@section('actions')
    <a href="{{ route('admin.orders.index') }}" class="btn btn-sm">← 목록</a>
@endsection

@section('content')
@php $activeTi = $order->taxInvoices->firstWhere('is_active', true); @endphp

{{-- 주문 요약 --}}
<div class="panel">
    <div class="panel-h"><div><h2>주문 정보</h2><div class="sub">{{ $order->order_no }} · {{ optional($order->created_at)->format('Y-m-d H:i') }}</div></div>
        <div style="display:flex;gap:8px;align-items:center">
            <span class="badge {{ in_array($order->status,['paid','done'])?'ok':($order->status=='cancelled'?'off':'warn') }}">{{ $order->status_label }}</span>
            <form action="{{ route('admin.orders.status', $order) }}" method="post" style="display:flex;gap:6px;align-items:center">@csrf
                <select class="input" name="status" style="height:34px;width:112px;font-size:12.5px">
                    @foreach(\App\Models\Order::STATUSES as $k=>$v)
                        <option value="{{ $k }}" @selected($order->status===$k)>{{ $v }}</option>
                    @endforeach
                </select>
                <button class="btn btn-sm">상태 변경</button>
            </form>
        </div>
    </div>
    <div class="panel-b" style="display:grid;grid-template-columns:1fr 1fr;gap:8px 30px">
        <div><span class="t-sub">고객명</span> · <b>{{ $order->customer_name ?: '-' }}</b></div>
        <div><span class="t-sub">연락처</span> · {{ $order->customer_phone ?: '-' }}</div>
        <div><span class="t-sub">수령인</span> · {{ $order->receiver_name ?: '-' }}</div>
        <div><span class="t-sub">결제수단</span> · {{ $order->payment_method ?: '-' }}</div>
        <div style="grid-column:1/3"><span class="t-sub">주소</span> · {{ trim(($order->address1?:'').' '.($order->address2?:'')) ?: '-' }}</div>
    </div>
    <table class="table">
        <thead><tr><th>품목</th><th style="width:70px">수량</th><th style="width:110px;text-align:right">단가</th><th style="width:120px;text-align:right">금액</th></tr></thead>
        <tbody>
        @foreach($order->items as $it)
            <tr><td>{{ $it->product_name }}</td><td>{{ number_format($it->qty) }}</td><td style="text-align:right">{{ number_format($it->price) }}</td><td style="text-align:right;font-weight:700">{{ number_format($it->line_total) }}</td></tr>
        @endforeach
        <tr><td colspan="3" style="text-align:right;font-weight:800">합계 (VAT 포함)</td><td style="text-align:right;font-weight:800;color:#d84315">{{ number_format($order->total) }}원</td></tr>
        </tbody>
    </table>
</div>

{{-- 거래명세서 --}}
<div class="panel">
    <div class="panel-h"><div><h2>거래명세서</h2><div class="sub">PDF 다운로드 · 인쇄 · 이메일 발송</div></div></div>
    <div class="panel-b">
        <div style="display:flex;flex-wrap:wrap;gap:8px;align-items:center">
            <a href="{{ route('admin.orders.statement.preview', $order) }}" target="_blank" class="btn btn-sm">PDF 미리보기</a>
            <a href="{{ route('admin.orders.statement.print', $order) }}" target="_blank" class="btn btn-sm">인쇄 페이지</a>
            <a href="{{ route('admin.orders.statement.download', $order) }}" class="btn btn-sm btn-accent">PDF 다운로드</a>
            <form action="{{ route('admin.orders.statement.send', $order) }}" method="post" style="display:flex;gap:8px;align-items:center;margin-left:auto">@csrf
                <input class="input" style="height:38px;width:220px" type="email" name="email" placeholder="수신 이메일 (미입력 시 주문자)" value="{{ $order->user->email ?? '' }}">
                <button class="btn btn-sm">이메일 발송</button>
            </form>
        </div>
        @if($order->statements->count())
        <table class="table" style="margin-top:16px">
            <thead><tr><th style="width:60px">회차</th><th>파일명</th><th>동작</th><th>수신</th><th>담당</th><th>일시</th></tr></thead>
            <tbody>
            @foreach($order->statements as $s)
                <tr>
                    <td>{{ $s->seq }}</td>
                    <td class="t-sub">{{ $s->file_name }}</td>
                    <td><span class="badge {{ $s->action=='email'?'hq':'ok' }}">{{ $s->action_label }}</span>@if($s->error_message)<span class="badge off" title="{{ $s->error_message }}">실패</span>@endif</td>
                    <td class="t-sub">{{ $s->sent_to ?: '-' }}</td>
                    <td class="t-sub">{{ $s->issuer->name ?? '-' }}</td>
                    <td class="t-sub">{{ $s->created_at->format('Y.m.d H:i') }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
        @endif
    </div>
</div>

{{-- 세금계산서 --}}
<div class="panel">
    <div class="panel-h"><div><h2>세금계산서</h2><div class="sub">@if(config('popbill.simulate'))⚠ 시뮬레이트 모드 (실제 국세청 발행 아님)@else팝빌 전자세금계산서@endif</div></div></div>
    <div class="panel-b">
        @if($activeTi)
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px 30px">
                <div><span class="t-sub">상태</span> · <span class="badge {{ $activeTi->status=='issued'?'ok':'warn' }}">{{ $activeTi->status_label }}</span></div>
                <div><span class="t-sub">과세유형</span> · {{ $activeTi->kind_label }}</div>
                <div><span class="t-sub">공급받는자</span> · {{ $activeTi->receiver_corp_name }} ({{ $activeTi->receiver_corp_num }})</div>
                <div><span class="t-sub">승인번호</span> · {{ $activeTi->nts_confirm_num ?: '-' }}</div>
                <div><span class="t-sub">공급가액</span> · {{ number_format($activeTi->supply_amount) }}원</div>
                <div><span class="t-sub">세액</span> · {{ number_format($activeTi->tax_amount) }}원</div>
                <div><span class="t-sub">합계</span> · <b>{{ number_format($activeTi->total_amount) }}원</b></div>
                <div><span class="t-sub">발행일</span> · {{ optional($activeTi->issued_at)->format('Y-m-d H:i') }}</div>
            </div>
            <div style="display:flex;gap:8px;margin-top:16px">
                @unless($activeTi->status=='simulated')
                <a href="{{ route('admin.taxinvoice.popup', $activeTi) }}" target="_blank" class="btn btn-sm">팝빌 원본 보기</a>
                @endunless
                <form action="{{ route('admin.taxinvoice.cancel', $activeTi) }}" method="post" onsubmit="return confirm('세금계산서를 취소하시겠습니까?')">@csrf @method('DELETE')
                    <button class="btn btn-sm btn-danger">발행 취소</button>
                </form>
            </div>
        @else
            <form action="{{ route('admin.orders.taxinvoice', $order) }}" method="post" style="display:grid;grid-template-columns:1fr 1fr;gap:12px;max-width:640px">@csrf
                <label style="font-size:12.5px;font-weight:700">사업자등록번호 *
                    <input class="input" name="receiver_corp_num" required placeholder="123-45-67890" value="{{ old('receiver_corp_num') }}">
                </label>
                <label style="font-size:12.5px;font-weight:700">상호 *
                    <input class="input" name="receiver_corp_name" required value="{{ old('receiver_corp_name', $order->customer_name) }}">
                </label>
                <label style="font-size:12.5px;font-weight:700">대표자
                    <input class="input" name="receiver_ceo" value="{{ old('receiver_ceo') }}">
                </label>
                <label style="font-size:12.5px;font-weight:700">이메일
                    <input class="input" type="email" name="receiver_email" value="{{ old('receiver_email', $order->user->email ?? '') }}">
                </label>
                <label style="font-size:12.5px;font-weight:700">과세유형 *
                    <select class="input" name="invoice_kind">
                        <option value="tax">과세 (공급가액 + 부가세 10%)</option>
                        <option value="plain">면세 (계산서)</option>
                    </select>
                </label>
                <div style="display:flex;align-items:flex-end">
                    <button class="btn btn-accent" type="submit" style="width:100%">세금계산서 발행</button>
                </div>
            </form>
            <p class="t-sub" style="margin-top:10px">· 합계 {{ number_format($order->total) }}원 기준. 과세 선택 시 공급가액 {{ number_format(round($order->total/1.1)) }}원 + 세액 {{ number_format($order->total - round($order->total/1.1)) }}원으로 발행됩니다.</p>
        @endif

        @php $history = $order->taxInvoices->whereNotIn('status', ['issued','simulated']); @endphp
        @if($history->count())
        <div class="t-sub" style="margin-top:16px;font-weight:700">발행 이력</div>
        <table class="table">
            <thead><tr><th>관리번호</th><th>상태</th><th>합계</th><th>일시</th></tr></thead>
            <tbody>
            @foreach($order->taxInvoices as $ti)
                <tr>
                    <td class="t-sub">{{ $ti->mgt_key }}</td>
                    <td><span class="badge {{ $ti->is_active?'ok':($ti->status=='failed'?'off':'warn') }}">{{ $ti->status_label }}</span></td>
                    <td class="t-sub">{{ number_format($ti->total_amount) }}원</td>
                    <td class="t-sub">{{ $ti->created_at->format('Y.m.d H:i') }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
        @endif
    </div>
</div>
@endsection
