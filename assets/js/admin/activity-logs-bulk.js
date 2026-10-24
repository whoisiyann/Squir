/* Admin activity logs: select mode + delete selected logs */
(function () {
    var root = document.getElementById('alRoot');
    var body = document.getElementById('alBody');
    var btn = document.getElementById('alSelectBtn');
    if (!root || !body || !window.SquirBulkSelect) return;

    var endpoint = root.getAttribute('data-endpoint');

    function post(ids) {
        var data = new URLSearchParams();
        data.set('ajax', 'delete_logs');
        data.set('csrf_token', window.ADMIN_CSRF_TOKEN || '');
        ids.forEach(function (id) { data.append('ids[]', id); });

        return fetch(endpoint, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'Accept': 'application/json' },
            body: data.toString()
        }).then(function (response) {
            return response.json().catch(function () { return {}; }).then(function (json) {
                if (!response.ok) throw new Error(json.error || 'Could not delete the selected logs.');
                return json;
            });
        });
    }

    SquirBulkSelect.init({
        root: body,
        marqueeArea: document.getElementById('alScroll') || body,
        itemSelector: 'tr[data-log-row]',
        getId: function (item) { return item.getAttribute('data-log-id'); },
        checkHost: function (item) { return item.querySelector('.al-user-cell') || item.firstElementChild; },
        toggleButtons: [btn],
        noun: 'log',
        onDelete: function (ids, info) {
            if (!window.AdminAlert) {
                window.alert('The dialog could not load. Please refresh the page and try again.');
                return;
            }

            return AdminAlert.confirm({
                title: info.count === 1 ? 'Delete this log?' : 'Delete ' + info.count + ' logs?',
                text: 'The selected user activity logs will be permanently deleted. This cannot be undone.',
                confirmText: 'Yes, delete',
                danger: true
            }).then(function (confirmed) {
                if (!confirmed) return;

                return post(ids).then(function (json) {
                    info.done();
                    var refresh = document.getElementById('alRefresh');
                    if (refresh) refresh.click();
                    var n = json.deleted || 0;
                    return AdminAlert.success('Logs deleted', n + ' log' + (n === 1 ? '' : 's') + ' deleted.');
                }).catch(function (error) {
                    return AdminAlert.error(error && error.message ? error.message : 'Could not reach the server. Please try again.');
                });
            });
        }
    });
})();
