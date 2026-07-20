<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>페이지를 찾을 수 없습니다 · KOR SAFETY</title>
    <style>
        *{box-sizing:border-box}
        body{margin:0;min-height:100vh;display:grid;place-items:center;background:#f7f8fa;
            font-family:"Pretendard","Apple SD Gothic Neo","Malgun Gothic",system-ui,sans-serif;color:#12151b;letter-spacing:-.02em;padding:24px}
        .box{text-align:center;max-width:460px}
        .mark{display:inline-flex;align-items:center;gap:9px;margin-bottom:34px}
        .mark .m{width:34px;height:34px;border-radius:9px;background:#12151b;color:#fff;display:grid;place-items:center;font-weight:900;font-size:14px}
        .mark b{font-weight:900;font-size:17px}.mark b span{color:#ff5722}
        .code{font-size:96px;font-weight:900;line-height:1;letter-spacing:-.05em;
            background:linear-gradient(120deg,#12151b,#ff5722);-webkit-background-clip:text;background-clip:text;color:transparent}
        h1{font-size:23px;margin:14px 0 10px}
        p{color:#6b7280;font-size:15px;line-height:1.6;margin:0 0 30px}
        .btns{display:flex;gap:10px;justify-content:center;flex-wrap:wrap}
        a.btn{display:inline-flex;align-items:center;height:50px;padding:0 24px;border-radius:999px;font-weight:700;font-size:15px;text-decoration:none;transition:.15s}
        .primary{background:#12151b;color:#fff}.primary:hover{background:#000}
        .ghost{background:#fff;color:#12151b;border:1px solid #e8e9ee}.ghost:hover{border-color:#12151b}
    </style>
</head>
<body>
    <div class="box">
        <div class="mark"><span class="m">KS</span><b>KOR<span>SAFETY</span></b></div>
        <div class="code">404</div>
        <h1>페이지를 찾을 수 없습니다</h1>
        <p>요청하신 페이지가 삭제되었거나 주소가 변경되었을 수 있습니다.<br>주소를 다시 확인해 주세요.</p>
        <div class="btns">
            <a class="btn primary" href="{{ url('/') }}">홈으로 가기</a>
            <a class="btn ghost" href="{{ url('/') }}">쇼핑 계속하기</a>
        </div>
    </div>
</body>
</html>
