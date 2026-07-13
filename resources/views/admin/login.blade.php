<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>관리자 로그인 · KOR SAFETY</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard@v1.3.9/dist/web/variable/pretendardvariable.min.css">
    <style>
        *{box-sizing:border-box} body{margin:0;font-family:"Pretendard",-apple-system,"Malgun Gothic",sans-serif;
        background:#0e1117;color:#e8eaed;min-height:100vh;display:grid;place-items:center;letter-spacing:-.01em}
        .card{width:380px;max-width:92vw;background:#171b22;border:1px solid #262c36;border-radius:18px;padding:38px 34px;
        box-shadow:0 20px 60px rgba(0,0,0,.4)}
        .mark{width:44px;height:44px;border-radius:11px;background:#ff5722;display:grid;place-items:center;
        font-weight:900;color:#fff;font-size:17px;margin-bottom:22px}
        h1{font-size:21px;margin:0 0 6px;font-weight:800}
        p.sub{margin:0 0 26px;color:#8b93a1;font-size:14px}
        label{display:block;font-size:13px;font-weight:700;margin-bottom:8px;color:#b6bdc8}
        input{width:100%;height:48px;background:#0e1117;border:1.5px solid #2a313c;border-radius:11px;padding:0 16px;
        color:#fff;font-size:15px;font-family:inherit}
        input:focus{outline:none;border-color:#ff5722}
        button{width:100%;height:50px;margin-top:20px;background:#ff5722;color:#fff;border:0;border-radius:11px;
        font-weight:800;font-size:15px;cursor:pointer;font-family:inherit}
        button:hover{background:#e64514}
        .err{background:#2a1416;border:1px solid #5b2327;color:#ff9a9a;padding:11px 14px;border-radius:10px;font-size:13px;margin-bottom:18px}
        .back{display:block;text-align:center;margin-top:20px;color:#8b93a1;font-size:13px;text-decoration:none}
        .back:hover{color:#fff}
    </style>
</head>
<body>
    <form class="card" action="{{ route('admin.login.post') }}" method="post">
        @csrf
        <div class="mark">KS</div>
        <h1>관리자 페이지</h1>
        <p class="sub">KOR SAFETY 쇼핑몰 관리</p>
        @if($errors->any())
            <div class="err">{{ $errors->first() }}</div>
        @endif
        <label for="pw">비밀번호</label>
        <input id="pw" type="password" name="password" autofocus autocomplete="current-password" placeholder="관리자 비밀번호 입력">
        <button type="submit">로그인</button>
        <a class="back" href="{{ route('home') }}">← 쇼핑몰로 돌아가기</a>
    </form>
</body>
</html>
