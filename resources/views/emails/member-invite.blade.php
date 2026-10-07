<!DOCTYPE html>
<html lang="ko">
<head><meta charset="utf-8"><title>회원 가입 안내</title></head>
<body style="margin:0;padding:0;background:#f5f6f8;font-family:'Malgun Gothic','맑은 고딕',Apple SD Gothic Neo,sans-serif;color:#12151b">
    <div style="max-width:560px;margin:0 auto;padding:32px 20px">
        <div style="background:#fff;border-radius:14px;padding:34px 30px;border:1px solid #e8e9ee">
            <div style="font-size:13px;color:#6b7280;margin-bottom:6px">(주)한국안전 · 산업안전용품 전문몰</div>
            <h1 style="font-size:21px;margin:0 0 18px">
                @if($invite->company_name){{ $invite->company_name }} @endif
                @if($invite->name){{ $invite->name }}님, @endif
                회원 가입을 안내드립니다
            </h1>

            <p style="font-size:14.5px;line-height:1.75;color:#3a414d;margin:0 0 24px">
                아래 버튼을 눌러 가입 정보를 입력해 주세요.
                @if($invite->role === 'partner')
                    사업자등록증 확인 후 승인되면 <b>협력사 할인가</b>로 구매하실 수 있습니다.
                @else
                    가입 후 담당자 확인을 거쳐 이용하실 수 있습니다.
                @endif
            </p>

            <div style="text-align:center;margin-bottom:24px">
                <a href="{{ $link }}" style="display:inline-block;background:#ff5722;color:#fff;text-decoration:none;
                   font-weight:800;font-size:15px;padding:15px 34px;border-radius:999px">가입하기</a>
            </div>

            <div style="background:#f7f8fa;border-radius:10px;padding:16px;font-size:13px;color:#6b7280;line-height:1.7">
                · 가입 구분: <b style="color:#12151b">{{ $invite->role_label }}</b><br>
                · 가입할 이메일: <b style="color:#12151b">{{ $invite->email }}</b><br>
                · 링크 유효 기간: {{ optional($invite->expires_at)->format('Y년 m월 d일') }}까지
            </div>

            <p style="font-size:12.5px;line-height:1.7;color:#9aa0a6;margin:20px 0 0;word-break:break-all">
                버튼이 눌리지 않으면 아래 주소를 주소창에 붙여넣어 주세요.<br>{{ $link }}
            </p>
        </div>
        <div style="text-align:center;font-size:12px;color:#9aa0a6;margin-top:18px">
            (주)한국안전 · 고객센터 02-2273-9533
        </div>
    </div>
</body>
</html>
