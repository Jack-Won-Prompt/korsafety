{{-- 회원가입 이메일 인증 — 인증번호 발송·확인. 인증 전에는 가입 버튼이 눌리지 않는다 --}}
@once
@push('scripts')
<script>
(function () {
    var form = document.querySelector('form[data-verify-form]');
    if (!form) return;

    var emailInput = form.querySelector('input[name="email"]');
    var sendBtn = form.querySelector('[data-verify-send]');
    var codeRow = form.querySelector('[data-verify-row]');
    var codeInput = form.querySelector('[data-verify-code]');
    var confirmBtn = form.querySelector('[data-verify-confirm]');
    var msg = form.querySelector('[data-verify-msg]');
    var timerEl = form.querySelector('[data-verify-timer]');
    var flag = form.querySelector('input[name="email_verified"]');
    var submitBtn = form.querySelector('[type="submit"]');
    var token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    var timer = null;

    function say(text, kind) {
        msg.textContent = text;
        msg.className = 'verify-msg' + (kind ? ' is-' + kind : '');
        msg.hidden = !text;
    }

    function post(url, body) {
        return fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token },
            body: JSON.stringify(body)
        }).then(function (res) {
            return res.json().catch(function () { return {}; }).then(function (data) {
                return { ok: res.ok, status: res.status, data: data };
            });
        });
    }

    function lockVerified() {
        flag.value = '1';
        emailInput.readOnly = true;
        emailInput.classList.add('is-verified');
        sendBtn.hidden = true;
        codeRow.hidden = true;
        if (timerEl) { timerEl.textContent = ''; timerEl.hidden = true; }
        say('이메일 인증이 완료되었습니다.', 'ok');
        if (submitBtn) submitBtn.disabled = false;
        if (timer) { clearInterval(timer); timer = null; }
    }

    function startCountdown(seconds) {
        var left = seconds;
        if (timer) clearInterval(timer);
        if (timerEl) timerEl.hidden = false;
        timer = setInterval(function () {
            left--;
            if (left <= 0) {
                clearInterval(timer); timer = null;
                if (timerEl) { timerEl.textContent = ''; timerEl.hidden = true; }
                say('인증번호가 만료되었습니다. 다시 받아 주세요.', 'err');
                return;
            }
            var m = Math.floor(left / 60), s = left % 60;
            if (timerEl) timerEl.textContent = m + ':' + (s < 10 ? '0' : '') + s;
        }, 1000);
    }

    // 이메일을 고치면 인증을 처음부터 다시
    emailInput.addEventListener('input', function () {
        if (flag.value === '1') {
            flag.value = '';
            emailInput.classList.remove('is-verified');
            sendBtn.hidden = false;
            if (submitBtn) submitBtn.disabled = true;
            say('', '');
        }
    });

    sendBtn.addEventListener('click', function () {
        var email = (emailInput.value || '').trim();
        if (!email || email.indexOf('@') < 0) { say('이메일을 정확히 입력해 주세요.', 'err'); emailInput.focus(); return; }

        sendBtn.disabled = true;
        say('인증번호를 보내는 중입니다…', '');
        post({!! json_encode(route('email.verify.send')) !!}, { email: email }).then(function (r) {
            sendBtn.disabled = false;
            if (!r.ok) { say(r.data.message || '인증번호를 보내지 못했습니다.', 'err'); return; }
            codeRow.hidden = false;
            codeInput.value = '';
            codeInput.focus();
            say(r.data.message || '인증번호를 보냈습니다. 메일함을 확인해 주세요.', 'ok');
            sendBtn.textContent = '재발송';
            startCountdown(r.data.expires_in || 600);
        }).catch(function () {
            sendBtn.disabled = false;
            say('통신에 실패했습니다. 잠시 후 다시 시도해 주세요.', 'err');
        });
    });

    confirmBtn.addEventListener('click', function () {
        var code = (codeInput.value || '').trim();
        if (!code) { say('인증번호를 입력해 주세요.', 'err'); codeInput.focus(); return; }

        confirmBtn.disabled = true;
        post({!! json_encode(route('email.verify.confirm')) !!}, { email: (emailInput.value || '').trim(), code: code })
            .then(function (r) {
                confirmBtn.disabled = false;
                if (!r.ok) { say(r.data.message || '인증에 실패했습니다.', 'err'); return; }
                lockVerified();
            }).catch(function () {
                confirmBtn.disabled = false;
                say('통신에 실패했습니다. 잠시 후 다시 시도해 주세요.', 'err');
            });
    });

    codeInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') { e.preventDefault(); confirmBtn.click(); }
    });

    // 처음에는 가입 버튼을 잠가 둔다
    if (submitBtn && flag.value !== '1') submitBtn.disabled = true;
})();
</script>
@endpush
@endonce
