/* KOR SAFETY 실시간 채팅 문의 위젯 (Pusher + 폴링 폴백) */
(function () {
    'use strict';
    var root = document.getElementById('ks-chat');
    if (!root) return;

    var csrf = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
    var startUrl = root.dataset.start;
    var pusherKey = root.dataset.pusherKey;
    var pusherCluster = root.dataset.pusherCluster;
    var LS = 'ks_inquiry';

    var fab = root.querySelector('.ks-chat-fab');
    var panel = root.querySelector('.ks-chat-panel');
    var minBtn = root.querySelector('.ks-chat-min');
    var regForm = root.querySelector('.ks-chat-reg');
    var regErr = root.querySelector('.ks-chat-err');
    var thread = root.querySelector('.ks-chat-thread');
    var msgs = root.querySelector('.ks-chat-msgs');
    var sendForm = root.querySelector('.ks-chat-send');
    var sendInput = sendForm.querySelector('textarea');

    var token = null;          // 대화방 토큰
    var lastId = 0;            // 마지막으로 렌더된 메시지 id
    var seen = {};            // 중복 방지
    var pollTimer = null;
    var pusher = null, channel = null;

    /* ---------- 유틸 ---------- */
    function req(url, opts) {
        opts = opts || {};
        opts.headers = Object.assign({
            'X-CSRF-TOKEN': csrf,
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }, opts.headers || {});
        return fetch(url, opts);
    }
    function esc(s) {
        var d = document.createElement('div'); d.textContent = s; return d.innerHTML;
    }
    function fmtTime(iso) {
        try {
            var d = new Date(iso);
            var h = d.getHours(), m = d.getMinutes();
            var ap = h < 12 ? '오전' : '오후';
            h = h % 12; if (h === 0) h = 12;
            return ap + ' ' + h + ':' + (m < 10 ? '0' + m : m);
        } catch (e) { return ''; }
    }

    /* ---------- 렌더 ---------- */
    function addMessage(m) {
        if (!m || seen[m.id]) return;
        seen[m.id] = true;
        if (m.id > lastId) lastId = m.id;

        var wrap = document.createElement('div');
        wrap.className = 'ks-msg ' + (m.sender === 'admin' ? 'in' : 'out');
        wrap.innerHTML = '<div class="ks-bubble">' + esc(m.body).replace(/\n/g, '<br>') + '</div>' +
            '<div class="ks-time">' + fmtTime(m.created_at) + '</div>';
        msgs.appendChild(wrap);
        msgs.scrollTop = msgs.scrollHeight;
    }

    /* ---------- 대화 시작(등록 후 or 재방문) ---------- */
    function openThread() {
        regForm.hidden = true;
        thread.hidden = false;
        loadHistory();
        connectRealtime();
        setTimeout(function () { sendInput.focus(); }, 100);
    }

    function loadHistory() {
        req('/inquiry/' + token + '/poll?after=0').then(function (r) { return r.json(); })
            .then(function (data) {
                (data.messages || []).forEach(addMessage);
            }).catch(function () {});
    }

    /* ---------- 실시간(Pusher) 또는 폴링 ---------- */
    function connectRealtime() {
        if (pusherKey && window.Pusher) {
            try {
                pusher = new Pusher(pusherKey, { cluster: pusherCluster, forceTLS: true });
                channel = pusher.subscribe('inquiry.' + token);
                channel.bind('message.sent', function (data) { addMessage(data); });
                return; // 실시간 연결 성공 → 폴링 불필요
            } catch (e) { /* 실패 시 폴링으로 폴백 */ }
        }
        startPolling();
    }
    function startPolling() {
        if (pollTimer) return;
        pollTimer = setInterval(function () {
            if (document.hidden) return;
            req('/inquiry/' + token + '/poll?after=' + lastId).then(function (r) { return r.json(); })
                .then(function (data) { (data.messages || []).forEach(addMessage); })
                .catch(function () {});
        }, 3000);
    }

    /* ---------- 등록 제출 ---------- */
    regForm.addEventListener('submit', function (e) {
        e.preventDefault();
        regErr.hidden = true;
        var btn = regForm.querySelector('.ks-chat-submit');
        btn.disabled = true; btn.textContent = '연결 중...';
        var fd = new FormData(regForm);
        req(startUrl, { method: 'POST', body: fd }).then(function (r) {
            return r.json().then(function (d) { return { ok: r.ok, d: d }; });
        }).then(function (res) {
            btn.disabled = false; btn.textContent = '상담 시작하기';
            if (!res.ok) {
                var msg = res.d && res.d.errors ? Object.values(res.d.errors)[0][0] : (res.d.message || '오류가 발생했습니다.');
                regErr.textContent = msg; regErr.hidden = false; return;
            }
            token = res.d.token;
            localStorage.setItem(LS, JSON.stringify({ token: token, name: res.d.name }));
            openThread();
        }).catch(function () {
            btn.disabled = false; btn.textContent = '상담 시작하기';
            regErr.textContent = '연결에 실패했습니다. 잠시 후 다시 시도해 주세요.'; regErr.hidden = false;
        });
    });

    /* ---------- 메시지 전송 ---------- */
    sendForm.addEventListener('submit', function (e) {
        e.preventDefault();
        var body = sendInput.value.trim();
        if (!body || !token) return;
        sendInput.value = ''; autoGrow();
        var fd = new FormData(); fd.append('body', body);
        req('/inquiry/' + token + '/message', { method: 'POST', body: fd })
            .then(function (r) { return r.json(); })
            .then(function (m) { addMessage(m); })
            .catch(function () {});
    });
    // Enter 전송 / Shift+Enter 줄바꿈
    sendInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendForm.requestSubmit(); }
    });
    function autoGrow() {
        sendInput.style.height = 'auto';
        sendInput.style.height = Math.min(sendInput.scrollHeight, 96) + 'px';
    }
    sendInput.addEventListener('input', autoGrow);

    /* ---------- 열기/닫기 ---------- */
    function toggle(open) {
        var isOpen = open != null ? open : panel.hidden;
        panel.hidden = !isOpen;
        root.classList.toggle('open', isOpen);
        if (isOpen && token) setTimeout(function () { sendInput.focus(); }, 100);
    }
    fab.addEventListener('click', function () { toggle(); });
    minBtn.addEventListener('click', function () { toggle(false); });

    /* ---------- 초기화: 재방문 시 기존 대화 복구 ---------- */
    (function init() {
        try {
            var saved = JSON.parse(localStorage.getItem(LS) || 'null');
            if (saved && saved.token) {
                token = saved.token;
                var nameInput = regForm.querySelector('[name=name]');
                if (nameInput && saved.name) nameInput.value = saved.name;
                openThread();
            }
        } catch (e) {}
    })();
})();
