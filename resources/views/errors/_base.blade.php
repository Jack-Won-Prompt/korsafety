{{--
    오류 페이지 공통 틀.
    500·503에서도 떠야 하므로 DB·세션·뷰 컴포저 값에 기대지 않고 스스로 완결된다.
    (설정 파일 값인 config('company.*')만 사용)
--}}
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>@yield('title') · KOR SAFETY</title>
    <link rel="icon" type="image/png" href="{{ asset('brand/favicon.png') }}">
    <style>
        *{box-sizing:border-box}
        body{margin:0;min-height:100vh;display:grid;place-items:center;background:#f7f8fa;
            font-family:"Pretendard","Apple SD Gothic Neo","Malgun Gothic",system-ui,sans-serif;
            color:#12151b;letter-spacing:-.02em;padding:24px}
        .box{text-align:center;max-width:480px;width:100%}
        .logo{height:40px;width:auto;margin:0 auto 30px;display:block}
        .ico{width:88px;height:88px;border-radius:24px;background:#fff1ec;color:#ff5722;
            display:grid;place-items:center;margin:0 auto 4px}
        .ico svg{width:44px;height:44px}
        .code{font-size:13px;font-weight:800;letter-spacing:.2em;color:#ff5722;margin-top:16px}
        .big{font-size:92px;font-weight:900;line-height:1;letter-spacing:-.05em;
            background:linear-gradient(120deg,#12151b,#ff5722);
            -webkit-background-clip:text;background-clip:text;color:transparent}
        h1{font-size:23px;margin:10px 0 10px}
        p{color:#6b7280;font-size:15px;line-height:1.65;margin:0 0 28px}
        .search{display:flex;gap:8px;margin:0 0 24px}
        .search input{flex:1 1 auto;height:50px;border:1.5px solid #e8e9ee;border-radius:999px;
            padding:0 20px;font-size:15px;font-family:inherit;background:#fff;min-width:0}
        .search input:focus{outline:none;border-color:#12151b}
        .search button{flex:0 0 auto;height:50px;padding:0 22px;border:0;border-radius:999px;
            background:#ff5722;color:#fff;font-weight:700;font-size:15px;font-family:inherit;cursor:pointer}
        .search button:hover{background:#e64514}
        .btns{display:flex;gap:10px;justify-content:center;flex-wrap:wrap}
        a.btn,button.btn{display:inline-flex;align-items:center;height:50px;padding:0 24px;
            border-radius:999px;font-weight:700;font-size:15px;text-decoration:none;
            transition:.15s;cursor:pointer;border:0;font-family:inherit}
        .primary{background:#12151b;color:#fff}.primary:hover{background:#000}
        .ghost{background:#fff;color:#12151b;border:1px solid #e8e9ee}.ghost:hover{border-color:#12151b}
        .help{margin-top:28px;font-size:13px;color:#9aa0aa;line-height:1.7}
        .help a{color:#6b7280;text-decoration:none;font-weight:600}
        .help a:hover{color:#12151b}
    </style>
</head>
<body>
    <div class="box">
        <a href="{{ url('/') }}">
            <img class="logo" src="{{ asset('brand/logo-wordmark.png') }}" alt="(주)한국안전 · 산업안전용품 전문몰">
        </a>

        @yield('figure')

        <h1>@yield('heading')</h1>
        <p>@yield('message')</p>

        @yield('actions')

        <div class="help">
            문의 · 고객센터 <a href="tel:{{ preg_replace('/[^0-9+]/', '', config('company.tel')) }}">{{ config('company.tel') }}</a>
            · <a href="mailto:{{ config('company.email') }}">{{ config('company.email') }}</a>
        </div>
    </div>
</body>
</html>
