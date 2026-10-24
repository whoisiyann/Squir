/* Tasks: select mode + delete selected tasks */
(function () {
    var board = document.getElementById('tasksBoard');
    var btn = document.getElementById('tasksSelectBtn');
    var cfg = window.SQUIR_TASKS || {};
    if (!board || !window.SquirBulkSelect) return;

    function post(ids) {
        var body = new URLSearchParams();
        body.set('ajax', 'bulk_delete');
        body.set('csrf_token', cfg.csrf || '');
        ids.forEach(function (id) { body.append('ids[]', id); });

        return fetch('./tasks', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'Accept': 'application/json' },
            body: body.toString()
        }).then(function (response) {
            return response.json().catch(function () { return {}; }).then(function (json) {
                if (!response.ok) throw new Error(json.error || 'Could not delete the selected tasks. Please try again.');
                return json;
            });
        });
    }

    // Refresh the board without reloading the page
    function applyResult(json) {
        if (window.SquirTasksBoard) {
            window.SquirTasksBoard.update(json);
            window.SquirTasksBoard.toast(json.deleted === 1 ? 'Task deleted' : (json.deleted || 0) + ' tasks deleted');
        } else {
            window.location.reload();
        }
    }

    SquirBulkSelect.init({
        root: board,
        itemSelector: '.task-card[data-id]',
        getId: function (item) { return item.getAttribute('data-id'); },
        checkHost: function (item) { return item; },
        toggleButtons: [btn],
        noun: 'task',
        marqueeArea: board.closest('main') || board,
        onDelete: function (ids, info) {
            var message = info.count === 1
                ? 'Are you sure you want to delete this task?'
                : 'Are you sure you want to delete these ' + info.count + ' tasks? This cannot be undone.';

            if (!window.SquirDialogs) {
                if (!window.confirm(message)) return;
                return post(ids).then(function (json) { info.done(); applyResult(json); });
            }

            SquirDialogs.confirmDelete({
                message: message,
                onConfirm: function () {
                    return post(ids).then(function (json) {
                        info.done();
                        applyResult(json);
                    });
                }
            });
        }
    });
})();
