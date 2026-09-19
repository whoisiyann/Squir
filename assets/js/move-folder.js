

(function () {
    var backdrop = document.getElementById('moveFolderBackdrop');
    if (!backdrop) return;

    var form = document.getElementById('moveFolderForm');
    var idInput = document.getElementById('moveFolderItemId');
    var targetInput = document.getElementById('moveFolderTarget');
    var nameEl = document.getElementById('moveFolderItemName');
    var options = Array.prototype.slice.call(backdrop.querySelectorAll('.move-option'));
    var currentFolderId = '';

    function openModal(itemId, title, folderId) {
        currentFolderId = folderId || '';
        idInput.value = itemId;
        targetInput.value = '';
        nameEl.textContent = title || '';

        options.forEach(function (option) {
            var isCurrent = (option.getAttribute('data-folder-id') || '') === String(currentFolderId);
            option.classList.toggle('is-current', isCurrent);
            option.setAttribute('aria-pressed', isCurrent ? 'true' : 'false');
        });

        backdrop.classList.add('open');
        backdrop.setAttribute('aria-hidden', 'false');
    }

    function closeModal() {
        backdrop.classList.remove('open');
        backdrop.setAttribute('aria-hidden', 'true');
    }

    document.addEventListener('click', function (event) {
        var trigger = event.target.closest('[data-move-folder-trigger]');
        if (!trigger) return;
        event.preventDefault();
        openModal(
            trigger.getAttribute('data-item-id'),
            trigger.getAttribute('data-item-title'),
            trigger.getAttribute('data-folder-id')
        );
    });

    options.forEach(function (option) {
        option.addEventListener('click', function () {
            var folderId = option.getAttribute('data-folder-id') || '';
            if (folderId === String(currentFolderId)) {  
                closeModal();
                return;
            }
            targetInput.value = folderId;
            form.submit();
        });
    });

    Array.prototype.forEach.call(backdrop.querySelectorAll('[data-move-close]'), function (btn) {
        btn.addEventListener('click', closeModal);
    });

    backdrop.addEventListener('click', function (event) {
        if (event.target === backdrop) closeModal();
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && backdrop.classList.contains('open')) closeModal();
    });
})();
