(function () {
    var UNLOCK_MS = 30000;

    function submitButtons(form) {
        var buttons = Array.prototype.slice.call(form.querySelectorAll('button[type="submit"]'));
        if (form.id) {
            buttons = buttons.concat(Array.prototype.slice.call(
                document.querySelectorAll('button[type="submit"][form="' + form.id + '"]')
            ));
        }
        return buttons;
    }

    function setBusy(form, busy) {
        form.setAttribute('data-submitting', busy ? '1' : '0');
        submitButtons(form).forEach(function (btn) {
            btn.disabled = busy;
            btn.style.opacity = busy ? '0.6' : '';
            btn.style.cursor = busy ? 'not-allowed' : '';
        });
        if (!busy) clearTimeout(form._unlockTimer);
    }

    function newToken() {
        var bytes = new Uint8Array(16);
        window.crypto.getRandomValues(bytes);
        return Array.prototype.map.call(bytes, function (b) {
            return ('0' + b.toString(16)).slice(-2);
        }).join('');
    }

    // Allow one submit per form until the server answers
    document.addEventListener('submit', function (event) {
        var form = event.target;
        if (!form.hasAttribute || !form.hasAttribute('data-submit-once')) return;

        if (form.getAttribute('data-submitting') === '1') {
            event.preventDefault();
            return;
        }

        // Let validation handlers cancel submission before disabling buttons.
        form.setAttribute('data-submitting', '1');
        setTimeout(function () {
            if (event.defaultPrevented) {
                form.setAttribute('data-submitting', '0');
                return;
            }
            setBusy(form, true);
            form._unlockTimer = setTimeout(function () { setBusy(form, false); }, UNLOCK_MS);
        }, 0);
    });

    // Reset after back/forward navigation
    window.addEventListener('pageshow', function (event) {
        Array.prototype.forEach.call(document.querySelectorAll('form[data-submit-once]'), function (form) {
            setBusy(form, false);
            var tokenInput = form.querySelector('input[name="submit_token"]');
            if (event.persisted && tokenInput && window.crypto) tokenInput.value = newToken();
        });
    });
})();
