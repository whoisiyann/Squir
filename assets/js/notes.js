(function () {
    function $all(selector, scope) { return Array.prototype.slice.call((scope || document).querySelectorAll(selector)); }

    var editor = document.getElementById('noteEditor');
    var notesList = document.getElementById('notesList');
    var menuFavBtn = document.querySelector('.note-menu-favorite'); // Favorite menu

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

    // Toggle favorite
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
        var star = btn.querySelector('i');
        if (star) star.className = isFav ? 'fa-solid fa-star' : 'ti ti-star';
    }


    function syncEditorFavoriteLabel(noteId, isFav) {
        if (!editor || !menuFavBtn) return;
        if (editor.getAttribute('data-note-id') !== String(noteId)) return;
        menuFavBtn.innerHTML = '<i class="' + (isFav ? 'fa-solid fa-star' : 'ti ti-star') + '"></i> ' + (isFav ? 'Unfavorite' : 'Favorite');
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
                menuFavBtn.innerHTML = '<i class="' + (json.is_favorite ? 'fa-solid fa-star' : 'ti ti-star') + '"></i> ' + (json.is_favorite ? 'Unfavorite' : 'Favorite');
                var card = notesList && notesList.querySelector('.note-card[data-note-id="' + noteId + '"]');
                if (card) {
                    var starBtn = card.querySelector('.note-favorite-btn');
                    if (starBtn) applyFavState(card, starBtn, !!json.is_favorite);
                }
            });
        });
    }

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
            btn.innerHTML = '<i class="' + (!wasFav ? 'fa-solid fa-star' : 'ti ti-star') + '"></i> ' + (!wasFav ? 'Unfavorite' : 'Favorite');
            syncEditorFavoriteLabel(noteId, !wasFav);

            toggleFavoriteRequest(noteId, editorCsrf).then(function (json) {
                if (starBtn) applyFavState(card, starBtn, !!json.is_favorite);
                btn.innerHTML = '<i class="' + (json.is_favorite ? 'fa-solid fa-star' : 'ti ti-star') + '"></i> ' + (json.is_favorite ? 'Unfavorite' : 'Favorite');
                syncEditorFavoriteLabel(noteId, !!json.is_favorite);
            }).catch(function () {
                if (starBtn) applyFavState(card, starBtn, wasFav);
                btn.innerHTML = '<i class="' + (wasFav ? 'fa-solid fa-star' : 'ti ti-star') + '"></i> ' + (wasFav ? 'Unfavorite' : 'Favorite');
                syncEditorFavoriteLabel(noteId, wasFav);
            });
        });
    });

    // Close menus
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

        // Save note
        function saveNow() {
            clearTimeout(saveTimer);
            if (statusEl) statusEl.textContent = 'Saving…';

            var body = new URLSearchParams();
            body.set('ajax', 'save');
            body.set('csrf_token', csrfToken);
            body.set('note_id', noteId);
            body.set('title', titleInput.value);
            body.set('content', getCleanHtml());
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

        // Update note card
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

        function isTransparentBg(value) {
            if (!value) return true;
            var v = value.trim().toLowerCase();
            return v === '' || v === 'transparent' || v === 'initial' || v === 'inherit' ||
                v === 'rgba(0, 0, 0, 0)' || v === 'rgba(0,0,0,0)';
        }

        function normalizeHighlights() {
            $all('[style*="background-color"]', contentEl).forEach(function (el) {
                if (isTransparentBg(el.style.backgroundColor)) {
                    el.classList.remove('note-hl');
                    el.style.removeProperty('background-color');
                    if (!el.getAttribute('style')) el.removeAttribute('style');
                } else {
                    el.classList.add('note-hl');
                }
            });
        }

        function stripStrayHighlights() {
            $all('[style*="background-color"]', contentEl).forEach(function (el) {
                if (el.classList.contains('note-hl')) return;
                el.style.removeProperty('background-color');
                if (!el.getAttribute('style')) el.removeAttribute('style');
            });
        }

        normalizeHighlights();

        // Highlight
        var ZWSP = '\u200B';
        var pendingHl = null; // Pending highlight

        function isBlank(text) { return text.replace(/\u200B/g, '') === ''; }

        // Find highlight
        function findHighlightEl(node) {
            var el = node && node.nodeType === 3 ? node.parentNode : node;
            while (el && el !== contentEl) {
                if (el.nodeType === 1) {
                    if (el.classList.contains('note-hl')) return el;
                    if (el.style && el.style.backgroundColor && !isTransparentBg(el.style.backgroundColor)) return el;
                }
                el = el.parentNode;
            }
            return null;
        }

        function getSelectionRange() {
            var sel = window.getSelection();
            if (!sel || sel.rangeCount === 0) return null;
            var range = sel.getRangeAt(0);
            return contentEl.contains(range.commonAncestorContainer) ? range : null;
        }

        function setCaret(node, offset) {
            var r = document.createRange();
            r.setStart(node, offset);
            r.collapse(true);
            var sel = window.getSelection();
            sel.removeAllRanges();
            sel.addRange(r);
        }

        function isVisiblyBlank(text) { return text.replace(/[\u200B\s]/g, '') === ''; }

        // Selected text nodes
        function textNodesInRange(range, skipBlank) {
            var root = range.commonAncestorContainer;
            var all = [];
            if (root.nodeType === 3) {
                all.push(root);
            } else {
                var walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT, null);
                while (walker.nextNode()) all.push(walker.currentNode);
            }
            return all.filter(function (t) {
                if (!range.intersectsNode(t)) return false;
                var start = t === range.startContainer ? range.startOffset : 0;
                var end = t === range.endContainer ? range.endOffset : t.length;
                if (end <= start) return false;
                return !skipBlank || !isVisiblyBlank(t.data.slice(start, end));
            });
        }

        // Check highlight
        function isRangeHighlighted(range) {
            if (range.collapsed) return !!findHighlightEl(range.startContainer);
            var texts = textNodesInRange(range, true);
            if (!texts.length) return false;
            return texts.every(function (t) { return !!findHighlightEl(t); });
        }

        // Isolate text nodes
        function isolateRangeTextNodes(range) {
            var nodes = textNodesInRange(range, false);
            if (!nodes.length) return [];
            var first = nodes[0], last = nodes[nodes.length - 1];
            var so = range.startContainer === first ? range.startOffset : 0;
            var eo = range.endContainer === last ? range.endOffset : last.length;
            var same = first === last;
            if (eo < last.length) last.splitText(eo);
            if (so > 0) {
                var mid = first.splitText(so);
                nodes[0] = mid;
                if (same) nodes[nodes.length - 1] = mid;
            }
            return nodes;
        }

        // Exit highlight
        function exitHighlightAtCaret(range) {
            var hl = findHighlightEl(range.startContainer);
            if (!hl) return;
            var parent = hl.parentNode;

            var tailRange = document.createRange();
            tailRange.setStart(range.startContainer, range.startOffset);
            tailRange.setEnd(hl, hl.childNodes.length);
            var tailFrag = tailRange.extractContents();

            var marker = document.createTextNode(ZWSP);
            parent.insertBefore(marker, hl.nextSibling);

            if (!isBlank(tailFrag.textContent || '')) {
                var tail = hl.cloneNode(false);
                tail.appendChild(tailFrag);
                parent.insertBefore(tail, marker.nextSibling);
            }
            if (isBlank(hl.textContent || '')) parent.removeChild(hl);
            if (pendingHl && !pendingHl.parentNode) pendingHl = null;

            setCaret(marker, 1);
        }

        // Enter highlight
        function enterHighlightAtCaret(range) {
            var span = document.createElement('span');
            span.className = 'note-hl';
            var text = document.createTextNode(ZWSP);
            span.appendChild(text);
            range.insertNode(span);
            pendingHl = span;
            setCaret(text, 1);
        }

        // Split highlight
        function isolateInHighlight(t, hl) {
            var parent = hl.parentNode;
            var r = document.createRange();
            r.setStartAfter(t);
            r.setEnd(hl, hl.childNodes.length);
            var after = r.extractContents();
            r = document.createRange();
            r.setStart(hl, 0);
            r.setEndBefore(t);
            var before = r.extractContents();
            if (!isBlank(after.textContent || '')) {
                var a = hl.cloneNode(false);
                a.appendChild(after);
                parent.insertBefore(a, hl.nextSibling);
            }
            if (!isBlank(before.textContent || '')) {
                var b = hl.cloneNode(false);
                b.appendChild(before);
                parent.insertBefore(b, hl);
            }
        }

        function removeHighlightFromNodes(nodes) {
            nodes.forEach(function (t) {
                var guard = 0;
                var hl = findHighlightEl(t);
                while (hl && guard++ < 10) {
                    if (hl.tagName === 'SPAN') {
                        isolateInHighlight(t, hl);
                        var p = hl.parentNode;
                        while (hl.firstChild) p.insertBefore(hl.firstChild, hl);
                        p.removeChild(hl);
                    } else {
                        hl.style.removeProperty('background-color');
                        hl.classList.remove('note-hl');
                        if (!hl.getAttribute('style')) hl.removeAttribute('style');
                    }
                    hl = findHighlightEl(t);
                }
            });
        }

        function applyHighlightToNodes(nodes) {
            removeHighlightFromNodes(nodes); // Remove duplicates
            var spans = [];
            nodes.forEach(function (t) {
                var structural = /^\s+$/.test(t.data) &&
                    (t.parentNode === contentEl || /^(UL|OL)$/.test(t.parentNode.nodeName));
                if (structural) return;
                var span = document.createElement('span');
                span.className = 'note-hl';
                t.parentNode.insertBefore(span, t);
                span.appendChild(t);
                spans.push(span);
            });
            spans.forEach(function (span) {
                var prev = span.previousSibling;
                if (prev && prev.nodeType === 1 && prev.tagName === 'SPAN' && prev.classList.contains('note-hl')) {
                    while (span.firstChild) prev.appendChild(span.firstChild);
                    span.parentNode.removeChild(span);
                }
            });
        }

        function toggleHighlight() {
            var range = getSelectionRange();
            if (!range) return;
            var on = isRangeHighlighted(range);
            if (range.collapsed) {
                if (on) exitHighlightAtCaret(range); else enterHighlightAtCaret(range);
                return;
            }
            var nodes = isolateRangeTextNodes(range);
            if (!nodes.length) return;
            if (on) removeHighlightFromNodes(nodes); else applyHighlightToNodes(nodes);

            // Keep selection
            var first = nodes[0], last = nodes[nodes.length - 1];
            var r = document.createRange();
            r.setStart(first, 0);
            r.setEnd(last, last.length);
            var sel = window.getSelection();
            sel.removeAllRanges();
            sel.addRange(r);
        }

        // Clean pending highlight
        function cleanupPendingHighlight() {
            if (!pendingHl) return;
            if (!isBlank(pendingHl.textContent || '')) { pendingHl = null; return; }
            var sel = window.getSelection();
            var inside = sel && sel.rangeCount && pendingHl.contains(sel.anchorNode);
            if (!inside) {
                if (pendingHl.parentNode) pendingHl.parentNode.removeChild(pendingHl);
                pendingHl = null;
            }
        }

        // Clean saved HTML
        function getCleanHtml() {
            var clone = contentEl.cloneNode(true);
            $all('.note-hl', clone).forEach(function (el) {
                if (isBlank(el.textContent || '') && !el.querySelector('img, br')) el.parentNode.removeChild(el);
            });
            return clone.innerHTML.replace(/\u200B/g, '');
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

            var hlBtn = toolbar.querySelector('[data-cmd="hiliteColor"]');
            if (hlBtn) {
                var hlRange = getSelectionRange();
                hlBtn.classList.toggle('active', !!hlRange && isRangeHighlighted(hlRange));
            }
        }

        toolbar.addEventListener('mousedown', function (event) {
            if (event.target.closest('button')) event.preventDefault();
        });

        $all('[data-cmd]', toolbar).forEach(function (el) {
            el.addEventListener('click', function () {
                var cmd = el.getAttribute('data-cmd');
                var value = el.getAttribute('data-value');
                contentEl.focus();

                if (cmd === 'createLink') {
                    var url = window.prompt('Link URL:', 'https://');
                    if (!url) return;
                    document.execCommand('createLink', false, url);
                    stripStrayHighlights();
                } else if (cmd === 'formatBlock') {
                    var current = document.queryCommandValue('formatBlock').toLowerCase();
                    document.execCommand('formatBlock', false, current === value ? 'p' : value);
                    stripStrayHighlights();
                } else if (cmd === 'hiliteColor') {
                    toggleHighlight();
                } else {
                    document.execCommand(cmd, false, null);
                    stripStrayHighlights();
                }
                scheduleSave();
                updateToolbarState();
                updateEmptyState();
            });
        });

        contentEl.addEventListener('keyup', updateToolbarState);
        contentEl.addEventListener('mouseup', updateToolbarState);
        document.addEventListener('selectionchange', function () {
            cleanupPendingHighlight();
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
            normalizeHighlights();
            scheduleSave();
            updateEmptyState();
        });
        contentEl.addEventListener('blur', saveNow);

        folderSelect.addEventListener('change', saveNow);
    }
})();