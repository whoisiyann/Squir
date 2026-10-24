/* Vault: select mode + delete selected passwords */
(function () {
    var wrap = document.getElementById('vaultTableWrap');
    var btn = document.getElementById('vaultSelectBtn');
    if (!wrap || !window.SquirBulkSelect) {
        if (btn) btn.hidden = true;   // nothing to select
        return;
    }

    var tbody = wrap.querySelector('tbody');

    function post(ids) {
        var body = new URLSearchParams();
        body.set('ajax', 'bulk_delete');
        body.set('csrf_token', window.VAULT_CSRF_TOKEN || '');
        ids.forEach(function (id) { body.append('ids[]', id); });

        return fetch('./vault', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'Accept': 'application/json' },
            body: body.toString()
        }).then(function (response) {
            return response.json().catch(function () { return {}; }).then(function (json) {
                if (!response.ok) throw new Error(json.error || 'Could not delete the selected passwords. Please try again.');
                return json;
            });
        });
    }

    SquirBulkSelect.init({
        root: tbody,
        marqueeArea: wrap.closest('main') || wrap,   // whole page area, not just the small table
        marqueeFromItems: true,                      // list view rows fill the full width
        itemSelector: 'tr[data-vault-id]',
        getId: function (item) { return item.getAttribute('data-vault-id'); },
        checkHost: function (item) { return item.querySelector('.vault-item-cell') || item.firstElementChild; },
        toggleButtons: [btn],
        noun: 'password',
        ignoreLongPress: '.vault-actions',
        onDelete: function (ids, info) {
            var message = info.count === 1
                ? 'Are you sure you want to delete this password?'
                : 'Are you sure you want to delete these ' + info.count + ' passwords? This cannot be undone.';

            if (!window.SquirDialogs) {
                if (!window.confirm(message)) return;
                return post(ids).then(function () { window.location.reload(); });
            }

            SquirDialogs.confirmDelete({
                message: message,
                onConfirm: function () {
                    return post(ids).then(function () {
                        info.done();
                        window.location.reload();
                    });
                }
            });
        }
    });
})();
