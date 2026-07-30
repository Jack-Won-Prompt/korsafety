@extends('layouts.app')
@section('title', '계정 삭제 · KOR SAFETY')
@section('meta_desc', 'KOR SAFETY 계정 삭제 안내 및 요청')

@section('content')
@php $c = config('company'); @endphp
<div class="legal">
    <div class="legal-head">
        <div class="k">DELETE ACCOUNT</div>
        <h1>계정 삭제</h1>
        <div class="meta">{{ $c['name'] }} · 회원 탈퇴 및 개인정보 삭제 안내</div>
    </div>

    @if(session('error'))<div class="note" style="border-color:var(--danger);color:var(--danger)">{{ session('error') }}</div>@endif

    <p>{{ $c['name'] }}가 운영하는 KOR SAFETY 서비스의 계정을 삭제(회원 탈퇴)하실 수 있습니다. 계정을 삭제하면 아래 안내에 따라 회원 정보가 처리됩니다.</p>

    <h2>삭제되는 정보</h2>
    <ul>
        <li>회원 계정 정보 : 이름, 이메일, 비밀번호</li>
        <li>서비스 로그인 정보 및 개인 식별 연결</li>
    </ul>

    <h2>보관되는 정보 (법령에 따른 보존)</h2>
    <p>다음 정보는 관련 법령에 따라 일정 기간 보관되며, 이 경우 개인을 식별할 수 없도록 분리(익명화)하여 보관합니다.</p>
    <table class="tbl">
        <tr><th>항목</th><th>근거</th><th>기간</th></tr>
        <tr><td>주문·결제·배송 기록</td><td>전자상거래법</td><td>5년</td></tr>
        <tr><td>세금계산서 등 거래 증빙</td><td>국세기본법</td><td>5년</td></tr>
    </table>

    <h2>삭제 방법</h2>
    <ol class="steps">
        <li>KOR SAFETY 계정으로 로그인합니다.</li>
        <li>본 페이지(계정 삭제)에서 비밀번호를 입력하고 동의 후 <b>계정 삭제</b>를 진행합니다.</li>
        <li>삭제가 완료되면 즉시 로그아웃되며 계정은 복구할 수 없습니다.</li>
    </ol>
    <div class="note">
        직접 삭제가 어려운 경우 고객센터로 요청하실 수 있습니다.<br>
        · 이메일 : {{ $c['email'] }} · 전화 : {{ $c['tel'] }} (평일 09:00~18:00)<br>
        요청 접수 후 본인 확인을 거쳐 지체 없이 처리해 드립니다.
    </div>

    @auth
        @if(auth()->user()->isCustomer())
            <div class="del-box">
                <h2>계정 삭제하기</h2>
                <p style="font-size:14px;margin:0 0 4px">현재 <b>{{ auth()->user()->email }}</b> 계정에 로그인되어 있습니다. 삭제 시 되돌릴 수 없습니다.</p>
                <form action="{{ route('account.destroy') }}" method="post" onsubmit="return confirm('정말로 계정을 삭제하시겠습니까? 되돌릴 수 없습니다.')">
                    @csrf
                    @method('DELETE')
                    <label>비밀번호 확인</label>
                    <input type="password" name="password" required placeholder="현재 비밀번호" autocomplete="current-password">
                    <label class="chk">
                        <input type="checkbox" name="confirm" value="1" required>
                        <span>위 안내 내용을 확인하였으며, 계정 삭제 및 개인정보 처리에 동의합니다.</span>
                    </label>
                    <button type="submit" class="btn btn-danger">계정 영구 삭제</button>
                </form>
            </div>
        @else
            <div class="note">현재 업무용 계정(관리자·판매자 등)으로 로그인되어 있습니다. 업무용 계정 삭제는 고객센터를 통해 처리해 주세요.</div>
        @endif
    @else
        <div class="del-box">
            <h2>로그인이 필요합니다</h2>
            <p style="font-size:14px;margin:0 0 16px">계정을 삭제하려면 먼저 로그인해 주세요.</p>
            <a href="{{ route('login') }}" class="btn btn-danger">로그인하고 계정 삭제</a>
        </div>
    @endauth
</div>
@endsection
