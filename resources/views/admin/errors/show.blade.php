@extends('manage.layout')
@section('title', '에러 #'.$log->id)
@section('page', '에러 상세 #'.$log->id)
@section('crumb', $log->type_label.' · '.$log->short_class)
@section('actions')
    <a href="{{ route('admin.errors') }}" class="btn btn-sm">← 목록</a>
    <form action="{{ route('admin.errors.destroy', $log) }}" method="post" style="display:inline"
          onsubmit="return confirm('이 에러 기록을 삭제할까요? 되돌릴 수 없습니다.')">@csrf @method('DELETE')
        <button class="btn btn-sm btn-danger">삭제</button>
    </form>
@endsection

@push('styles')
<style>
    .err-pre{margin:0;padding:14px 16px;background:#161b26;color:#e6e9f0;border-radius:10px;font-family:ui-monospace,Consolas,monospace;
             font-size:12px;line-height:1.6;white-space:pre-wrap;word-break:break-all;max-height:520px;overflow:auto}
    .err-message{font-size:14.5px;font-weight:700;color:#1b2130;line-height:1.55;white-space:pre-wrap;word-break:break-all}
    .err-kv{display:grid;grid-template-columns:110px 1fr;gap:8px 14px;font-size:13px}
    .err-kv dt{color:#8a90a0;font-weight:700}
    .err-kv dd{margin:0;word-break:break-all}
    .err-mono{font-family:ui-monospace,Consolas,monospace;font-size:12.5px}
    .err-status{display:flex;gap:12px;align-items:center;flex-wrap:wrap}
</style>
@endpush

@section('content')
{{-- 처리 상태 --}}
<div class="panel">
    <div class="panel-h">
        <div class="err-status">
            <span class="badge {{ $log->isResolved() ? 'ok' : 'off' }}" style="font-size:13px;padding:6px 14px">{{ $log->status_label }}</span>
            <div>
                <h2 style="margin:0">처리 상태</h2>
                <div class="sub">
                    @if($log->isResolved())
                        {{ optional($log->resolved_at)->format('Y.m.d H:i') }} · {{ $log->resolver->name ?? '알 수 없음' }} 님이 해결 처리
                    @else
                        {{ number_format($log->occurrences) }}회 발생 · 최근 {{ optional($log->last_seen_at)->locale('ko')->diffForHumans() }}
                    @endif
                </div>
            </div>
        </div>
    </div>
    <div class="panel-b">
        @if($log->isResolved())
            @if($log->resolution_note)
                <div class="t-sub" style="margin-bottom:6px">처리 내용</div>
                <div style="white-space:pre-wrap;font-size:13.5px;margin-bottom:14px">{{ $log->resolution_note }}</div>
            @endif
            <form action="{{ route('admin.errors.reopen', $log) }}" method="post">@csrf
                <button class="btn btn-sm">미해결로 되돌리기</button>
                <span class="hint" style="margin-left:8px">해결 후 같은 에러가 다시 나면 새 미해결 건으로 자동 등록됩니다.</span>
            </form>
        @else
            <form action="{{ route('admin.errors.resolve', $log) }}" method="post">@csrf
                <textarea class="input" name="note" rows="3" maxlength="2000" style="width:100%;height:auto;padding:10px 12px"
                          placeholder="처리 내용 (선택) — 원인, 수정한 내용, 배포 버전 등">{{ old('note') }}</textarea>
                <div style="margin-top:10px"><button class="btn btn-accent">해결 처리</button></div>
            </form>
        @endif
    </div>
</div>

{{-- 에러 정보 --}}
<div class="panel">
    <div class="panel-h"><div><h2>에러 내용</h2><div class="sub">{{ $log->exception_class }}</div></div></div>
    <div class="panel-b">
        <div class="err-message">{{ $log->message }}</div>
        <dl class="err-kv" style="margin-top:16px">
            <dt>유형</dt><dd>{{ $log->type_label }}</dd>
            <dt>발생 위치</dt><dd class="err-mono">{{ $log->file }}{{ $log->line ? ':'.$log->line : '' }}</dd>
            @if($log->code)<dt>에러 코드</dt><dd class="err-mono">{{ $log->code }}</dd>@endif
            <dt>발생 횟수</dt><dd><b>{{ number_format($log->occurrences) }}회</b></dd>
            <dt>최초 발생</dt><dd>{{ optional($log->first_seen_at)->format('Y.m.d H:i:s') }}</dd>
            <dt>최근 발생</dt><dd>{{ optional($log->last_seen_at)->format('Y.m.d H:i:s') }}</dd>
        </dl>
    </div>
</div>

<div class="grid-2">
    {{-- 요청 정보 --}}
    <div class="panel">
        <div class="panel-h"><div><h2>요청 정보</h2><div class="sub">마지막 발생 기준</div></div></div>
        <div class="panel-b">
            <dl class="err-kv">
                <dt>출처</dt><dd>{{ $log->source_label }}</dd>
                <dt>{{ $log->source === 'console' ? '명령' : 'URL' }}</dt>
                <dd class="err-mono">@if($log->method && $log->method !== 'CLI'){{ $log->method }} @endif{{ $log->url ?: '-' }}</dd>
                <dt>회원</dt><dd>{{ $log->user ? $log->user->name.' ('.$log->user->email.')' : ($log->source === 'console' ? '-' : '비회원') }}</dd>
                <dt>IP</dt><dd>{{ $log->ip_address ?: '-' }}</dd>
                <dt>브라우저</dt><dd class="t-sub">{{ $log->user_agent ?: '-' }}</dd>
            </dl>
        </div>
    </div>

    {{-- 요청 파라미터 --}}
    <div class="panel">
        <div class="panel-h"><div><h2>요청 파라미터</h2><div class="sub">비밀번호 · 토큰 등 민감 정보는 가려서 저장</div></div></div>
        <div class="panel-b">
            @if($log->input)
                <pre class="err-pre" style="max-height:260px">{{ json_encode($log->input, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
            @else
                <div class="empty" style="padding:18px 0">파라미터 없음</div>
            @endif
        </div>
    </div>
</div>

{{-- 스택 트레이스 --}}
<div class="panel">
    <div class="panel-h"><div><h2>스택 트레이스</h2><div class="sub">최초 발생 시점</div></div></div>
    <div class="panel-b">
        <pre class="err-pre">{{ $log->trace ?: '트레이스 없음' }}</pre>
    </div>
</div>

{{-- 같은 원인의 다른 기록 --}}
@if($related->count())
<div class="panel">
    <div class="panel-h"><div><h2>같은 원인의 다른 기록</h2><div class="sub">해결 후 재발한 이력</div></div></div>
    <table class="table">
        <thead><tr><th style="width:70px">번호</th><th style="width:80px">상태</th><th style="width:90px">발생</th><th style="width:150px">최초 ~ 최근</th><th>처리</th></tr></thead>
        <tbody>
        @foreach($related as $r)
            <tr>
                <td><a href="{{ route('admin.errors.show', $r) }}">#{{ $r->id }}</a></td>
                <td><span class="badge {{ $r->isResolved() ? 'ok' : 'off' }}">{{ $r->status_label }}</span></td>
                <td>{{ number_format($r->occurrences) }}회</td>
                <td class="t-sub">{{ optional($r->first_seen_at)->format('m.d H:i') }} ~ {{ optional($r->last_seen_at)->format('m.d H:i') }}</td>
                <td class="t-sub">
                    @if($r->isResolved())
                        {{ optional($r->resolved_at)->format('Y.m.d') }} {{ $r->resolver->name ?? '' }}
                        @if($r->resolution_note) · {{ \Illuminate\Support\Str::limit($r->resolution_note, 60) }}@endif
                    @else - @endif
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endif
@endsection
