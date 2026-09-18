// assets/js/pin-gate.js
// Nagbibigay ng window.SquirPin.ensure() — humihingi ng PIN bago
// payagan ang pag-reveal/copy ng isang vault password.
//
// I-load ito BAGO ang vault.js.

window.SquirPin = (function () {
    var backdrop = document.getElementById('pinModalBackdrop');

    // Walang modal sa page na ito (hal. ibang page) — huwag humarang.
    if (!backdrop) {
        return { ensure: function () { return Promise.resolve(); } };
    }

    var modal = backdrop.querySelector('.pin-modal');
    var wrap = document.getElementById('pinModalInputs');
    var boxes = Array.prototype.slice.call(wrap.querySelectorAll('.pin-modal-box'));
    var errorEl = document.getElementById('pinModalError');
    var closeBtn = document.getElementById('pinModalClose');

    var length = boxes.length;
    var ttlSeconds = typeof window.SQUIR_PIN_TTL === 'number' ? window.SQUIR_PIN_TTL : 0;
    var csrfToken = window.VAULT_CSRF_TOKEN || '';

    var unlockedUntil = 0;
    var resolveCurrent = null;
    var busy = false;

    /* ---------- UI helpers ---------- */

    function value() {
        return boxes.map(function (box) { return box.value; }).join('');
    }

    function refresh() {
        boxes.forEach(function (box) {
            box.classList.toggle('filled', box.value !== '');
        });
    }

    function clearBoxes(focus) {
        boxes.forEach(function (box) { box.value = ''; });
        refresh();
        if (focus !== false) boxes[0].focus();
    }

    function shake() {
        wrap.classList.remove('shake');
        void wrap.offsetWidth;
        wrap.classList.add('shake');
    }

    function setBusy(state) {
        busy = state;
        modal.classList.toggle('is-busy', state);
    }

    function open() {
        errorEl.textContent = '';
        setBusy(false);
        backdrop.classList.add('open');
        backdrop.setAttribute('aria-hidden', 'false');
        clearBoxes();
    }

    function close() {
        backdrop.classList.remove('open');
        backdrop.setAttribute('aria-hidden', 'true');
        clearBoxes(false);
        errorEl.textContent = '';
        setBusy(false);
    }

    function cancel() {
        resolveCurrent = null;
        close();
    }

    /* ---------- Server check ---------- */

    function sendPin(pin) {
        var body = new URLSearchParams();
        body.set('ajax', 'verify_pin');
        body.set('csrf_token', csrfToken);
        body.set('pin', pin);

        return fetch('./vault', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: body.toString()
        }).then(function (response) {
            return response.json().catch(function () { return {}; });
        });
    }

    function submitPin() {
        if (busy) return;

        var pin = value();
        if (pin.length !== length) return;

        setBusy(true);
        errorEl.textContent = '';

        sendPin(pin).then(function (json) {
            if (json && json.ok) {
                if (ttlSeconds > 0) {
                    unlockedUntil = Date.now() + ttlSeconds * 1000;
                }
                var resolve = resolveCurrent;
                resolveCurrent = null;
                close();
                if (resolve) resolve();
                return;
            }

            setBusy(false);
            errorEl.textContent = (json && json.error) || 'That PIN is incorrect.';
            shake();
            clearBoxes();
        }).catch(function () {
            setBusy(false);
            errorEl.textContent = 'Could not check your PIN. Please try again.';
            clearBoxes();
        });
    }

    /* ---------- Events ---------- */

    boxes.forEach(function (box, index) {
        box.addEventListener('input', function () {
            box.value = box.value.replace(/\D/g, '').slice(0, 1);
            if (box.value !== '' && index < boxes.length - 1) {
                boxes[index + 1].focus();
            }
            refresh();
            if (value().length === length) submitPin();
        });

        box.addEventListener('keydown', function (event) {
            if (event.key === 'Backspace' && box.value === '' && index > 0) {
                event.preventDefault();
                boxes[index - 1].value = '';
                boxes[index - 1].focus();
                refresh();
            } else if (event.key === 'Enter') {
                event.preventDefault();
                submitPin();
            } else if (event.key === 'Escape') {
                cancel();
            }
        });

        box.addEventListener('paste', function (event) {
            event.preventDefault();
            var digits = (event.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '');
            for (var i = 0; i < boxes.length; i++) {
                boxes[i].value = digits[i] || '';
            }
            refresh();
            if (value().length === length) submitPin();
            else boxes[Math.min(digits.length, boxes.length - 1)].focus();
        });

        box.addEventListener('focus', function () { box.select(); });
    });

    closeBtn.addEventListener('click', cancel);

    backdrop.addEventListener('click', function (event) {
        if (event.target === backdrop) cancel();
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && backdrop.classList.contains('open')) cancel();
    });

    /* ---------- Public API ---------- */

    return {
        /**
         * Nire-resolve kapag tama ang PIN. Kapag kinansela ng user,
         * nananatiling pending ang promise — walang mangyayari,
         * walang error message na lalabas.
         */
        ensure: function () {
            if (ttlSeconds > 0 && Date.now() < unlockedUntil) {
                return Promise.resolve();
            }

            return new Promise(function (resolve) {
                resolveCurrent = resolve;
                open();
            });
        },

        lock: function () {
            unlockedUntil = 0;
        }
    };
})();