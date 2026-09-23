

(function () {
    var openBtn = document.getElementById('openNotesFolderPanel');
    var panel = document.getElementById('nfPanelBackdrop');
    if (!openBtn || !panel) return;

    var csrf = window.NOTES_CSRF_TOKEN || '';
    var activeFolder = window.NOTES_ACTIVE_FOLDER == null ? '' : String(window.NOTES_ACTIVE_FOLDER);
    var REOPEN_KEY = 'squir-notes-folders-reopen';

    var rows = Array.prototype.slice.call(panel.querySelectorAll('.nf-row'));
    var backBtn = document.getElementById('nfBackBtn');
    var deleteBtn = document.getElementById('nfDeleteBtn');
    var newBtn = document.getElementById('nfNewBtn');

    var nameDialog = document.getElementById('nfNameBackdrop');
    var nameTitle = document.getElementById('nfNameTitle');
    var nameLabel = document.getElementById('nfNameLabel');
    var nameInput = document.getElementById('nfNameInput');
    var nameError = document.getElementById('nfNameError');
    var nameOk = document.getElementById('nfNameOk');

    var deleteDialog = document.getElementById('nfDeleteBackdrop');
    var deleteError = document.getElementById('nfDeleteError');
    var deleteConfirm = document.getElementById('nfDeleteConfirm');

    var selectedId = '';       // '' = "All"
    var nameMode = 'rename';   // 'rename' | 'create'
    var nameTargetId = '';
    var nameOriginal = '';

    function rowFor(id) {
        for (var i = 0; i < rows.length; i++) {
            if ((rows[i].getAttribute('data-folder-id') || '') === id) return rows[i];
        }
        return null;
    }

    function show(el) { el.classList.add('open'); el.setAttribute('aria-hidden', 'false'); }
    function hide(el) { el.classList.remove('open'); el.setAttribute('aria-hidden', 'true'); }

    // Send a folder management request
    function request(params) {
        var body = new URLSearchParams();
        body.set('csrf_token', csrf);
        Object.keys(params).forEach(function (key) { body.set(key, params[key]); });

        return fetch('./notes', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: body.toString()
        }).then(function (response) {
            return response.json().catch(function () { return {}; }).then(function (json) {
                if (!response.ok) throw new Error(json.error || 'Something went wrong. Please try again.');
                return json;
            });
        });
    }

    function reloadWithPanelOpen(url) {
        try { window.sessionStorage.setItem(REOPEN_KEY, '1'); } catch (error) {}
        if (url) { window.location.href = url; } else { window.location.reload(); }
    }

    // Select a notes folder
    function select(id) {
        selectedId = id;
        rows.forEach(function (row) {
            row.classList.toggle('is-selected', (row.getAttribute('data-folder-id') || '') === id);
        });
        deleteBtn.disabled = (id === '');
    }

    rows.forEach(function (row) {
        var id = row.getAttribute('data-folder-id') || '';

        row.querySelector('.nf-row-select').addEventListener('click', function () { select(id); });

        var renameBtn = row.querySelector('.nf-row-rename');
        if (renameBtn) {
            renameBtn.addEventListener('click', function () {
                select(id);
                openNameDialog('rename', id, row.getAttribute('data-folder-name') || '');
            });
        }
    });

    // Open the notes folder panel
    function openPanel() {
        select(rowFor(activeFolder) ? activeFolder : '');
        show(panel);
    }

    openBtn.addEventListener('click', function (event) {
        event.preventDefault();
        openPanel();
    });

   
    backBtn.addEventListener('click', function () {
        if (selectedId !== activeFolder) {
            window.location.href = './notes' + (selectedId !== '' ? '?folder=' + encodeURIComponent(selectedId) : '');
            return;
        }
        hide(panel);
    });

    panel.addEventListener('click', function (event) {
        if (event.target === panel) hide(panel);
    });

    // Open the folder name dialog
    function openNameDialog(mode, folderId, currentName) {
        nameMode = mode;
        nameTargetId = folderId;
        nameOriginal = currentName;

        nameTitle.textContent = mode === 'rename' ? 'Rename Folder' : 'New Folder';
        nameLabel.textContent = mode === 'rename' ? 'Rename to' : 'Folder name';
        nameInput.value = currentName;
        nameError.textContent = '';
        nameOk.disabled = false;

        show(nameDialog);
        nameInput.focus();
        nameInput.select();
    }

    function closeNameDialog() { hide(nameDialog); }

    // Submit a folder name change
    function submitName() {
        var name = nameInput.value.trim();
        if (name === '') {
            nameError.textContent = 'Folder name is required.';
            return;
        }
        if (nameMode === 'rename' && name === nameOriginal) {
            closeNameDialog();
            return;
        }

        nameOk.disabled = true;
        nameError.textContent = '';

        var params = nameMode === 'rename'
            ? { ajax: 'folder_rename', folder_id: nameTargetId, folder_name: name }
            : { ajax: 'folder_create', folder_name: name };

        request(params).then(function () {
            reloadWithPanelOpen();
        }).catch(function (error) {
            nameError.textContent = error.message;
            nameOk.disabled = false;
        });
    }

    newBtn.addEventListener('click', function () { openNameDialog('create', '', ''); });
    nameOk.addEventListener('click', submitName);
    document.getElementById('nfNameCancel').addEventListener('click', closeNameDialog);
    document.getElementById('nfNameClose').addEventListener('click', closeNameDialog);
    nameInput.addEventListener('keydown', function (event) {
        if (event.key === 'Enter') { event.preventDefault(); submitName(); }
    });
    nameDialog.addEventListener('click', function (event) {
        if (event.target === nameDialog) closeNameDialog();
    });

    function closeDeleteDialog() { hide(deleteDialog); }

    deleteBtn.addEventListener('click', function () {
        if (selectedId === '') return;
        deleteError.textContent = '';
        deleteConfirm.disabled = false;
        show(deleteDialog);
    });

    deleteConfirm.addEventListener('click', function () {
        if (selectedId === '') return;
        deleteConfirm.disabled = true;

        request({ ajax: 'folder_delete', folder_id: selectedId }).then(function () {
            // Reset the filter when its folder is deleted
            // Reset the filter when its folder is deleted
            reloadWithPanelOpen(selectedId === activeFolder ? './notes' : null);
        }).catch(function (error) {
            deleteError.textContent = error.message;
            deleteConfirm.disabled = false;
        });
    });

    document.getElementById('nfDeleteCancel').addEventListener('click', closeDeleteDialog);
    deleteDialog.addEventListener('click', function (event) {
        if (event.target === deleteDialog) closeDeleteDialog();
    });


    document.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape') return;
        if (deleteDialog.classList.contains('open')) { closeDeleteDialog(); return; }
        if (nameDialog.classList.contains('open')) { closeNameDialog(); return; }
        if (panel.classList.contains('open')) hide(panel);
    });


    try {
        if (window.sessionStorage.getItem(REOPEN_KEY) === '1') {
            window.sessionStorage.removeItem(REOPEN_KEY);
            openPanel();
        }
    } catch (error) {}
})();
