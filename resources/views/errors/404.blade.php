@extends('errors._base')
@section('title', '페이지를 찾을 수 없습니다')
@section('heading', '페이지를 찾을 수 없습니다')
@section('message')
    요청하신 페이지가 삭제되었거나 주소가 변경되었을 수 있습니다.<br>
    찾으시는 상품이 있다면 아래에서 검색해 보세요.
@endsection

@section('figure')
    <div class="big">404</div>
@endsection

@section('actions')
    <form class="search" action="{{ url('/search') }}" method="get">
        <input type="text" name="q" placeholder="안전화, 브랜드, 상품명 검색" aria-label="상품 검색" autofocus>
        <button type="submit">검색</button>
    </form>
    <div class="btns">
        <a class="btn primary" href="{{ url('/') }}">홈으로 가기</a>
        <a class="btn ghost" href="{{ url('/about') }}">회사소개</a>
    </div>
@endsection
