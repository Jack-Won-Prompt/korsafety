<!DOCTYPE html>
<html lang="ko">
<head><meta charset="utf-8"><title>회원가입 인증번호</title></head>
<body style="margin:0;padding:0;background:#f5f6f8;font-family:'Malgun Gothic','맑은 고딕',Apple SD Gothic Neo,sans-serif;color:#12151b">
    <div style="max-width:520px;margin:0 auto;padding:32px 20px">
        <div style="background:#fff;border-radius:14px;padding:34px 30px;border:1px solid #e8e9ee">
            <div style="font-size:13px;color:#6b7280;margin-bottom:6px">(주)한국안전 · 산업안전용품 전문몰</div>
            <h1 style="font-size:21px;margin:0 0 18px">회원가입 인증번호</h1>
            <p style="font-size:14.5px;line-height:1.7;color:#3a414d;margin:0 0 22px">
                아래 인증번호를 가입 화면에 입력해 주세요.
            </p>

            <div style="background:#f7f8fa;border:1px solid #e8e9ee;border-radius:12px;padding:22px;text-align:center;margin-bottom:22px">
                <div style="font-size:34px;font-weight:800;letter-spacing:8px;color:#ff5722">{{ $code }}</div>
                <div style="font-size:12.5px;color:#6b7280;margin-top:10px">유효 시간 {{ $minutes }}분</div>
            </div>

            <p style="font-size:13px;line-height:1.7;color:#6b7280;margin:0">
                본인이 요청하지 않았다면 이 메일을 무시해 주세요.<br>
                인증번호는 다른 사람에게 알려주지 마세요.
            </p>
        </div>
        <div style="text-align:center;font-size:12px;color:#9aa0a6;margin-top:18px">
            (주)한국안전 · 고객센터 02-2273-9533
        </div>
    </div>
</body>
</html>
