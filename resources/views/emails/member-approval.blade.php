<!DOCTYPE html>
<html lang="ko">
<head><meta charset="utf-8"><title>회원 가입 처리 안내</title></head>
<body style="margin:0;padding:0;background:#f5f6f8;font-family:'Malgun Gothic','맑은 고딕',Apple SD Gothic Neo,sans-serif;color:#12151b">
    <div style="max-width:560px;margin:0 auto;padding:32px 20px">
        <div style="background:#fff;border-radius:14px;padding:34px 30px;border:1px solid #e8e9ee">
            <div style="font-size:13px;color:#6b7280;margin-bottom:6px">(주)한국안전 · 산업안전용품 전문몰</div>

            @if($approved)
                <h1 style="font-size:21px;margin:0 0 18px">{{ $user->name }}님, 가입이 승인되었습니다</h1>
                <p style="font-size:14.5px;line-height:1.75;color:#3a414d;margin:0 0 24px">
                    이제 <b>{{ $user->email }}</b> 로 로그인하여 이용하실 수 있습니다.
                    @if($user->isPartner())
                        협력사 회원으로 승인되어 <b>협력사 할인가</b>가 적용됩니다.
                    @endif
                </p>
                <div style="text-align:center;margin-bottom:24px">
                    <a href="{{ $loginUrl }}" style="display:inline-block;background:#ff5722;color:#fff;text-decoration:none;
                       font-weight:800;font-size:15px;padding:15px 34px;border-radius:999px">로그인하기</a>
                </div>
            @else
                <h1 style="font-size:21px;margin:0 0 18px">{{ $user->name }}님, 가입이 승인되지 않았습니다</h1>
                <p style="font-size:14.5px;line-height:1.75;color:#3a414d;margin:0 0 20px">
                    확인 결과 가입이 승인되지 않았습니다. 자세한 내용은 고객센터로 문의해 주세요.
                </p>
                @if($reason)
                    <div style="background:#fff1ec;border:1px solid #ffd3c4;border-radius:10px;padding:16px;
                                font-size:13.5px;color:#a33a16;line-height:1.7;margin-bottom:20px">
                        사유: {{ $reason }}
                    </div>
                @endif
            @endif

            <p style="font-size:12.5px;line-height:1.7;color:#9aa0a6;margin:0">
                문의: 고객센터 02-2273-9533
            </p>
        </div>
        <div style="text-align:center;font-size:12px;color:#9aa0a6;margin-top:18px">
            (주)한국안전 · 산업안전용품 전문몰
        </div>
    </div>
</body>
</html>
