@php
    $c = config('company');
    $lastStaffReply = $sr->replies->where('is_staff', true)->last();
@endphp
<div style="max-width:560px;margin:0 auto;font-family:'Malgun Gothic',sans-serif;color:#222">
    <div style="background:#12151b;color:#fff;padding:26px 28px;border-radius:14px 14px 0 0">
        <div style="font-size:12px;letter-spacing:.15em;color:#ffab91">KOR SAFETY · SERVICE REQUEST</div>
        <div style="font-size:20px;font-weight:800;margin-top:6px">SR 처리완료 안내</div>
    </div>
    <div style="border:1px solid #e8e9ee;border-top:0;border-radius:0 0 14px 14px;padding:26px 28px">
        <p style="font-size:14px;line-height:1.7;margin:0 0 18px">
            안녕하세요, <b>{{ $sr->user->name ?? '고객' }}</b>님.<br>
            요청하신 <b>{{ $sr->sr_no }}</b> 건의 처리가 <b style="color:#12703a">완료</b>되었습니다.
        </p>
        <table style="width:100%;border-collapse:collapse;font-size:13.5px">
            <tr><td style="padding:8px 0;color:#6b7280;width:96px">SR 번호</td><td style="padding:8px 0;font-weight:700">{{ $sr->sr_no }}</td></tr>
            <tr><td style="padding:8px 0;color:#6b7280">제목</td><td style="padding:8px 0;font-weight:700">{{ $sr->title }}</td></tr>
            <tr><td style="padding:8px 0;color:#6b7280">유형</td><td style="padding:8px 0">{{ $sr->category_label }} · 중요도 {{ $sr->priority_label }}</td></tr>
            <tr><td style="padding:8px 0;color:#6b7280">접수일</td><td style="padding:8px 0">{{ optional($sr->created_at)->format('Y-m-d H:i') }}</td></tr>
            <tr><td style="padding:8px 0;color:#6b7280">담당자</td><td style="padding:8px 0">{{ $sr->assignee->name ?? '본사 담당자' }}</td></tr>
            <tr><td style="padding:8px 0;color:#6b7280;border-top:1px solid #eee">처리 상태</td><td style="padding:8px 0;border-top:1px solid #eee;font-weight:800;color:#12703a">{{ $sr->status_label }}</td></tr>
        </table>

        @if($lastStaffReply)
            <div style="margin-top:20px;padding:16px 18px;background:#f4f7ff;border:1px solid #e0e6f5;border-radius:10px">
                <div style="font-size:12px;color:#6b7280;font-weight:700;margin-bottom:8px">담당자 처리 내용</div>
                <div style="font-size:13.5px;line-height:1.7;white-space:pre-wrap">{{ $lastStaffReply->body }}</div>
            </div>
        @endif

        <p style="margin:22px 0 0">
            <a href="{{ route('manage.sr.show', $sr) }}"
               style="display:inline-block;background:#ff5722;color:#fff;text-decoration:none;font-weight:700;
                      font-size:13.5px;padding:12px 22px;border-radius:9px">관리 콘솔에서 확인하기</a>
        </p>
        <p style="font-size:13px;color:#6b7280;line-height:1.7;margin:20px 0 0">
            · 처리 내용에 문제가 있으면 해당 SR에 답글로 알려주세요.<br>
            · 문의: TEL {{ $c['tel'] }} · {{ $c['email'] }}
        </p>
    </div>
    <div style="text-align:center;color:#9aa0aa;font-size:11px;padding:16px">
        © {{ date('Y') }} {{ $c['name'] }}. All rights reserved.
    </div>
</div>
