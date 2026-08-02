@extends('manage.layout')
@section('title', 'SR '.$sr->sr_no)
@section('page', 'SR 상세')
@section('crumb', $sr->sr_no.' · '.$sr->category_label)
@section('actions')
    <a href="{{ route('manage.sr.index') }}" class="btn btn-sm">← 목록</a>
@endsection

@section('content')
<div class="grid-2">
    <div>
        {{-- 요청 본문 --}}
        <div class="panel">
            <div class="panel-h">
                <div>
                    <h2>{{ $sr->title }}</h2>
                    <div class="sub">{{ $sr->user->name ?? '-' }} · {{ optional($sr->created_at)->format('Y-m-d H:i') }}</div>
                </div>
                <span class="badge {{ $sr->status_badge }}">{{ $sr->status_label }}</span>
            </div>
            <div class="panel-b">
                <div style="white-space:pre-wrap;line-height:1.75;font-size:14px">{{ $sr->content }}</div>
            </div>
        </div>

        {{-- 답글 --}}
        <div class="panel">
            <div class="panel-h"><div><h2>답글</h2><div class="sub">{{ $sr->replies->count() }}건</div></div></div>
            <div class="panel-b">
                @forelse($sr->replies as $r)
                    <div style="padding:14px 16px;border-radius:12px;margin-bottom:12px;border:1px solid var(--line);
                                background:{{ $r->is_staff ? '#f4f7ff' : '#fafbfc' }}">
                        <div style="display:flex;gap:8px;align-items:center;margin-bottom:7px">
                            <b style="font-size:13.5px">{{ $r->user->name ?? '알 수 없음' }}</b>
                            @if($r->is_staff)<span class="badge hq">담당자</span>@else<span class="badge warn">요청자</span>@endif
                            <span class="t-sub" style="margin-left:auto">{{ $r->created_at->format('Y.m.d H:i') }}</span>
                        </div>
                        <div style="white-space:pre-wrap;line-height:1.7;font-size:13.5px">{{ $r->body }}</div>
                    </div>
                @empty
                    <div class="empty" style="padding:26px 0">아직 등록된 답글이 없습니다.</div>
                @endforelse

                @if($sr->is_closed)
                    <div class="t-sub" style="padding:14px;border:1px dashed var(--line);border-radius:10px;text-align:center">
                        종료된 SR입니다. ({{ optional($sr->closed_at)->format('Y-m-d H:i') }}) 추가 요청은 새 SR로 등록해 주세요.
                    </div>
                @else
                    <form action="{{ route('manage.sr.reply', $sr) }}" method="post" style="margin-top:6px">@csrf
                        <div class="form-row" style="margin-bottom:10px">
                            <label>{{ $isStaff ? '담당자 답변' : '추가 문의' }}</label>
                            <textarea class="input" name="body" rows="5" style="height:auto;padding:13px;line-height:1.7"
                                      placeholder="{{ $isStaff ? '처리 내용 · 안내 사항을 적어 주세요.' : '추가로 전달할 내용을 적어 주세요.' }}">{{ old('body') }}</textarea>
                        </div>
                        <button class="btn btn-accent btn-sm">답글 등록</button>
                    </form>
                @endif
            </div>
        </div>
    </div>

    <div>
        {{-- 처리 정보 · 상태 관리 --}}
        <div class="panel">
            <div class="panel-h"><h2>처리 정보</h2></div>
            <div class="panel-b">
                <div style="display:grid;gap:10px;font-size:13.5px">
                    <div><span class="t-sub">SR 번호</span> · <b>{{ $sr->sr_no }}</b></div>
                    <div><span class="t-sub">유형</span> · {{ $sr->category_label }}</div>
                    <div><span class="t-sub">중요도</span> · {{ $sr->priority_label }}</div>
                    <div><span class="t-sub">요청자</span> · {{ $sr->user->name ?? '-' }} ({{ $sr->requester_role ?: '-' }})</div>
                    <div><span class="t-sub">담당자</span> · {{ $sr->assignee->name ?? '미지정' }}</div>
                    <div><span class="t-sub">종료일시</span> · {{ optional($sr->closed_at)->format('Y-m-d H:i') ?: '-' }}</div>
                    <div><span class="t-sub">완료 안내메일</span> ·
                        @if($sr->resolved_notified_at)
                            <span class="badge ok">발송</span> <span class="t-sub">{{ $sr->resolved_notified_at->format('Y-m-d H:i') }} · {{ $sr->user->email ?? '-' }}</span>
                        @else
                            <span class="t-sub">미발송</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="panel">
            <div class="panel-h"><div><h2>상태 관리</h2><div class="sub">{{ $isStaff ? '담당 지정 · 처리 상태 변경' : '처리 완료 시 종료 처리할 수 있습니다.' }}</div></div></div>
            <div class="panel-b">
                @if($isStaff)
                    <form action="{{ route('manage.sr.status', $sr) }}" method="post" style="display:flex;gap:8px;margin-bottom:10px">@csrf
                        <select class="input" name="status" style="height:38px">
                            @foreach(\App\Models\ServiceRequest::STATUSES as $k => $v)
                                <option value="{{ $k }}" @selected($sr->status === $k)>{{ $v }}</option>
                            @endforeach
                        </select>
                        <button class="btn btn-sm btn-accent" style="flex:0 0 auto">변경</button>
                    </form>
                    <div class="hint" style="margin:-4px 0 12px">‘처리완료’로 변경하면 등록자에게 완료 안내 메일이 자동 발송됩니다.</div>
                    @if($sr->assignee_id !== auth()->id())
                        <form action="{{ route('manage.sr.assign', $sr) }}" method="post">@csrf
                            <button class="btn btn-sm" style="width:100%">내가 담당하기</button>
                        </form>
                    @endif
                @elseif(! $sr->is_closed)
                    <form action="{{ route('manage.sr.status', $sr) }}" method="post"
                          onsubmit="return confirm('이 SR을 종료할까요? 종료 후에는 답글을 달 수 없습니다.')">@csrf
                        <input type="hidden" name="status" value="closed">
                        <button class="btn btn-sm" style="width:100%">SR 종료하기</button>
                    </form>
                @else
                    <div class="t-sub">이미 종료된 SR입니다.</div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
