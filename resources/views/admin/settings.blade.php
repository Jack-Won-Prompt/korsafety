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

    <div class="panel">
        <div class="panel-h"><div><h2>가격 표시 설정</h2></div></div>
        <div class="panel-b">
            <div style="margin-bottom:14px">
                <div style="font-weight:700;font-size:15px">상품 가격 노출 방식</div>
                <div class="hint" style="margin-top:6px">전체 상품에 일괄 적용됩니다. '가격 문의'로 설정하면 판매가 대신 문의 안내가 표시됩니다.</div>
            </div>
            <label style="display:flex;align-items:flex-start;gap:10px;padding:12px 14px;border:1px solid var(--line);border-radius:10px;margin-bottom:10px;cursor:pointer">
                <input type="radio" name="price_display_mode" value="ask" {{ $settings['price_display_mode'] !== 'price' ? 'checked' : '' }} style="margin-top:3px">
                <span><b>가격 문의</b><br><span class="hint">판매가를 숨기고 '가격 문의' 안내를 표시합니다. (기본값)</span></span>
            </label>
            <label style="display:flex;align-items:flex-start;gap:10px;padding:12px 14px;border:1px solid var(--line);border-radius:10px;cursor:pointer">
                <input type="radio" name="price_display_mode" value="price" {{ $settings['price_display_mode'] === 'price' ? 'checked' : '' }} style="margin-top:3px">
                <span><b>제품 가격 노출</b><br><span class="hint">등록된 판매가(할인가 포함)를 상품 목록·상세에 표시합니다.</span></span>
            </label>
        </div>
    </div>

    <div class="panel">
        <div class="panel-h"><div><h2>유지보수 모드</h2></div></div>
        <div class="panel-b">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:24px">
                <div>
                    <div style="font-weight:700;font-size:15px">유지보수 모드 사용</div>
                    <div class="hint" style="margin-top:6px">체크하면 메인 화면의 상단 카테고리 아래 영역이 안내 문구 섹션으로 대체됩니다. (헤더·카테고리 메뉴·푸터는 그대로 노출)</div>
                </div>
                <label class="switch">
                    <input type="checkbox" name="maintenance_mode" value="1" {{ $settings['maintenance_mode'] ? 'checked' : '' }}>
                    <span class="slider"></span>
                </label>
            </div>
            <div style="margin-top:18px">
                <label style="font-weight:700;font-size:14px;display:block;margin-bottom:8px">안내 문구</label>
                <input class="input" type="text" name="maintenance_message" maxlength="200"
                       value="{{ old('maintenance_message', $settings['maintenance_message']) }}"
                       placeholder="더 좋은 서비스를 위해서 준비중에 있습니다.">
                <div class="hint" style="margin-top:6px">비워 두면 기본 문구가 사용됩니다.</div>
            </div>
        </div>
    </div>

    <button class="btn btn-accent" type="submit">설정 저장</button>
</form>
@endsection
