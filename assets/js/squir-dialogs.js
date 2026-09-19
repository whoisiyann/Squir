

(function () {
    if (window.SquirDialogs) return;

    var DEFAULT_DELETE_TEXT = 'Are you sure you want to delete this?';

    function show(backdrop) { backdrop.classList.add('open'); backdrop.setAttribute('aria-hidden', 'false'); }
    function hide(backdrop) { backdrop.classList.remove('open'); backdrop.setAttribute('aria-hidden', 'true'); }

    function build(html) {
        var wrap = document.createElement('div');
        wrap.className = 'sqd-dialog-backdrop';
        wrap.setAttribute('aria-hidden', 'true');
        wrap.innerHTML = html;
        document.body.appendChild(wrap);
        return wrap;
    }

    /* =============== Delete confirm =============== */
    var del = null;

    function getDeleteUi() {
        if (del) return del;

        var backdrop = build(
            '<div class="sqd-dialog sqd-dialog-center" role="alertdialog" aria-modal="true" aria-labelledby="sqdDeleteTitle" aria-describedby="sqdDeleteText">' +
                '<i class="ti ti-trash sqd-delete-icon" aria-hidden="true"></i>' +
                '<h2 id="sqdDeleteTitle">Delete</h2>' +
                '<p id="sqdDeleteText"></p>' +
                '<p class="sqd-error" role="alert"></p>' +
                '<div class="sqd-dialog-actions">' +
                    '<button type="button" class="sqd-btn sqd-btn-outline" data-sqd-cancel>Cancel</button>' +
                    '<button type="button" class="sqd-btn sqd-btn-danger" data-sqd-confirm>Delete</button>' +
                '</div>' +
            '</div>'
        );

        del = {
            backdrop: backdrop,
            text: backdrop.querySelector('#sqdDeleteText'),
            error: backdrop.querySelector('.sqd-error'),
            cancel: backdrop.querySelector('[data-sqd-cancel]'),
            confirm: backdrop.querySelector('[data-sqd-confirm]'),
            onConfirm: null
        };

        del.cancel.addEventListener('click', function () { hide(backdrop); });
        backdrop.addEventListener('click', function (event) {
            if (event.target === backdrop) hide(backdrop);
        });
        del.confirm.addEventListener('click', function () {
            var result = del.onConfirm ? del.onConfirm() : null;
            if (result && typeof result.then === 'function') {
                del.confirm.disabled = true;
                result.then(function () {
                    hide(backdrop);
                }).catch(function (error) {
                    del.error.textContent = (error && error.message) || 'Something went wrong. Please try again.';
                    del.confirm.disabled = false;
                });
            } else {
                hide(backdrop);
            }
        });

        return del;
    }

    function confirmDelete(options) {
        options = options || {};
        var ui = getDeleteUi();
        ui.text.textContent = options.message || DEFAULT_DELETE_TEXT;
        ui.error.textContent = '';
        ui.confirm.disabled = false;
        ui.onConfirm = options.onConfirm || null;
        show(ui.backdrop);
        ui.cancel.focus(); //
    }

    /* =============== Rename / New name dialog =============== */
    var nm = null;

    function getNameUi() {
        if (nm) return nm;

        var backdrop = build(
            '<div class="sqd-dialog" role="dialog" aria-modal="true" aria-labelledby="sqdNameTitle">' +
                '<div class="sqd-dialog-header">' +
                    '<h2 id="sqdNameTitle"></h2>' +
                    '<button type="button" class="sqd-dialog-close" aria-label="Close" data-sqd-close><i class="ti ti-x"></i></button>' +
                '</div>' +
                '<label class="sqd-label" for="sqdNameInput"></label>' +
                '<input type="text" class="sqd-input" id="sqdNameInput" maxlength="100" autocomplete="off" placeholder="Folder name">' +
                '<p class="sqd-error" role="alert"></p>' +
                '<div class="sqd-dialog-actions">' +
                    '<button type="button" class="sqd-btn sqd-btn-outline" data-sqd-cancel>Cancel</button>' +
                    '<button type="button" class="sqd-btn sqd-btn-outline" data-sqd-ok>OK</button>' +
                '</div>' +
            '</div>'
        );

        nm = {
            backdrop: backdrop,
            title: backdrop.querySelector('#sqdNameTitle'),
            label: backdrop.querySelector('.sqd-label'),
            input: backdrop.querySelector('#sqdNameInput'),
            error: backdrop.querySelector('.sqd-error'),
            ok: backdrop.querySelector('[data-sqd-ok]'),
            original: '',
            onSubmit: null
        };

        function submit() {
            var name = nm.input.value.trim();
            if (name === '') {
                nm.error.textContent = 'Folder name is required.';
                return;
            }
            if (name === nm.original) {  
                hide(backdrop);
                return;
            }

            nm.error.textContent = '';
            nm.ok.disabled = true;

            var result = nm.onSubmit ? nm.onSubmit(name) : null;
            Promise.resolve(result).then(function () {
                nm.ok.disabled = false;
                hide(backdrop);
            }).catch(function (error) {
                nm.error.textContent = (error && error.message) || 'Something went wrong. Please try again.';
                nm.ok.disabled = false;
            });
        }

        nm.ok.addEventListener('click', submit);
        nm.input.addEventListener('keydown', function (event) {
            if (event.key === 'Enter') { event.preventDefault(); submit(); }
        });
        backdrop.querySelector('[data-sqd-cancel]').addEventListener('click', function () { hide(backdrop); });
        backdrop.querySelector('[data-sqd-close]').addEventListener('click', function () { hide(backdrop); });
        backdrop.addEventListener('click', function (event) {
            if (event.target === backdrop) hide(backdrop);
        });

        return nm;
    }

    function promptName(options) {
        options = options || {};
        var ui = getNameUi();
        ui.title.textContent = options.title || 'Rename Folder';
        ui.label.textContent = options.label || 'Rename to';
        ui.input.value = options.value || '';
        ui.original = options.value || '';
        ui.onSubmit = options.onSubmit || null;
        ui.error.textContent = '';
        ui.ok.disabled = false;
        show(ui.backdrop);
        ui.input.focus();
        ui.input.select();
    }

    /* =============== forms na may data-confirm-delete =============== */
    document.addEventListener('submit', function (event) {
        var form = event.target;
        if (!form || !form.matches || !form.matches('form[data-confirm-delete]')) return;

        event.preventDefault();
        confirmDelete({
            message: form.getAttribute('data-confirm-text') || '',
            onConfirm: function () { form.submit(); }   
        });
    });

    /* =============== Esc =============== */
    document.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape') return;
        if (del && del.backdrop.classList.contains('open')) { hide(del.backdrop); return; }
        if (nm && nm.backdrop.classList.contains('open')) hide(nm.backdrop);
    });

    window.SquirDialogs = { confirmDelete: confirmDelete, promptName: promptName };
})();
