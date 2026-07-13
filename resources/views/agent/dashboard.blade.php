@extends('manage.layout')
@section('title', '협력사 대시보드')
@section('page', $agent->name)
@section('crumb', '협력사 · 영업 실적 및 커미션 현황')
@section('actions')
    <a href="{{ route('agent.orders.create') }}" class="btn btn-accent btn-sm">+ 주문 등록</a>
@endsection

@section('content')
@php $max = max(1, collect($chart)->max('value')); @endphp

<div class="tiles">
    <div class="tile">
        <div class="lab"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v10"/></svg> 지급대기 커미션</div>
        <div class="val" style="color:var(--accent)">{{ number_format($stats['commission_pending']) }}<span class="won">원</span></div>
        <div class="sub">수수료율 {{ rtrim(rtrim($stats['rate'],'0'),'.') }}%</div>
    </div>
    <div class="tile">
        <div class="lab">누적 커미션 (적립)</div>
        <div class="val">{{ number_format($stats['commission_accrued']) }}<span class="won">원</span></div>
        <div class="sub">지급완료 {{ number_format($stats['commission_paid']) }}원</div>
    </div>
    <div class="tile">
        <div class="lab">영업 매출 (결제완료)</div>
        <div class="val">{{ number_format($stats['sales']) }}<span class="won">원</span></div>
    </div>
    <div class="tile">
        <div class="lab">등록 주문 / 거래처</div>
        <div class="val">{{ number_format($stats['orders']) }}<span class="won">건</span></div>
        <div class="sub">거래처 {{ number_format($stats['clients']) }}곳</div>
    </div>
</div>

<div class="grid-2">
    <div class="panel">
        <div class="panel-h"><div><h2>일별 커미션</h2><div class="sub">최근 14일 적립 커미션</div></div></div>
        <div class="panel-b">
            <div class="chart">
                @foreach($chart as $c)
                    <div class="bar" title="{{ $c['date'] }} · {{ number_format($c['value']) }}원">
                        <div class="fill" style="height:{{ max(2, round($c['value']/$max*100)) }}%"></div>
                        <div class="d">{{ $c['date'] }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    <div class="panel">
        <div class="panel-h"><div><h2>최근 주문</h2></div><a href="{{ route('agent.orders.index') }}" class="btn btn-sm">전체</a></div>
        <div class="panel-b" style="padding:6px 10px">
            <table class="table">
                <tbody>
                @forelse($recent as $o)
                    <tr>
                        <td><a href="{{ route('agent.orders.show',$o) }}" class="t-name">{{ $o->order_no }}</a><div class="t-sub">{{ $o->client->name ?? '-' }}</div></td>
                        <td style="text-align:right">
                            @php $m=['pending'=>['warn','접수'],'paid'=>['ok','결제완료'],'shipped'=>['warn','배송중'],'done'=>['ok','완료'],'cancelled'=>['off','취소']][$o->status]??['ok',$o->status]; @endphp
                            <span class="badge {{ $m[0] }}">{{ $m[1] }}</span><br>
                            <b style="color:var(--accent)">+{{ number_format($o->commission_amount) }}원</b>
                        </td>
                    </tr>
                @empty
                    <tr><td class="empty">등록된 주문이 없습니다.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
