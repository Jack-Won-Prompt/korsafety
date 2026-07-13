@extends('manage.layout')
@section('title', '판매점 대시보드')
@section('page', $seller->name)
@section('crumb', '판매점 · 매출 및 상품 현황')
@section('actions')
    <a href="{{ route('manage.products.create') }}" class="btn btn-accent btn-sm">+ 상품 등록</a>
@endsection

@section('content')
@php $max = max(1, collect($chart)->max('value')); @endphp

<div class="tiles">
    <div class="tile">
        <div class="lab">누적 매출</div>
        <div class="val">{{ number_format($stats['sales']) }}<span class="won">원</span></div>
    </div>
    <div class="tile">
        <div class="lab">주문 건수</div>
        <div class="val">{{ number_format($stats['orders']) }}<span class="won">건</span></div>
    </div>
    <div class="tile">
        <div class="lab">판매 수량</div>
        <div class="val">{{ number_format($stats['sold_qty']) }}<span class="won">개</span></div>
    </div>
    <div class="tile">
        <div class="lab">등록 상품</div>
        <div class="val">{{ number_format($stats['products']) }}<span class="won">개</span></div>
    </div>
</div>

<div class="grid-2">
    <div class="panel">
        <div class="panel-h"><div><h2>일별 매출</h2><div class="sub">최근 14일 자사 매출</div></div></div>
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
        <div class="panel-h"><div><h2>베스트 판매 상품</h2></div></div>
        <div class="panel-b" style="padding:8px 10px">
            <table class="table">
                <tbody>
                @forelse($bestItems as $b)
                    <tr>
                        <td><span class="t-name">{{ \Illuminate\Support\Str::limit($b->product_name, 28) }}</span></td>
                        <td style="text-align:right"><span class="t-sub">{{ $b->q }}개</span><br><b>{{ number_format($b->s) }}원</b></td>
                    </tr>
                @empty
                    <tr><td class="empty">판매 데이터가 없습니다.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
