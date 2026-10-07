{{-- 화면에서 난 웹스크립트 오류를 관리자 > 오류 관리로 보낸다 (한 페이지당 최대 5건) --}}
<script>
(function () {
    var ENDPOINT = {!! json_encode(url('/api/client-errors')) !!};
    var seen = {}, sent = 0, MAX = 5;

    function report(payload) {
        if (sent >= MAX) return;
        var message = (payload.message || '').slice(0, 2000);
        if (!message) return;
        // 다른 도메인 스크립트의 오류는 내용이 없어 도움이 되지 않는다
        if (message === 'Script error.' && !payload.file) return;
        if (/ResizeObserver loop/i.test(message)) return;

        var key = message + '|' + (payload.file || '') + '|' + (payload.line || 0);
        if (seen[key]) return;
        seen[key] = 1;
        sent++;

        var body = JSON.stringify({
            message: message,
            kind: payload.kind || 'Error',
            url: location.href.slice(0, 1000),
            file: (payload.file || '').slice(0, 500),
            line: payload.line || 0,
            stack: (payload.stack || '').slice(0, 20000)
        });

        try {
            if (navigator.sendBeacon && navigator.sendBeacon(ENDPOINT, new Blob([body], { type: 'application/json' }))) return;
            fetch(ENDPOINT, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: body, keepalive: true }).catch(function () {});
        } catch (e) { /* 오류 보고가 화면을 멈추게 하지 않는다 */ }
    }

    window.addEventListener('error', function (e) {
        if (!e) return;

        // 스크립트 · 스타일시트 로딩 실패 (이미지 깨짐은 양이 많아 제외)
        var el = e.target;
        if (el && el !== window && el.tagName) {
            var tag = el.tagName.toUpperCase();
            if (tag !== 'SCRIPT' && tag !== 'LINK') return;
            var src = el.src || el.href || '';
            if (!src) return;
            report({ kind: 'ResourceError', message: tag + ' 파일을 불러오지 못했습니다: ' + src, file: src, line: 0 });
            return;
        }

        report({
            kind: (e.error && e.error.name) || 'Error',
            message: e.message || '알 수 없는 스크립트 오류',
            file: e.filename || '',
            line: e.lineno || 0,
            stack: (e.error && e.error.stack) || ''
        });
    }, true);

    window.addEventListener('unhandledrejection', function (e) {
        var reason = e && e.reason;
        var message = '';
        try { message = (reason && (reason.message || String(reason))) || ''; } catch (x) {}
        report({
            kind: 'UnhandledRejection',
            message: message || '처리되지 않은 Promise 오류',
            file: '', line: 0,
            stack: (reason && reason.stack) || ''
        });
    });
})();
</script>
