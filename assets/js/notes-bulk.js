/* Notes: select mode + delete selected notes */
(function () {
    var list = document.getElementById('notesList');
    if (!list || !window.SquirBulkSelect) return;

    function post(ids) {
        var body = new URLSearchParams();
        body.set('ajax', 'bulk_delete');
        body.set('csrf_token', window.NOTES_CSRF_TOKEN || '');
        ids.forEach(function (id) { body.append('ids[]', id); });

        return fetch('./notes', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'Accept': 'application/json' },
            body: body.toString()
        }).then(function (response) {
            return response.json().catch(function () { return {}; }).then(function (json) {
                if (!response.ok) throw new Error(json.error || 'Could not delete the selected notes. Please try again.');
                return json;
            });
        });
    }

    function reloadWithout(ids) {
        var url = new URL(window.location.href);
        var open = url.searchParams.get('note');
        if (open && ids.indexOf(String(open)) !== -1) url.searchParams.delete('note');
        window.location.href = url.toString();
    }

    SquirBulkSelect.init({
        root: list,
        marqueeArea: document.querySelector('.notes-list-panel') || list,
        itemSelector: 'li.note-card[data-note-id]',
        getId: function (item) { return item.getAttribute('data-note-id'); },
        checkHost: function (item) { return item.querySelector('.note-card-link') || item; },
        noun: 'note',
        onDelete: function (ids, info) {
            var message = info.count === 1
                ? 'Are you sure you want to delete this note?'
                : 'Are you sure you want to delete these ' + info.count + ' notes? This cannot be undone.';

            if (!window.SquirDialogs) {
                if (!window.confirm(message)) return;
                return post(ids).then(function () { reloadWithout(ids); });
            }

            SquirDialogs.confirmDelete({
                message: message,
                onConfirm: function () {
                    return post(ids).then(function () {
                        info.done();
                        reloadWithout(ids);
                    });
                }
            });
        }
    });
})();
