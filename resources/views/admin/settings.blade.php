@extends('manage.layout')
@section('title', '사이트 설정')
@section('page', '사이트 설정')
@section('crumb', '메인 화면 및 노출 옵션')

@section('content')
<form action="{{ route('admin.settings.update') }}" method="post">
    @csrf
    <div class="panel">
        <div class="panel-h"><div><h2>메인 화면 설정</h2></div></div>
        <div class="panel-b">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:24px">
                <div>
                    <div style="font-weight:700;font-size:15px">메인 카테고리 영역 표시</div>
                    <div class="hint" style="margin-top:6px">메인 슬라이드 아래의 카테고리 바로가기 타일 영역을 표시하거나 숨깁니다.</div>
                </div>
                <label class="switch">
                    <input type="checkbox" name="home_show_categories" value="1" {{ $settings['home_show_categories'] ? 'checked' : '' }}>
                    <span class="slider"></span>
                </label>
            </div>
        </div>
    </div>
    <button class="btn btn-accent" type="submit">설정 저장</button>
</form>
@endsection
