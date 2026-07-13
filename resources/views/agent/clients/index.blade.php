@extends('manage.layout')
@section('title', '거래처 관리')
@section('page', '거래처 관리')
@section('crumb', '영업 대상 기업 · 병원')
@section('actions')
    <a href="{{ route('agent.clients.create') }}" class="btn btn-accent btn-sm">+ 거래처 등록</a>
@endsection

@section('content')
<div class="panel">
    <div class="panel-h"><div><h2>거래처</h2><div class="sub">총 {{ number_format($clients->total()) }}곳</div></div></div>
    <table class="table">
        <thead><tr><th>거래처명</th><th>유형</th><th>담당자</th><th>연락처</th><th>주문</th><th style="width:150px">관리</th></tr></thead>
        <tbody>
        @forelse($clients as $c)
            <tr>
                <td class="t-name">{{ $c->name }}<div class="t-sub">{{ $c->business_no }}</div></td>
                <td>@php $t=['company'=>['hq','기업'],'hospital'=>['ok','병원'],'etc'=>['warn','기타']][$c->type]??['hq',$c->type]; @endphp<span class="badge {{ $t[0] }}">{{ $t[1] }}</span></td>
                <td>{{ $c->contact_name }}</td>
                <td class="t-sub">{{ $c->phone }}</td>
                <td>{{ $c->orders_count }}건</td>
                <td>
                    <div style="display:flex;gap:6px">
                        <a href="{{ route('agent.clients.edit', $c) }}" class="btn btn-sm">수정</a>
                        <form action="{{ route('agent.clients.destroy', $c) }}" method="post" onsubmit="return confirm('삭제하시겠습니까?')">@csrf @method('DELETE')<button class="btn btn-sm btn-danger">삭제</button></form>
                    </div>
                </td>
            </tr>
        @empty
            <tr><td colspan="6" class="empty">등록된 거래처가 없습니다. ‘거래처 등록’으로 추가해 보세요.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
{{ $clients->links('manage.pagination') }}
@endsection
