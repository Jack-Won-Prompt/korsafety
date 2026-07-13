<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>관리자 · KOR SAFETY</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard@v1.3.9/dist/web/variable/pretendardvariable.min.css">
    <style>
        :root{--bg:#0e1117;--panel:#171b22;--line:#262c36;--muted:#8b93a1;--accent:#ff5722}
        *{box-sizing:border-box}
        body{margin:0;font-family:"Pretendard",-apple-system,"Malgun Gothic",sans-serif;background:var(--bg);
        color:#e8eaed;letter-spacing:-.01em}
        a{color:inherit;text-decoration:none}
        .topbar{height:64px;border-bottom:1px solid var(--line);display:flex;align-items:center;
        justify-content:space-between;padding:0 26px;position:sticky;top:0;background:rgba(14,17,23,.9);backdrop-filter:blur(8px);z-index:5}
        .brand{display:flex;align-items:center;gap:11px;font-weight:900;font-size:16px}
        .brand .m{width:34px;height:34px;border-radius:9px;background:var(--accent);display:grid;place-items:center;color:#fff;font-size:14px}
        .brand small{display:block;color:var(--muted);font-size:10px;font-weight:700;letter-spacing:.14em}
        .tb-right{display:flex;gap:10px;align-items:center}
        .btn{height:40px;padding:0 16px;border-radius:9px;border:1px solid var(--line);background:transparent;
        color:#e8eaed;font-weight:700;font-size:13.5px;cursor:pointer;font-family:inherit;display:inline-flex;align-items:center;gap:7px}
        .btn:hover{border-color:#3a424f}
        .btn-accent{background:var(--accent);border-color:var(--accent);color:#fff}
        .btn-accent:hover{background:#e64514}
        .wrap{max-width:960px;margin:0 auto;padding:36px 26px 80px}
        h1.page{font-size:24px;font-weight:800;margin:0 0 4px}
        .page-sub{color:var(--muted);font-size:14px;margin:0 0 30px}
        .stats{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:34px}
        .stat{background:var(--panel);border:1px solid var(--line);border-radius:14px;padding:22px}
        .stat .n{font-size:30px;font-weight:900}
        .stat .l{color:var(--muted);font-size:13px;margin-top:4px}
        .panel{background:var(--panel);border:1px solid var(--line);border-radius:16px;padding:8px 26px}
        .panel h2{font-size:16px;font-weight:800;padding:20px 0 4px;margin:0}
        .row{display:flex;align-items:center;justify-content:space-between;gap:20px;padding:22px 0;border-top:1px solid var(--line)}
        .row:first-of-type{border-top:0}
        .row .info{max-width:70%}
        .row .info .t{font-weight:700;font-size:15px}
        .row .info .d{color:var(--muted);font-size:13px;margin-top:4px;line-height:1.5}
        /* toggle */
        .switch{position:relative;display:inline-block;width:54px;height:30px;flex:0 0 auto}
        .switch input{opacity:0;width:0;height:0}
        .slider{position:absolute;inset:0;background:#333b47;border-radius:999px;transition:.2s;cursor:pointer}
        .slider:before{content:"";position:absolute;width:22px;height:22px;left:4px;top:4px;background:#fff;border-radius:50%;transition:.2s}
        .switch input:checked + .slider{background:var(--accent)}
        .switch input:checked + .slider:before{transform:translateX(24px)}
        .save{margin-top:22px;display:flex;justify-content:flex-end}
        .toast{background:#12251a;border:1px solid #1f5b38;color:#8ff0b6;padding:12px 16px;border-radius:11px;
        font-size:13.5px;font-weight:600;margin-bottom:22px}
    </style>
</head>
<body>
    <div class="topbar">
        <a href="{{ route('admin.index') }}" class="brand">
            <span class="m">KS</span>
            <span>KOR SAFETY <small>ADMIN CONSOLE</small></span>
        </a>
        <div class="tb-right">
            <a href="{{ route('home') }}" target="_blank" class="btn">쇼핑몰 보기 ↗</a>
            <form action="{{ route('admin.logout') }}" method="post">@csrf
                <button class="btn" type="submit">로그아웃</button>
            </form>
        </div>
    </div>

    <div class="wrap">
        <h1 class="page">대시보드</h1>
        <p class="page-sub">쇼핑몰 콘텐츠와 노출 옵션을 관리합니다.</p>

        @if(session('status'))
            <div class="toast">✓ {{ session('status') }}</div>
        @endif

        <div class="stats">
            <div class="stat"><div class="n">{{ number_format($stats['products']) }}</div><div class="l">등록 상품</div></div>
            <div class="stat"><div class="n">{{ number_format($stats['categories']) }}</div><div class="l">카테고리</div></div>
            <div class="stat"><div class="n">{{ number_format($stats['soldout']) }}</div><div class="l">품절 상품</div></div>
        </div>

        <form action="{{ route('admin.settings.update') }}" method="post">
            @csrf
            <div class="panel">
                <h2>메인 화면 설정</h2>
                <div class="row">
                    <div class="info">
                        <div class="t">메인 카테고리 영역 표시</div>
                        <div class="d">메인 화면 슬라이드(히어로) 아래에 있는 카테고리 바로가기 타일 영역을 표시하거나 숨깁니다. 끄면 슬라이드 바로 아래에 베스트셀러가 나옵니다.</div>
                    </div>
                    <label class="switch">
                        <input type="checkbox" name="home_show_categories" value="1" {{ $settings['home_show_categories'] ? 'checked' : '' }}>
                        <span class="slider"></span>
                    </label>
                </div>
            </div>
            <div class="save">
                <button class="btn btn-accent" type="submit">설정 저장</button>
            </div>
        </form>
    </div>
</body>
</html>
