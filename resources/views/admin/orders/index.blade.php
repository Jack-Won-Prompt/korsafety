@extends('manage.layout')
@section('title', '주문 관리')
@section('page', '주문 관리')
@section('crumb', '검색 · 피킹리스트 · 거래명세서 · 세금계산서')

@section('content')
{{-- 검색 필터 --}}
<div class="panel">
    <div class="panel-b">
        <form method="get" style="display:flex;flex-wrap:wrap;gap:8px;align-items:center">
            <input class="input" style="height:38px;width:220px" name="q" value="{{ $q }}" placeholder="주문번호·고객·수령인·연락처">
            <select class="input" style="height:38px" name="status">
                <option value="">전체 상태</option>
                @foreach(['pending'=>'결제대기','paid'=>'결제완료','shipped'=>'배송중','done'=>'배송완료','cancelled'=>'취소'] as $k=>$v)
                    <option value="{{ $k }}" @selected($status===$k)>{{ $v }}</option>
                @endforeach
            </select>
            <span class="t-sub">기간</span>
            <input class="input" style="height:38px" type="date" name="from" value="{{ $from }}">
            <span class="t-sub">~</span>
            <input class="input" style="height:38px" type="date" name="to" value="{{ $to }}">
            <button class="btn btn-sm btn-accent">검색</button>
            <a href="{{ route('admin.orders.index') }}" class="btn btn-sm">초기화</a>
        </form>
    </div>
</div>

{{-- 피킹 리스트 통합 출력 (체크 선택) --}}
<form id="pickForm" method="post" action="{{ route('admin.orders.picking') }}" target="_blank">
    @csrf
    <input type="hidden" name="format" id="pickFormat" value="print">
    <input type="hidden" name="q" value="{{ $q }}">
    <input type="hidden" name="status" value="{{ $status }}">
    <input type="hidden" name="from" value="{{ $from }}">
    <input type="hidden" name="to" value="{{ $to }}">

    <div class="panel">
        <div class="panel-h">
            <div><h2>전체 주문</h2><div class="sub">총 {{ number_format($orders->total()) }}건 · <span id="selCount">0</span>건 선택</div></div>
            <div style="display:flex;gap:8px">
                <button type="button" class="btn btn-sm" onclick="pick('print')">🖨 PDA 출력(피킹)</button>
                <button type="button" class="btn btn-sm btn-accent" onclick="pick('pdf')">⭳ 피킹리스트 PDF</button>
            </div>
        </div>
        <table class="table">
            <thead><tr>
                <th style="width:34px"><input type="checkbox" id="chkAll" title="전체 선택"></th>
                <th>주문번호</th><th>고객/수령인</th><th style="text-align:right">금액</th><th style="width:60px">품목</th><th>상태</th><th>명세/계산서</th><th>일시</th><th style="width:70px">관리</th>
            </tr></thead>
            <tbody>
            @forelse($orders as $o)
                <tr>
                    <td><input type="checkbox" class="chkRow" name="ids[]" value="{{ $o->id }}"></td>
                    <td class="t-name">{{ $o->order_no }}</td>
                    <td>{{ $o->customer_name ?: '-' }}<div class="t-sub">{{ $o->receiver_name ?: $o->customer_phone }}</div></td>
                    <td style="text-align:right;font-weight:800">{{ number_format($o->total) }}원</td>
                    <td class="t-sub">{{ $o->items_count }}개</td>
                    <td><span class="badge {{ in_array($o->status,['paid','done'])?'ok':($o->status=='cancelled'?'off':'warn') }}">{{ $o->status_label }}</span></td>
                    <td>
                        @if($o->statements->count())<span class="badge ok" title="거래명세서">명세 {{ $o->statements->count() }}</span>@endif
                        @php $ti = $o->taxInvoices->firstWhere('is_active', true); @endphp
                        @if($ti)<span class="badge hq">계산서</span>@endif
                        @if(!$o->statements->count() && !$ti)<span class="t-sub">-</span>@endif
                    </td>
                    <td class="t-sub">{{ optional($o->created_at)->format('Y.m.d H:i') }}</td>
                    <td><a href="{{ route('admin.orders.show', $o) }}" class="btn btn-sm btn-accent">상세</a></td>
                </tr>
            @empty
                <tr><td colspan="9" class="empty">조건에 맞는 주문이 없습니다.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</form>
{{ $orders->links('manage.pagination') }}

@push('scripts')
<script>
(function(){
    var all = document.getElementById('chkAll');
    var rows = function(){ return Array.prototype.slice.call(document.querySelectorAll('.chkRow')); };
    var count = document.getElementById('selCount');
    function refresh(){ count.textContent = rows().filter(function(c){return c.checked;}).length; }
    all.addEventListener('change', function(){ rows().forEach(function(c){ c.checked = all.checked; }); refresh(); });
    document.addEventListener('change', function(e){ if(e.target.classList.contains('chkRow')) refresh(); });
    window.pick = function(fmt){
        var n = rows().filter(function(c){return c.checked;}).length;
        if(n === 0 && !confirm('선택된 주문이 없습니다. 현재 검색 결과 전체를 출력할까요?')) return;
        document.getElementById('pickFormat').value = fmt;
        document.getElementById('pickForm').submit();
    };
})();
</script>
@endpush
@endsection
