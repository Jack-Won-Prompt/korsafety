@extends('manage.layout')
@section('title', '자동 처리 Agent')
@section('page', '자동 처리 Agent')
@section('crumb', '운영 오류 자동 수정 · SR 자동 답변 내역')
@section('actions')
    <form method="post" action="{{ route('admin.agent.run') }}" style="display:inline">@csrf
        <button class="btn btn-accent btn-sm">지금 처리</button>
    </form>
    <a href="{{ route('admin.settings') }}" class="btn btn-sm">설정</a>
@endsection

@push('styles')
<style>
    .ag-tabs{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:12px}
    .ag-tabs a{padding:7px 14px;border-radius:999px;border:1px solid #e3e6ee;background:#fff;font-size:13px;font-weight:700;color:#555;text-decoration:none}
    .ag-tabs a.on{background:#1b2130;border-color:#1b2130;color:#fff}
    .ag-tabs a b{margin-left:4px;font-weight:800}
    .ag-switch{display:flex;gap:16px;flex-wrap:wrap;font-size:13px}
    .ag-switch span{display:inline-flex;align-items:center;gap:6px}
    .ag-dot{width:9px;height:9px;border-radius:50%;display:inline-block}
    .ag-on{background:#14804a}.ag-off{background:#c9ccd3}
    .ag-detail{font-size:12.5px;color:#555;line-height:1.6;white-space:pre-wrap;word-break:break-word}
    .ag-mono{font-family:ui-monospace,Consolas,monospace;font-size:11.5px;color:#8a90a0}
</style>
@endpush

@section('content')
@php $tabUrl = fn ($s) => route('admin.agent', array_merge(request()->except(['page','status']), ['status' => $s])); @endphp

{{-- 동작 상태 --}}
<div class="panel">
    <div class="panel-h"><div><h2>동작 상태</h2><div class="sub">설정에서 언제든 끄고 켤 수 있습니다</div></div></div>
    <div class="panel-b">
        <div class="ag-switch">
            <span><i class="ag-dot {{ $ready['enabled'] ? 'ag-on' : 'ag-off' }}"></i> 전체 {{ $ready['enabled'] ? '켜짐' : '꺼짐' }}</span>
            <span><i class="ag-dot {{ $ready['error'] ? 'ag-on' : 'ag-off' }}"></i> 오류 접수</span>
            <span><i class="ag-dot {{ $ready['sr'] ? 'ag-on' : 'ag-off' }}"></i> SR 접수</span>
            <span><i class="ag-dot {{ $ready['auto_fix'] ? 'ag-on' : 'ag-off' }}"></i> 자동 수정·배포</span>
            <span><i class="ag-dot {{ $ready['key'] ? 'ag-on' : 'ag-off' }}"></i> API 키 {{ $ready['key'] ? '등록됨' : '없음' }}</span>
            <span class="t-sub">하루 상한 {{ $ready['limit'] }}건 · 오늘 {{ $stats['today'] }}건 처리 · 토큰 {{ number_format($stats['tokens_today']) }}</span>
        </div>
        @unless($ready['key'])
            <div class="t-sub" style="margin-top:10px;color:#c11c0f">서버 환경 설정에 ANTHROPIC_API_KEY 가 없어 동작하지 않습니다.</div>
        @endunless
    </div>
</div>

<div class="ag-tabs">
    <a href="{{ $tabUrl('all') }}" class="{{ $status === 'all' ? 'on' : '' }}">전체<b>{{ number_format($stats['all']) }}</b></a>
    @foreach(\App\Models\AgentTask::STATUSES as $k => $v)
        <a href="{{ $tabUrl($k) }}" class="{{ $status === $k ? 'on' : '' }}">{{ $v }}<b>{{ number_format($stats[$k] ?? 0) }}</b></a>
    @endforeach
</div>

<div class="panel">
    <div class="panel-h"><div><h2>작업 내역</h2><div class="sub">총 {{ number_format($tasks->total()) }}건 · 무엇을 어떻게 처리했는지 남습니다</div></div></div>
    <table class="table">
        <thead><tr>
            <th style="width:60px">번호</th>
            <th style="width:90px">구분</th>
            <th style="width:90px">상태</th>
            <th>처리 내용</th>
            <th style="width:120px">커밋</th>
            <th style="width:130px">접수</th>
            <th style="width:150px">관리</th>
        </tr></thead>
        <tbody>
        @forelse($tasks as $t)
            @php $a = $t->analysisArray(); @endphp
            <tr>
                <td class="t-sub">#{{ $t->id }}</td>
                <td><span class="badge {{ $t->type === 'error' ? 'alert' : 'hq' }}">{{ $t->type_label }}</span>
                    <div class="t-sub" style="font-size:11px">{{ $t->ref_id }}번</div></td>
                <td><span class="badge {{ $t->status_badge }}">{{ $t->status_label }}</span></td>
                <td>
                    <div class="t-name">{{ $t->payload['title'] ?? ($t->payload['message'] ?? '-') }}</div>
                    @if($t->result)<div class="ag-detail" style="margin-top:4px">{{ $t->result }}</div>@endif
                    @if(!empty($a['cause']))
                        <div class="ag-detail" style="margin-top:6px;color:#8a90a0">원인: {{ \Illuminate\Support\Str::limit($a['cause'], 160) }}</div>
                    @endif
                    @if(!empty($a['file']))<div class="ag-mono">{{ $a['file'] }}</div>@endif
                </td>
                <td class="t-sub">
                    @if($t->commit_hash)<span class="ag-mono">{{ substr($t->commit_hash, 0, 7) }}</span>@else-@endif
                    @if($t->tokens)<div style="font-size:11px">{{ number_format($t->tokens) }} 토큰</div>@endif
                </td>
                <td class="t-sub">{{ optional($t->created_at)->format('m.d H:i') }}
                    @if($t->finished_at)<div style="font-size:11px">완료 {{ $t->finished_at->format('H:i') }}</div>@endif
                </td>
                <td>
                    <div style="display:flex;gap:6px;flex-wrap:wrap">
                        @if($t->status !== 'done')
                            <form method="post" action="{{ route('admin.agent.retry', $t) }}">@csrf
                                <button class="btn btn-sm">다시 시도</button>
                            </form>
                        @endif
                        @if($t->commit_hash)
                            <form method="post" action="{{ route('admin.agent.revert', $t) }}"
                                  onsubmit="return confirm('이 수정을 되돌리고 운영에 반영할까요?')">@csrf
                                <button class="btn btn-sm btn-danger">되돌리기</button>
                            </form>
                        @endif
                    </div>
                </td>
            </tr>
        @empty
            <tr><td colspan="7" class="empty">처리한 작업이 없습니다.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
{{ $tasks->links('manage.pagination') }}
@endsection
