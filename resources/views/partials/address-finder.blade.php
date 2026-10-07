{{-- 우편번호 검색 (다음 우편번호 서비스) — 가입 화면 공통. 팝업 차단을 피하려고 화면 안 레이어로 띄운다 --}}
@once
<div class="addr-layer" id="addrLayer" hidden>
    <div class="addr-layer-dim" data-addr-close></div>
    <div class="addr-layer-box" role="dialog" aria-modal="true" aria-label="주소 검색">
        <div class="addr-layer-head">
            <b>주소 검색</b>
            <button type="button" class="addr-layer-close" data-addr-close aria-label="닫기">✕</button>
        </div>
        <div class="addr-layer-body" id="addrLayerBody"></div>
    </div>
</div>

@push('scripts')
<script src="//t1.daumcdn.net/mapjsapi/bundle/postcode/prod/postcode.v2.js"></script>
<script>
(function () {
    var layer = document.getElementById('addrLayer');
    var body = document.getElementById('addrLayerBody');
    var prefix = null;

    // 닫을 때는 숨기기만 한다. 내용을 바로 비우면 우편번호 스크립트가 스스로 정리하다 오류를 낸다
    // (다음 검색을 열 때 비운다)
    function close() {
        layer.hidden = true;
        document.body.style.overflow = '';
    }

    document.addEventListener('click', function (e) {
        if (e.target.closest('[data-addr-close]')) { close(); return; }

        var btn = e.target.closest('[data-addr-find]');
        if (!btn) return;
        e.preventDefault();

        if (typeof daum === 'undefined' || !daum.Postcode) {
            alert('주소 검색을 불러오지 못했습니다. 잠시 후 다시 시도하거나 직접 입력해 주세요.');
            return;
        }

        prefix = btn.getAttribute('data-addr-find');
        body.innerHTML = '';
        layer.hidden = false;
        document.body.style.overflow = 'hidden';

        new daum.Postcode({
            oncomplete: function (data) {
                var road = data.roadAddress || data.jibunAddress || '';
                var extra = '';
                if (data.bname && /[동|로|가]$/g.test(data.bname)) extra += data.bname;
                if (data.buildingName && data.apartment === 'Y') extra += (extra ? ', ' : '') + data.buildingName;
                if (extra) road += ' (' + extra + ')';

                var zip = document.getElementById(prefix + '_postcode');
                var addr = document.getElementById(prefix + '_address1');
                var detail = document.getElementById(prefix + '_address2');
                if (zip) zip.value = data.zonecode || '';
                if (addr) addr.value = road;
                close();
                if (detail) detail.focus();
            },
            onresize: function (size) { body.style.height = size.height + 'px'; },
            width: '100%',
            height: '100%'
        }).embed(body);
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !layer.hidden) close();
    });
})();
</script>
@endpush
@endonce
