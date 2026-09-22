(function () {
    function $all(selector, scope) { return Array.prototype.slice.call((scope || document).querySelectorAll(selector)); }

    var editor = document.getElementById('noteEditor');
    var notesList = document.getElementById('notesList');
    var menuFavBtn = document.querySelector('.note-menu-favorite'); // editor's 3-dot Favorite item

    /* ---------- Toast (bottom pop-up) ---------- */
    var toastEl = null;
    var toastTimer = null;

    function toast(message) {
        if (!message) return;
        if (!toastEl) {
            toastEl = document.createElement('div');
            toastEl.className = 'dash-toast';
            toastEl.setAttribute('role', 'status');
            document.body.appendChild(toastEl);
        }
        toastEl.textContent = message;

        void toastEl.offsetWidth;
        toastEl.classList.add('show');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(function () { toastEl.classList.remove('show'); }, 2800);
    }

    if (window.NOTES_FLASH) toast(window.NOTES_FLASH);

/* ---------- Search: debounce auto-submit (feels live, still a normal GET) ---------- */
    /* Search debounce */
    (function () {
        var form = document.getElementById('notesSearchForm');
        var input = document.getElementById('notesSearchInput');
        if (!form || !input) return;

        var timer = null;
        input.addEventListener('input', function () {
            clearTimeout(timer);
            timer = setTimeout(function () { form.submit(); }, 450);
        });

        if (input.value !== '') {
            input.focus();
            var end = input.value.length;
            input.setSelectionRange(end, end);
        }
    })();

    /* ---------- Folder pills ---------- */
    (function () {
        var pills = document.querySelector('.notes-folder-pills');
        if (!pills) return;

        var active = pills.querySelector('.folder-pill.active');
        if (active && pills.scrollWidth > pills.clientWidth) {
            var offset = active.getBoundingClientRect().left - pills.getBoundingClientRect().left + pills.scrollLeft;
            pills.scrollLeft = Math.max(0, offset - (pills.clientWidth - active.offsetWidth) / 2);
        }

        pills.addEventListener('wheel', function (event) {
            if (pills.scrollWidth <= pills.clientWidth) return;
            if (Math.abs(event.deltaY) <= Math.abs(event.deltaX)) return;
            event.preventDefault();
            pills.scrollLeft += event.deltaY;
        }, { passive: false });
    })();

    /* ---------- Favorite star ---------- */
    // Toggle a note favorite
    function toggleFavoriteRequest(noteId, csrfToken) {
        var body = new URLSearchParams();
        body.set('ajax', 'toggle_favorite');
        body.set('csrf_token', csrfToken);
        body.set('note_id', noteId);
        return fetch('./notes', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: body.toString()
        }).then(function (response) {
            if (!response.ok) throw new Error('Failed to toggle favorite');
            return response.json();
        });
    }

    function applyFavState(card, btn, isFav) {
        card.setAttribute('data-favorite', isFav ? '1' : '0');
        btn.classList.toggle('is-fav', isFav);
        btn.setAttribute('data-tooltip', isFav ? 'Unfavorite' : 'Favorite');
    }


    function syncEditorFavoriteLabel(noteId, isFav) {
        if (!editor || !menuFavBtn) return;
        if (editor.getAttribute('data-note-id') !== String(noteId)) return;
        menuFavBtn.innerHTML = '<i class="fa-solid fa-star"></i> ' + (isFav ? 'Unfavorite' : 'Favorite');
    }

    var editorCsrf = window.NOTES_CSRF_TOKEN || (editor ? editor.getAttribute('data-csrf') : null);

    $all('.note-favorite-btn').forEach(function (btn) {
        btn.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();
            var card = btn.closest('.note-card');
            if (!card) return;
            var noteId = card.getAttribute('data-note-id');
            var wasFav = card.getAttribute('data-favorite') === '1';

            applyFavState(card, btn, !wasFav);
            syncEditorFavoriteLabel(noteId, !wasFav);
            toggleFavoriteRequest(noteId, editorCsrf).then(function (json) {
                applyFavState(card, btn, !!json.is_favorite);
                syncEditorFavoriteLabel(noteId, !!json.is_favorite);
            }).catch(function () {
                applyFavState(card, btn, wasFav);
                syncEditorFavoriteLabel(noteId, wasFav);
            });
        });
    });

    if (menuFavBtn && editor) {
        menuFavBtn.addEventListener('click', function () {
            var noteId = editor.getAttribute('data-note-id');
            toggleFavoriteRequest(noteId, editorCsrf).then(function (json) {
                menuFavBtn.innerHTML = '<i class="fa-solid fa-star"></i> ' + (json.is_favorite ? 'Unfavorite' : 'Favorite');
                var card = notesList && notesList.querySelector('.note-card[data-note-id="' + noteId + '"]');
                if (card) {
                    var starBtn = card.querySelector('.note-favorite-btn');
                    if (starBtn) applyFavState(card, starBtn, !!json.is_favorite);
                }
            });
        });
    }

    /* ---------- Favorite item (list rows, inside 3-dot dropdown) ---------- */
    $all('.note-menu-favorite-item').forEach(function (btn) {
        btn.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();

            var dropdown = btn.closest('.note-menu-dropdown');
            var noteId = dropdown ? dropdown.getAttribute('data-note-id') : null;
            if (!noteId || !notesList) return;

            var card = notesList.querySelector('.note-card[data-note-id="' + noteId + '"]');
            if (!card) return;

            var wasFav = card.getAttribute('data-favorite') === '1';
            var starBtn = card.querySelector('.note-favorite-btn');

            if (starBtn) applyFavState(card, starBtn, !wasFav);
            btn.innerHTML = '<i class="fa-solid fa-star"></i> ' + (!wasFav ? 'Unfavorite' : 'Favorite');
            syncEditorFavoriteLabel(noteId, !wasFav);

            toggleFavoriteRequest(noteId, editorCsrf).then(function (json) {
                if (starBtn) applyFavState(card, starBtn, !!json.is_favorite);
                btn.innerHTML = '<i class="fa-solid fa-star"></i> ' + (json.is_favorite ? 'Unfavorite' : 'Favorite');
                syncEditorFavoriteLabel(noteId, !!json.is_favorite);
            }).catch(function () {
                if (starBtn) applyFavState(card, starBtn, wasFav);
                btn.innerHTML = '<i class="fa-solid fa-star"></i> ' + (wasFav ? 'Unfavorite' : 'Favorite');
                syncEditorFavoriteLabel(noteId, wasFav);
            });
        });
    });

    /* ---------- 3-dot dropdown menus (list rows + detail header) ---------- */
    // Close note menus
    function closeAllMenus() {
        $all('.note-menu-dropdown.open').forEach(function (menu) {
            menu.classList.remove('open');
            menu.style.top = '';
            menu.style.left = '';
            if (menu._placeholder && menu._placeholder.parentNode) {
                menu._placeholder.parentNode.replaceChild(menu, menu._placeholder);
                menu._placeholder = null;
            }
        });
    }

    function positionMenu(menu, btn) {
        var rect = btn.getBoundingClientRect();
        var menuWidth = menu.offsetWidth || 190;
        var left = Math.max(8, Math.min(rect.right - menuWidth, window.innerWidth - menuWidth - 8));
        var top = rect.bottom + 6;
        var menuHeight = menu.offsetHeight || 0;
        if (menuHeight && top + menuHeight > window.innerHeight - 8) {
            top = rect.top - menuHeight - 6;
        }
        menu.style.left = left + 'px';
        menu.style.top = top + 'px';
    }

    $all('.note-menu-btn').forEach(function (btn) {
        var menu = btn.parentElement.querySelector('.note-menu-dropdown');
        if (!menu) return;

        var card = btn.closest('.note-card');
        if (card) {
            menu.setAttribute('data-note-id', card.getAttribute('data-note-id'));
        }

        btn.addEventListener('click', function (event) {
            event.stopPropagation();
            var willOpen = !menu.classList.contains('open');
            closeAllMenus();
            if (willOpen) {
                var placeholder = document.createElement('span');
                placeholder.style.display = 'none';
                menu.parentNode.insertBefore(placeholder, menu);
                menu._placeholder = placeholder;
                document.body.appendChild(menu);
                menu.classList.add('open');
                positionMenu(menu, btn);
            }
        });
    });
    document.addEventListener('click', closeAllMenus);
    window.addEventListener('resize', closeAllMenus);

    /* ---------- Editor: toolbar + autosave ---------- */
    if (editor) {
        var noteId = editor.getAttribute('data-note-id');
        var csrfToken = editor.getAttribute('data-csrf');
        var titleInput = document.getElementById('noteTitleInput');
        var contentEl = document.getElementById('noteContentEditable');
        var folderSelect = document.getElementById('noteFolderSelect');
        var statusEl = document.getElementById('noteSaveStatus');
        var breadcrumbTitle = document.getElementById('noteBreadcrumbTitle');
        var toolbar = document.querySelector('.note-toolbar');

        var saveTimer = null;

        var mobileBreakpoint = '(max-width: 900px)';

        function isMobile() {
            return window.matchMedia(mobileBreakpoint).matches;
        }

        function positionMobileToolbar() {
            if (!toolbar || !window.visualViewport) return;
            var vv = window.visualViewport;
            var keyboardHeight = Math.max(0, window.innerHeight - vv.height - vv.offsetTop);
            toolbar.style.bottom = keyboardHeight + 'px';
        }

        function showMobileToolbar() {
            if (!toolbar || !isMobile()) return;
            toolbar.classList.add('note-toolbar-visible');
            positionMobileToolbar();
        }

        function hideMobileToolbar() {
            if (!toolbar) return;
            toolbar.classList.remove('note-toolbar-visible');
        }

        if (toolbar && contentEl) {
            contentEl.addEventListener('focus', showMobileToolbar);

            document.addEventListener('focusin', function (event) {
                if (!isMobile()) return;
                var withinEditor = event.target === contentEl || toolbar.contains(event.target);
                if (!withinEditor) hideMobileToolbar();
            });

            if (window.visualViewport) {
                window.visualViewport.addEventListener('resize', function () {
                    if (toolbar.classList.contains('note-toolbar-visible')) positionMobileToolbar();
                });
                window.visualViewport.addEventListener('scroll', function () {
                    if (toolbar.classList.contains('note-toolbar-visible')) positionMobileToolbar();
                });
            }
        }

        function updateEmptyState() {
            var isEmpty = contentEl.textContent.replace(/\u200B/g, '').trim() === '';
            contentEl.classList.toggle('is-empty', isEmpty);
        }
        updateEmptyState();

        function scheduleSave() {
            if (statusEl) statusEl.textContent = 'Editing…';
            clearTimeout(saveTimer);
            saveTimer = setTimeout(saveNow, 700);
        }

        // Save the active note
        function saveNow() {
            clearTimeout(saveTimer);
            if (statusEl) statusEl.textContent = 'Saving…';

            var body = new URLSearchParams();
            body.set('ajax', 'save');
            body.set('csrf_token', csrfToken);
            body.set('note_id', noteId);
            body.set('title', titleInput.value);
            body.set('content', contentEl.innerHTML);
            body.set('folder_id', folderSelect.value);

            fetch('./notes', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: body.toString()
            }).then(function (response) {
                if (!response.ok) throw new Error('Save failed');
                return response.json();
            }).then(function (json) {
                if (statusEl) {
                    statusEl.textContent = 'Saved';
                    setTimeout(function () { statusEl.textContent = ''; }, 1500);
                }
                if (breadcrumbTitle) breadcrumbTitle.textContent = json.title;
                updateListCard(json);
            }).catch(function () {
                if (statusEl) statusEl.textContent = 'Could not save';
            });
        }

        // Update the note list card
        function updateListCard(json) {
            if (!notesList) return;
            var card = notesList.querySelector('.note-card[data-note-id="' + noteId + '"]');
            if (!card) return;
            var titleEl = card.querySelector('.note-card-top strong');
            var excerptEl = card.querySelector('.note-card-excerpt');
            var dateEl = card.querySelector('.note-card-date');
            if (titleEl) titleEl.textContent = json.title;
            if (excerptEl) excerptEl.textContent = json.excerpt;
            if (dateEl) dateEl.textContent = json.updated_at;
        }

        function updateToolbarState() {
            $all('[data-cmd]', toolbar).forEach(function (el) {
                var cmd = el.getAttribute('data-cmd');
                if (cmd === 'createLink' || cmd === 'hiliteColor') return;
                try {
                    if (cmd === 'formatBlock') {
                        var value = el.getAttribute('data-value');
                        var current = document.queryCommandValue('formatBlock').toLowerCase();
                        el.classList.toggle('active', current === value);
                    } else {
                        el.classList.toggle('active', document.queryCommandState(cmd));
                    }
                } catch (error) {}
            });
        }

        $all('[data-cmd]', toolbar).forEach(function (el) {
            el.addEventListener('click', function () {
                var cmd = el.getAttribute('data-cmd');
                var value = el.getAttribute('data-value');
                contentEl.focus();

                if (cmd === 'createLink') {
                    var url = window.prompt('Link URL:', 'https://');
                    if (!url) return;
                    document.execCommand('createLink', false, url);
                } else if (cmd === 'formatBlock') {
                    var current = document.queryCommandValue('formatBlock').toLowerCase();
                    document.execCommand('formatBlock', false, current === value ? 'p' : value);
                } else if (cmd === 'hiliteColor') {
                    var isActive = el.classList.toggle('active');
                    var appliedValue = isActive
                        ? getComputedStyle(document.documentElement).getPropertyValue('--highlight-color').trim()
                        : 'transparent';
                    var supportsHilite = document.queryCommandSupported
                        ? document.queryCommandSupported('hiliteColor')
                        : false;

                    if (supportsHilite) {
                        document.execCommand('hiliteColor', false, appliedValue);
                    } else {
                        document.execCommand('backColor', false, appliedValue);
                    }
                } else {
                    document.execCommand(cmd, false, null);
                }
                scheduleSave();
                updateToolbarState();
                updateEmptyState();
            });
        });

        contentEl.addEventListener('keyup', updateToolbarState);
        contentEl.addEventListener('mouseup', updateToolbarState);
        document.addEventListener('selectionchange', function () {
            if (document.activeElement === contentEl) updateToolbarState();
        });

        contentEl.addEventListener('paste', function (event) {
            var plainText = event.clipboardData
                ? event.clipboardData.getData('text/plain')
                : '';
            if (!plainText) return;

            event.preventDefault();
            document.execCommand('insertText', false, plainText);
            scheduleSave();
            updateEmptyState();
        });

        titleInput.addEventListener('input', function () {
            if (breadcrumbTitle) breadcrumbTitle.textContent = titleInput.value || 'Untitled note';
            scheduleSave();
        });
        titleInput.addEventListener('blur', saveNow);

        contentEl.addEventListener('input', function () {
            scheduleSave();
            updateEmptyState();
        });
        contentEl.addEventListener('blur', saveNow);

        folderSelect.addEventListener('change', saveNow);
    }
})();