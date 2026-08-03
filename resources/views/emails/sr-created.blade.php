@php
    $c = config('company');
    $urgent = in_array($sr->priority, ['high', 'urgent'], true);
@endphp
<div style="max-width:560px;margin:0 auto;font-family:'Malgun Gothic',sans-serif;color:#222">
    <div style="background:#12151b;color:#fff;padding:26px 28px;border-radius:14px 14px 0 0">
        <div style="font-size:12px;letter-spacing:.15em;color:#ffab91">KOR SAFETY · SERVICE REQUEST</div>
        <div style="font-size:20px;font-weight:800;margin-top:6px">새 SR이 접수되었습니다</div>
    </div>
    <div style="border:1px solid #e8e9ee;border-top:0;border-radius:0 0 14px 14px;padding:26px 28px">
        <p style="font-size:14px;line-height:1.7;margin:0 0 18px">
            <b>{{ $sr->user->name ?? '알 수 없음' }}</b>님이 SR <b>{{ $sr->sr_no }}</b>을(를) 등록했습니다.
            @if($urgent)<br><b style="color:#c62828">중요도 {{ $sr->priority_label }} — 우선 확인이 필요합니다.</b>@endif
        </p>

        <table style="width:100%;border-collapse:collapse;font-size:13.5px">
            <tr><td style="padding:8px 0;color:#6b7280;width:96px">SR 번호</td><td style="padding:8px 0;font-weight:700">{{ $sr->sr_no }}</td></tr>
            <tr><td style="padding:8px 0;color:#6b7280">제목</td><td style="padding:8px 0;font-weight:700">{{ $sr->title }}</td></tr>
            <tr><td style="padding:8px 0;color:#6b7280">유형</td><td style="padding:8px 0">{{ $sr->category_label }}</td></tr>
            <tr><td style="padding:8px 0;color:#6b7280">중요도</td>
                <td style="padding:8px 0;{{ $urgent ? 'font-weight:800;color:#c62828' : '' }}">{{ $sr->priority_label }}</td></tr>
            <tr><td style="padding:8px 0;color:#6b7280">요청자</td>
                <td style="padding:8px 0">{{ $sr->user->name ?? '-' }} ({{ $sr->requester_role ?: '-' }}) · {{ $sr->user->email ?? '-' }}</td></tr>
            <tr><td style="padding:8px 0;color:#6b7280;border-top:1px solid #eee">접수일시</td>
                <td style="padding:8px 0;border-top:1px solid #eee">{{ optional($sr->created_at)->format('Y-m-d H:i') }}</td></tr>
        </table>

        <div style="margin-top:20px;padding:16px 18px;background:#fafbfc;border:1px solid #e8e9ee;border-radius:10px">
            <div style="font-size:12px;color:#6b7280;font-weight:700;margin-bottom:8px">요청 내용</div>
            <div style="font-size:13.5px;line-height:1.7;white-space:pre-wrap">{{ $sr->content }}</div>
        </div>

        <p style="margin:22px 0 0">
            <a href="{{ route('manage.sr.show', $sr) }}"
               style="display:inline-block;background:#ff5722;color:#fff;text-decoration:none;font-weight:700;
                      font-size:13.5px;padding:12px 22px;border-radius:9px">관리 콘솔에서 처리하기</a>
        </p>
        <p style="font-size:13px;color:#6b7280;line-height:1.7;margin:20px 0 0">
            · 답글을 등록하면 처리중으로 바뀌고, 처리완료로 변경하면 요청자에게 안내 메일이 발송됩니다.<br>
            · 문의: TEL {{ $c['tel'] }} · {{ $c['email'] }}
        </p>
    </div>
    <div style="text-align:center;color:#9aa0aa;font-size:11px;padding:16px">
        © {{ date('Y') }} {{ $c['name'] }}. All rights reserved.
    </div>
</div>
