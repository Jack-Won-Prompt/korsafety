<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>일시적인 오류가 발생했습니다 · KOR SAFETY</title>
    <style>
        *{box-sizing:border-box}
        body{margin:0;min-height:100vh;display:grid;place-items:center;background:#f7f8fa;
            font-family:"Pretendard","Apple SD Gothic Neo","Malgun Gothic",system-ui,sans-serif;color:#12151b;letter-spacing:-.02em;padding:24px}
        .box{text-align:center;max-width:460px}
        .mark{display:inline-flex;align-items:center;gap:9px;margin-bottom:34px}
        .mark .m{width:34px;height:34px;border-radius:9px;background:#12151b;color:#fff;display:grid;place-items:center;font-weight:900;font-size:14px}
        .mark b{font-weight:900;font-size:17px}.mark b span{color:#ff5722}
        .ico{width:90px;height:90px;border-radius:24px;background:#fff1ec;color:#ff5722;display:grid;place-items:center;margin:0 auto 8px}
        .ico svg{width:46px;height:46px}
        .code{font-size:14px;font-weight:800;letter-spacing:.2em;color:#ff5722;margin-top:14px}
        h1{font-size:23px;margin:8px 0 10px}
        p{color:#6b7280;font-size:15px;line-height:1.6;margin:0 0 30px}
        .btns{display:flex;gap:10px;justify-content:center;flex-wrap:wrap}
        a.btn,button.btn{display:inline-flex;align-items:center;height:50px;padding:0 24px;border-radius:999px;font-weight:700;font-size:15px;text-decoration:none;transition:.15s;cursor:pointer;border:0}
        .primary{background:#12151b;color:#fff}.primary:hover{background:#000}
        .ghost{background:#fff;color:#12151b;border:1px solid #e8e9ee}.ghost:hover{border-color:#12151b}
    </style>
</head>
<body>
    <div class="box">
        <img src="{{ asset('brand/logo-light.png') }}" alt="KOR SAFETY" style="height:40px;margin-bottom:30px">
        <div class="ico">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><path d="M12 9v4M12 17h.01"/></svg>
        </div>
        <div class="code">ERROR 500</div>
        <h1>일시적인 오류가 발생했습니다</h1>
        <p>서비스 처리 중 문제가 발생했습니다.<br>잠시 후 다시 시도해 주세요. 문제가 계속되면 고객센터(1588-0000)로 문의해 주세요.</p>
        <div class="btns">
            <a class="btn primary" href="{{ url('/') }}">홈으로 가기</a>
            <button class="btn ghost" onclick="location.reload()">다시 시도</button>
        </div>
    </div>
</body>
</html>
