@extends('layouts.app')
@section('title', '가입 신청 완료 · KOR SAFETY')
@section('robots', 'noindex,nofollow')

@section('content')
<div class="wrap">
    <div class="acct" style="text-align:center">
        <div class="acct-ico" style="margin-left:auto;margin-right:auto;background:#14804a">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
        </div>
        <h1>가입 신청이 접수되었습니다</h1>
        <p class="sub" style="margin-bottom:26px">
            {{ $user->name }}님, 신청해 주셔서 감사합니다.<br>
            담당자 확인 후 승인되면 <b>{{ $user->email }}</b> 로 로그인하실 수 있습니다.
        </p>

        <div class="invite-box" style="text-align:left;margin-bottom:26px">
            <span class="invite-label">진행 상태</span>
            <b>승인 대기</b>
            <span class="badge warn">확인 중</span>
        </div>

        <p class="sub" style="font-size:13.5px">
            승인 전에는 로그인할 수 없습니다. 문의는 고객센터 02-2273-9533 으로 연락해 주세요.
        </p>
        <a href="{{ route('home') }}" class="btn btn-lg btn-block" style="margin-top:10px">쇼핑몰 둘러보기</a>
    </div>
</div>
@endsection
