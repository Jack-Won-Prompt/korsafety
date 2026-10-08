@extends('manage.layout')
@section('title', '회원 초대')
@section('page', '회원 초대')
@section('crumb', '이메일로 가입 안내 발송 · 엑셀 명단 일괄 초대')
@section('actions')
    <a href="{{ route('admin.members') }}" class="btn btn-sm">회원 관리</a>
    <a href="{{ route('admin.invitations.template') }}" class="btn btn-sm">⭳ 엑셀 양식</a>
@endsection

@push('styles')
<style>
    .inv-tabs{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:12px}
    .inv-tabs a{padding:7px 14px;border-radius:999px;border:1px solid #e3e6ee;background:#fff;font-size:13px;font-weight:700;color:#555;text-decoration:none}
    .inv-tabs a.on{background:#1b2130;border-color:#1b2130;color:#fff}
    .inv-tabs a b{margin-left:4px;font-weight:800}

    /* 명단 파일 고르기 — 브라우저 기본 파일 입력은 꾸밀 수 없어 버튼과 이름을 따로 그린다 */
    .file-pick{display:flex;align-items:center;gap:12px;min-width:0;padding:9px 12px;
               border:1.5px dashed #d8dce6;border-radius:10px;background:#fafbfd;transition:.15s}
    .file-pick:hover{border-color:#1b2130;background:#f4f6fa}
    .file-pick input[type="file"]{position:absolute;width:1px;height:1px;padding:0;margin:-1px;
                                  overflow:hidden;clip:rect(0,0,0,0);border:0}
    .file-pick-btn{display:inline-flex;align-items:center;gap:6px;flex:0 0 auto;height:32px;
                   margin:0;padding:0 14px;border-radius:999px;background:#1b2130;color:#fff;
                   font-size:13px;font-weight:700;cursor:pointer}
    .file-pick-btn:hover{background:#000}
    .file-pick-btn svg{width:15px;height:15px}
    .file-pick input[type="file"]:focus-visible + .file-pick-btn{outline:2px solid #ff5722;outline-offset:2px}
    .file-pick-name{min-width:0;font-size:13px;color:#8a90a0;overflow:hidden;
                    text-overflow:ellipsis;white-space:nowrap}
    .file-pick.has-file{border-style:solid;border-color:#86c9a4;background:#f0fdf4}
    .file-pick.has-file .file-pick-name{color:#14804a;font-weight:700}
    @media (max-width:560px){
        .file-pick{flex-wrap:wrap}
        .file-pick-name{width:100%}
    }
</style>
@endpush

@include('partials.file-pick')

@section('content')
@push('scripts')
<script>
// 명단 파일을 고르지 않고 보내려 하면 미리 알려 준다
document.addEventListener('click', function (e) {
    var btn = e.target.closest('[data-need-file]');
    if (!btn) return;
    var form = btn.closest('form');
    var input = form && form.querySelector('input[type="file"]');
    if (input && !(input.files && input.files.length)) {
        e.preventDefault();
        alert('올릴 명단 파일을 먼저 선택해 주세요.');
        input.click();
    }
});
</script>
@endpush
@php $tabUrl = fn ($s) => route('admin.invitations', array_merge(request()->except(['page','status']), ['status' => $s])); @endphp

<div class="tiles">
    <div class="tile">
        <div class="lab">보낸 초대</div>
        <div class="val">{{ number_format($stats['all']) }}<span class="won"> 건</span></div>
        <div class="sub">가입 완료 {{ number_format($stats['accepted']) }}건</div>
    </div>
    <div class="tile">
        <div class="lab">응답 대기</div>
        <div class="val" style="{{ $stats['sent'] ? 'color:#a35a06' : '' }}">{{ number_format($stats['sent']) }}<span class="won"> 건</span></div>
        <div class="sub">아직 가입하지 않은 초대</div>
    </div>
    <div class="tile">
        <div class="lab">승인 대기 회원</div>
        <div class="val" style="{{ $stats['waiting'] ? 'color:#c11c0f' : '' }}">{{ number_format($stats['waiting']) }}<span class="won"> 명</span></div>
        <div class="sub"><a href="{{ route('admin.members', ['approval' => 'pending']) }}">승인 처리하러 가기 →</a></div>
    </div>
    <div class="tile">
        <div class="lab">취소된 초대</div>
        <div class="val">{{ number_format($stats['cancelled']) }}<span class="won"> 건</span></div>
        <div class="sub">링크가 열리지 않습니다</div>
    </div>
</div>

{{-- 초대 보내기 --}}
<div class="panel">
    <div class="panel-h"><div><h2>초대 보내기</h2><div class="sub">초대 링크는 {{ \App\Models\Invitation::VALID_DAYS }}일간 유효합니다</div></div></div>
    <div class="panel-b">
        <form method="post" action="{{ route('admin.invitations.store') }}" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">@csrf
            <input class="input" style="height:38px;flex:1 1 220px;min-width:180px" type="email" name="email" placeholder="이메일 *" required>
            <input class="input" style="height:38px;flex:0 0 130px" name="name" placeholder="이름">
            <input class="input" style="height:38px;flex:0 0 180px" name="company_name" placeholder="회사명">
            <select class="input" style="height:38px;flex:0 0 140px" name="role">
                @foreach(\App\Models\Invitation::ROLES as $k => $v)
                    <option value="{{ $k }}">{{ $v }}</option>
                @endforeach
            </select>
            <button class="btn btn-sm btn-accent">초대 메일 보내기</button>
        </form>

        <div style="margin-top:18px;padding-top:18px;border-top:1px solid #eef0f4">
            <div style="font-weight:700;font-size:14px;margin-bottom:10px">엑셀 명단으로 일괄 초대</div>

            <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin-bottom:12px">
                <a href="{{ route('admin.invitations.template') }}" class="btn btn-sm btn-accent">① 엑셀 양식 내려받기</a>
                <span class="t-sub">양식을 받아 <b>이메일 · 이름 · 회사명 · 구분(일반/협력사)</b>을 채운 뒤, 아래에서 올려 주세요.</span>
            </div>

            <form method="post" action="{{ route('admin.invitations.import') }}" enctype="multipart/form-data"
                  style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">@csrf
                <span class="t-sub" style="font-weight:700">② 작성한 명단</span>
                <div class="file-pick" style="flex:1 1 280px">
                    <input type="file" id="invFile" name="file" accept=".csv,.txt">
                    <label for="invFile" class="file-pick-btn">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="M7 10l5-5 5 5"/><path d="M12 5v12"/></svg>
                        파일 선택
                    </label>
                    <span class="file-pick-name" data-file-name data-empty="선택한 파일이 없습니다">선택한 파일이 없습니다</span>
                </div>
                <button class="btn btn-sm" data-need-file>명단 올려서 보내기</button>
            </form>

            <div class="t-sub" style="margin-top:10px;line-height:1.6">
                · 엑셀에서 수정한 뒤 <b>CSV(쉼표로 분리)</b> 형식으로 저장해 올려 주세요.<br>
                · 이미 가입한 이메일이나 형식이 틀린 줄은 건너뛰고, 건너뛴 사유를 알려드립니다.
            </div>
        </div>
    </div>
</div>

<div class="inv-tabs">
    <a href="{{ $tabUrl('all') }}" class="{{ $status === 'all' ? 'on' : '' }}">전체<b>{{ number_format($stats['all']) }}</b></a>
    @foreach(\App\Models\Invitation::STATUSES as $k => $v)
        @if($k !== 'expired')
            <a href="{{ $tabUrl($k) }}" class="{{ $status === $k ? 'on' : '' }}">{{ $v }}<b>{{ number_format($stats[$k] ?? 0) }}</b></a>
        @endif
    @endforeach
</div>

<div class="panel">
    <div class="panel-h">
        <div><h2>초대 내역</h2><div class="sub">총 {{ number_format($invitations->total()) }}건</div></div>
        <form method="get" style="display:flex;gap:8px;align-items:center">
            <input type="hidden" name="status" value="{{ $status }}">
            <input class="input" style="height:34px;width:220px;font-size:12.5px" name="q" value="{{ $q }}" placeholder="이메일 · 이름 · 회사명">
            <button class="btn btn-sm">검색</button>
        </form>
    </div>
    <table class="table">
        <thead><tr>
            <th>이메일 / 이름</th>
            <th style="width:160px">회사명</th>
            <th style="width:100px">구분</th>
            <th style="width:100px">상태</th>
            <th style="width:130px">보낸 날짜</th>
            <th style="width:130px">유효 기간</th>
            <th style="width:170px">관리</th>
        </tr></thead>
        <tbody>
        @forelse($invitations as $inv)
            <tr>
                <td>
                    <span class="t-name">{{ $inv->email }}</span>
                    <div class="t-sub">{{ $inv->name ?: '-' }}@if($inv->inviter) · 보낸 사람 {{ $inv->inviter->name }}@endif</div>
                </td>
                <td class="t-sub">{{ $inv->company_name ?: '-' }}</td>
                <td><span class="badge {{ $inv->role === 'partner' ? 'hq' : 'ok' }}">{{ $inv->role_label }}</span></td>
                <td><span class="badge {{ $inv->status_badge }}">{{ $inv->status_label }}</span></td>
                <td class="t-sub">{{ optional($inv->sent_at)->format('Y.m.d H:i') }}</td>
                <td class="t-sub">{{ optional($inv->expires_at)->format('Y.m.d') }}까지</td>
                <td>
                    <div style="display:flex;gap:6px">
                        @if($inv->status === 'accepted')
                            @if($inv->user)<a href="{{ route('admin.members.show', $inv->user) }}" class="btn btn-sm">회원 보기</a>@endif
                        @else
                            <form method="post" action="{{ route('admin.invitations.resend', $inv) }}">@csrf
                                <button class="btn btn-sm">다시 보내기</button>
                            </form>
                            @if($inv->status !== 'cancelled')
                                <form method="post" action="{{ route('admin.invitations.cancel', $inv) }}"
                                      onsubmit="return confirm('이 초대를 취소할까요? 링크가 열리지 않게 됩니다.')">@csrf
                                    <button class="btn btn-sm btn-danger">취소</button>
                                </form>
                            @endif
                        @endif
                    </div>
                </td>
            </tr>
        @empty
            <tr><td colspan="7" class="empty">보낸 초대가 없습니다.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
{{ $invitations->links('manage.pagination') }}
@endsection
