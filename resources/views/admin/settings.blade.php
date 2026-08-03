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
        <div class="panel-h"><div><h2>연락처 배너 (모바일 앱 · 웹)</h2><div class="sub">앱 홈·상품 상세와 쇼핑몰 홈·상품 상세에 함께 적용됩니다</div></div></div>
        <div class="panel-b">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:24px">
                <div>
                    <div style="font-weight:700;font-size:15px">연락처 배너 표시</div>
                    <div class="hint" style="margin-top:6px">앱 홈·상품 상세와 쇼핑몰 홈·상품 상세에 표시됩니다. 탭(클릭)하면 바로 전화가 걸립니다.</div>
                </div>
                <label class="switch">
                    <input type="checkbox" name="contact_banner_enabled" value="1" {{ $settings['contact_banner_enabled'] ? 'checked' : '' }}>
                    <span class="slider"></span>
                </label>
            </div>
            <div style="margin-top:18px">
                <label style="font-weight:700;font-size:14px;display:block;margin-bottom:8px">배너 문구</label>
                <input class="input" type="text" name="contact_banner_text" maxlength="60"
                       value="{{ old('contact_banner_text', $settings['contact_banner_text']) }}"
                       placeholder="안전제품 관련 제작 및 제품문의">
            </div>
            <div style="margin-top:14px">
                <label style="font-weight:700;font-size:14px;display:block;margin-bottom:8px">연락처</label>
                <input class="input" type="text" name="contact_banner_phone" maxlength="30"
                       value="{{ old('contact_banner_phone', $settings['contact_banner_phone']) }}"
                       placeholder="02-2273-9533">
                <div class="hint" style="margin-top:6px">예시 표시 — <b>{{ $settings['contact_banner_text'] }}</b> · {{ $settings['contact_banner_phone'] }}</div>
            </div>
        </div>
    </div>

    <div class="panel">
        <div class="panel-h"><div><h2>SR 알림</h2><div class="sub">서비스 요청이 접수되면 담당자에게 메일로 알립니다</div></div></div>
        <div class="panel-b">
            <label style="font-weight:700;font-size:14px;display:block;margin-bottom:8px">접수 알림 수신 주소</label>
            <input class="input" type="text" name="sr_notify_email" maxlength="300"
                   value="{{ old('sr_notify_email', $settings['sr_notify_email']) }}"
                   placeholder="jack@withworks.co.kr">
            <div class="hint" style="margin-top:6px">
                쉼표(,)로 여러 명을 지정할 수 있습니다. 비워 두면 기본값 {{ \App\Models\Setting::DEFAULTS['sr_notify_email'] }} 으로 되돌아갑니다.<br>
                처리완료로 상태를 바꿀 때 보내는 안내 메일은 SR 등록자 본인에게 갑니다.
            </div>
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
