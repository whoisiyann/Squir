// assets/js/notes.js
 
(function () {
    function $all(selector, scope) { return Array.prototype.slice.call((scope || document).querySelectorAll(selector)); }
 
    var editor = document.getElementById('noteEditor');
    var notesList = document.getElementById('notesList');
 
    /* ---------- Search: debounce auto-submit (feels live, still a normal GET) ---------- */
    (function () {
        var form = document.getElementById('notesSearchForm');
        var input = document.getElementById('notesSearchInput');
        if (!form || !input) return;
        var timer = null;
        input.addEventListener('input', function () {
            clearTimeout(timer);
            timer = setTimeout(function () { form.submit(); }, 450);
        });
    })();
 
    /* ---------- Favorite star ---------- */
    function toggleFavoriteRequest(noteId, csrfToken) {
        var body = new URLSearchParams();
        body.set('ajax', 'toggle_favorite');
        body.set('csrf_token', csrfToken);
        body.set('note_id', noteId);
        return fetch('./notes.php', {
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
 
    var editorCsrf = editor ? editor.getAttribute('data-csrf') : null;
 
    $all('.note-favorite-btn').forEach(function (btn) {
        btn.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();
            var card = btn.closest('.note-card');
            if (!card) return;
            var noteId = card.getAttribute('data-note-id');
            var wasFav = card.getAttribute('data-favorite') === '1';
 
            applyFavState(card, btn, !wasFav);
            toggleFavoriteRequest(noteId, editorCsrf).then(function (json) {
                applyFavState(card, btn, !!json.is_favorite);
            }).catch(function () {
                applyFavState(card, btn, wasFav);
            });
        });
    });
 
    var menuFavBtn = document.querySelector('.note-menu-favorite');
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
 
    /* ---------- 3-dot dropdown menus (list rows + detail header) ---------- */
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
 
        // Prefer CSS output (spans with style=) over legacy tags like
        // <font>, so color/highlight survive the server-side HTML whitelist.
        try { document.execCommand('styleWithCSS', false, true); } catch (e) {}
 
        /* ---------- keep track of the last real text selection ----------
           Clicking a toolbar button can steal focus from the editable area
           before the click handler runs, which would otherwise lose the
           user's text selection right when we need it. */
        var savedSelectionRange = null;
        function rememberSelection() {
            var sel = window.getSelection();
            if (sel && sel.rangeCount > 0 && contentEl.contains(sel.anchorNode)) {
                savedSelectionRange = sel.getRangeAt(0).cloneRange();
            }
        }
        function restoreSavedSelection() {
            if (!savedSelectionRange) return;
            var sel = window.getSelection();
            sel.removeAllRanges();
            sel.addRange(savedSelectionRange);
        }
        contentEl.addEventListener('mouseup', rememberSelection);
        contentEl.addEventListener('keyup', rememberSelection);
 
        /* ---------- simple one-shot commands (bold, italic, lists, align, indent...) ---------- */
        $all('[data-cmd]', toolbar).forEach(function (el) {
            if (el.tagName === 'SELECT') {
                el.addEventListener('change', function () {
                    contentEl.focus();
                    document.execCommand('formatBlock', false, el.value);
                    scheduleSave();
                });
                return;
            }
            el.addEventListener('click', function () {
                var cmd = el.getAttribute('data-cmd');
                contentEl.focus();
                if (cmd === 'createLink') {
                    var url = window.prompt('Link URL:', 'https://');
                    if (!url) return;
                    document.execCommand('createLink', false, url);
                } else if (cmd === 'insertImage') {
                    var imgUrl = window.prompt('Image URL (https://...):', 'https://');
                    if (!imgUrl) return;
                    document.execCommand('insertImage', false, imgUrl);
                } else if (cmd === 'insertChecklist') {
                    document.execCommand('insertHTML', false, '<ul class="note-checklist"><li>List item</li></ul><p><br></p>');
                } else {
                    document.execCommand(cmd, false, null);
                }
                scheduleSave();
            });
        });
 
        /* ---------- checklist: click near the box (not the text) to toggle it done ---------- */
        contentEl.addEventListener('click', function (event) {
            var li = event.target.closest && event.target.closest('.note-checklist > li');
            if (!li) return;
            var rect = li.getBoundingClientRect();
            if (event.clientX - rect.left > 26) return; // clicking the text should just place the cursor
            event.preventDefault();
            li.classList.toggle('checked');
            scheduleSave();
        });
 
        /* ---------- font size control (Word/Docs-style) ---------- */
        var fontSizeValueEl = document.getElementById('noteFontSizeValue');
        var fontSizePanel = document.getElementById('noteFontSizePanel');
        var FONT_SIZE_PRESETS = [8, 9, 10, 11, 12, 14, 18, 24, 30, 36, 48, 60, 72, 96];
        var currentFontSize = fontSizeValueEl ? (parseInt(fontSizeValueEl.value, 10) || 11) : 11;
 
        function clampFontSize(value) {
            value = parseInt(value, 10);
            if (isNaN(value)) value = currentFontSize;
            return Math.max(8, Math.min(96, value));
        }
 
        // Gamit ang sariling "fontSize" command ng browser (mas reliable
        // kaysa manual na Range-based na pag-wrap ng <span> — 'yon ang dating
        // paraan pero hindi talaga gumagana sa lahat ng selection shapes).
        // Pinapalitan lang natin ng eksaktong pt value ang default na size
        // na ginagamit ng command (index 7 = pinakamalaki sa legacy scale).
        function applyFontSizeToSelection(value) {
            document.execCommand('styleWithCSS', false, false);
            var didApply = document.execCommand('fontSize', false, '7');
            document.execCommand('styleWithCSS', false, true);
 
            $all('font[size="7"]', contentEl).forEach(function (fontEl) {
                var span = document.createElement('span');
                span.style.fontSize = value + 'pt';
                while (fontEl.firstChild) span.appendChild(fontEl.firstChild);
                fontEl.parentNode.replaceChild(span, fontEl);
            });
            return didApply;
        }
 
        // Kapag walang naka-highlight (cursor lang), i-set ang laki para sa
        // mga susunod pang titype-in — gaya ng ginagawa ng Google Docs/Word.
        function applyFontSizeAtCursor(value) {
            var sel = window.getSelection();
            if (!sel || sel.rangeCount === 0 || !contentEl.contains(sel.anchorNode)) return;
            var range = sel.getRangeAt(0);
            var span = document.createElement('span');
            span.style.fontSize = value + 'pt';
            var marker = document.createTextNode('\u200b'); // zero-width, para may mahawakan ang caret
            span.appendChild(marker);
            range.insertNode(span);
            var newRange = document.createRange();
            newRange.setStart(marker, marker.length);
            newRange.collapse(true);
            sel.removeAllRanges();
            sel.addRange(newRange);
        }
 
        function markActivePreset() {
            $all('[data-fontsize-preset]', fontSizePanel).forEach(function (btn) {
                btn.classList.toggle('active', parseInt(btn.getAttribute('data-fontsize-preset'), 10) === currentFontSize);
            });
        }
 
        function applyFontSize(value) {
            currentFontSize = clampFontSize(value);
            if (fontSizeValueEl) fontSizeValueEl.value = currentFontSize;
            markActivePreset();
            contentEl.focus();
            restoreSavedSelection();
 
            var sel = window.getSelection();
            var hasSelection = sel && sel.rangeCount > 0 && !sel.isCollapsed && contentEl.contains(sel.anchorNode);
            if (hasSelection) {
                applyFontSizeToSelection(currentFontSize);
            } else {
                applyFontSizeAtCursor(currentFontSize);
            }
            scheduleSave();
        }
 
        // I-reflect sa toolbar ang laki ng font kung saan naka-cursor/naka-highlight
        // ngayon, gaya ng ginagawa ng Word/Google Docs habang gumagalaw ang cursor.
        function refreshFontSizeDisplay() {
            var sel = window.getSelection();
            if (!sel || sel.rangeCount === 0 || !contentEl.contains(sel.anchorNode)) return;
            var node = sel.anchorNode;
            var el = node.nodeType === 3 ? node.parentElement : node;
            if (!el) return;
            var px = parseFloat(window.getComputedStyle(el).fontSize);
            if (isNaN(px)) return;
            currentFontSize = clampFontSize(Math.round(px * 72 / 96));
            if (fontSizeValueEl && document.activeElement !== fontSizeValueEl) {
                fontSizeValueEl.value = currentFontSize;
            }
            markActivePreset();
        }
        document.addEventListener('selectionchange', function () {
            if (contentEl.contains(window.getSelection().anchorNode)) {
                refreshFontSizeDisplay();
            }
        });
 
        $all('[data-fontsize]', toolbar).forEach(function (btn) {
            btn.addEventListener('click', function () {
                applyFontSize(currentFontSize + (btn.getAttribute('data-fontsize') === 'inc' ? 1 : -1));
                closeFontSizePanel();
            });
        });
 
        function openFontSizePanel() {
            closeAllTogglePanels();
            markActivePreset();
            if (fontSizePanel) fontSizePanel.classList.add('open');
        }
        function closeFontSizePanel() {
            if (fontSizePanel) fontSizePanel.classList.remove('open');
        }
 
        if (fontSizeValueEl) {
            fontSizeValueEl.addEventListener('focus', openFontSizePanel);
            fontSizeValueEl.addEventListener('click', function (event) { event.stopPropagation(); openFontSizePanel(); });
            fontSizeValueEl.addEventListener('change', function () {
                applyFontSize(fontSizeValueEl.value);
            });
            fontSizeValueEl.addEventListener('keydown', function (event) {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    applyFontSize(fontSizeValueEl.value);
                    closeFontSizePanel();
                    contentEl.focus();
                }
            });
        }
 
        if (fontSizePanel) {
            fontSizePanel.addEventListener('click', function (event) { event.stopPropagation(); });
            $all('[data-fontsize-preset]', fontSizePanel).forEach(function (btn) {
                btn.addEventListener('click', function () {
                    applyFontSize(btn.getAttribute('data-fontsize-preset'));
                    closeFontSizePanel();
                });
            });
        }
 
        /* ---------- text color / highlight pickers + align / line-spacing dropdowns ---------- */
        function closeAllTogglePanels() {
            $all('.note-color-panel.open, .note-dropdown-panel.open, .note-fontsize-panel.open', toolbar).forEach(function (p) {
                p.classList.remove('open');
            });
        }
 
        $all('.note-color-swatch-btn', toolbar).forEach(function (btn) {
            btn.addEventListener('click', function (event) {
                event.stopPropagation();
                var target = btn.getAttribute('data-color-target');
                var panel = toolbar.querySelector('.note-color-panel[data-panel-for="' + target + '"]');
                var willOpen = panel && !panel.classList.contains('open');
                closeAllTogglePanels();
                if (willOpen) panel.classList.add('open');
            });
        });
 
        $all('.note-dropdown-btn', toolbar).forEach(function (btn) {
            btn.addEventListener('click', function (event) {
                event.stopPropagation();
                var name = btn.getAttribute('data-dropdown');
                var panel = toolbar.querySelector('.note-dropdown-panel[data-panel-for="' + name + '"]');
                var willOpen = panel && !panel.classList.contains('open');
                closeAllTogglePanels();
                if (willOpen) panel.classList.add('open');
            });
        });
 
        document.addEventListener('click', closeAllTogglePanels);
 
        $all('.note-color-panel[data-panel-for="foreColor"] .note-swatch', toolbar).forEach(function (sw) {
            sw.addEventListener('click', function () {
                contentEl.focus();
                restoreSavedSelection();
                document.execCommand('foreColor', false, sw.getAttribute('data-color'));
                var bar = document.getElementById('noteForeColorBar');
                if (bar) bar.style.background = sw.getAttribute('data-color');
                closeAllTogglePanels();
                scheduleSave();
            });
        });
 
        $all('.note-color-panel[data-panel-for="hiliteColor"] .note-swatch', toolbar).forEach(function (sw) {
            sw.addEventListener('click', function () {
                contentEl.focus();
                restoreSavedSelection();
                var color = sw.getAttribute('data-color');
                if (!document.execCommand('hiliteColor', false, color)) {
                    document.execCommand('backColor', false, color);
                }
                var bar = document.getElementById('noteHiliteColorBar');
                if (bar) bar.style.background = color === 'transparent' ? '#fff3a0' : color;
                closeAllTogglePanels();
                scheduleSave();
            });
        });
 
        $all('.note-dropdown-panel[data-panel-for="align"] [data-cmd]', toolbar).forEach(function (btn) {
            btn.addEventListener('click', function () {
                var icon = document.getElementById('noteAlignIcon');
                var srcIcon = btn.querySelector('i');
                if (icon && srcIcon) icon.className = srcIcon.className;
                closeAllTogglePanels();
            });
        });
 
        $all('.note-dropdown-panel[data-panel-for="spacing"] [data-spacing]', toolbar).forEach(function (btn) {
            btn.addEventListener('click', function () {
                var value = btn.getAttribute('data-spacing');
                $all('p, h1, h2, h3, li', contentEl).forEach(function (block) {
                    block.style.lineHeight = value;
                });
                closeAllTogglePanels();
                scheduleSave();
            });
        });
 
        var saveTimer = null;
 
        function scheduleSave() {
            if (statusEl) statusEl.textContent = 'Editing…';
            clearTimeout(saveTimer);
            saveTimer = setTimeout(saveNow, 700);
        }
 
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
 
            fetch('./notes.php', {
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
 
        titleInput.addEventListener('input', function () {
            if (breadcrumbTitle) breadcrumbTitle.textContent = titleInput.value || 'Untitled note';
            scheduleSave();
        });
        titleInput.addEventListener('blur', saveNow);
 
        contentEl.addEventListener('input', scheduleSave);
        contentEl.addEventListener('blur', saveNow);
 
        folderSelect.addEventListener('change', saveNow);
    }
})();
 