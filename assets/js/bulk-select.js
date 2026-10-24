/* Shared selection behavior for list items. */
(function () {
    if (window.SquirBulkSelect) return;

    var LONG_PRESS_MS = 450;
    var MOVE_TOLERANCE = 8;

    function toArray(list) { return Array.prototype.slice.call(list); }

    function init(options) {
        var root = options.root;
        if (!root) return null;

        var noun = options.noun || 'item';
        var plural = options.plural || (noun + 's');
        var itemSelector = options.itemSelector;
        var getId = options.getId;
        var checkHost = options.checkHost || function (item) { return item; };

        var active = false;
        var selected = {};          // id -> true
        var lastClickedId = null;   // anchor for shift + click
        var suppressClickUntil = 0;
        var press = null;           // long press tracking
        var bar = null;
        var abortMarquee = function () {};   // set when marqueeArea is used

        /* ---------- helpers ---------- */
        function items() { return toArray(root.querySelectorAll(itemSelector)); }
        function idOf(item) { return String(getId(item)); }
        function selectedIds() { return Object.keys(selected); }
        function count() { return selectedIds().length; }

        function labelFor(n) { return n === 1 ? noun : plural; }

        /* ---------- action bar ---------- */
        function buildBar() {
            bar = document.createElement('div');
            bar.className = 'bs-bar';
            bar.setAttribute('role', 'toolbar');
            bar.setAttribute('aria-label', 'Selection actions');
            bar.hidden = true;
            bar.innerHTML =
                '<span class="bs-bar-count" aria-live="polite">0 selected</span>' +
                '<button type="button" class="bs-bar-btn" data-bs-all>Select all</button>' +
                '<button type="button" class="bs-bar-btn bs-bar-danger" data-bs-delete disabled>' +
                    '<i class="ti ti-trash" aria-hidden="true"></i><span>Delete</span>' +
                '</button>' +
                '<button type="button" class="bs-bar-btn bs-bar-cancel" data-bs-cancel aria-label="Cancel selection">' +
                    '<i class="ti ti-x" aria-hidden="true"></i><span>Cancel</span>' +
                '</button>';
            document.body.appendChild(bar);

            bar.querySelector('[data-bs-all]').addEventListener('click', toggleAll);
            bar.querySelector('[data-bs-delete]').addEventListener('click', startDelete);
            bar.querySelector('[data-bs-cancel]').addEventListener('click', function () { setActive(false); });
        }

        function updateBar() {
            if (!bar) return;
            var n = count();
            var total = items().length;
            bar.querySelector('.bs-bar-count').textContent = n + ' selected';
            bar.querySelector('[data-bs-delete]').disabled = n === 0;
            bar.querySelector('[data-bs-all]').textContent = (total > 0 && n >= total) ? 'Deselect all' : 'Select all';
            bar.querySelector('[data-bs-all]').disabled = total === 0;

            (options.toggleButtons || []).forEach(function (btn) {
                if (!btn) return;
                btn.classList.toggle('is-active', active);
                btn.setAttribute('aria-pressed', active ? 'true' : 'false');
            });
        }

        /* ---------- item state ---------- */
        function decorate(item) {
            if (item.__bsCheck && item.__bsCheck.isConnected) return;

            var box = document.createElement('span');
            box.className = 'bs-check';
            box.setAttribute('data-bs-owner', '1');
            box.setAttribute('aria-hidden', 'true');
            box.innerHTML = '<i class="ti ti-check"></i>';

            var host = checkHost(item) || item;
            host.insertBefore(box, host.firstChild);
            item.__bsCheck = box;
        }

        function paint(item) {
            var on = !!selected[idOf(item)];
            item.classList.toggle('bs-selected', on);
            item.setAttribute('aria-selected', on ? 'true' : 'false');
        }

        function syncItems() {
            var seen = {};
            items().forEach(function (item) {
                decorate(item);
                item.classList.add('bs-selectable');
                seen[idOf(item)] = true;
            });

            // Clear selections for removed items.
            selectedIds().forEach(function (id) { if (!seen[id]) delete selected[id]; });

            items().forEach(paint);
            updateBar();
        }

        function clearSelection() {
            selected = {};
            lastClickedId = null;
            items().forEach(paint);
        }

        /* ---------- mode ---------- */
        function setActive(on) {
            on = !!on;
            if (active === on) return;
            active = on;

            document.documentElement.classList.toggle('bs-active', on);
            root.classList.toggle('bs-root-active', on);

            if (on) {
                if (!bar) buildBar();
                bar.hidden = false;
                syncItems();
            } else {
                abortMarquee();
                clearSelection();
                items().forEach(function (item) {
                    item.classList.remove('bs-selectable', 'bs-selected');
                    item.removeAttribute('aria-selected');
                    if (item.__bsCheck) { item.__bsCheck.remove(); item.__bsCheck = null; }
                });
                if (bar) bar.hidden = true;
            }
            updateBar();
        }

        function setSelected(item, on) {
            var id = idOf(item);
            if (on) selected[id] = true; else delete selected[id];
            paint(item);
        }

        function toggleItem(item) {
            setSelected(item, !selected[idOf(item)]);
            lastClickedId = idOf(item);
            updateBar();
        }

        function selectRange(item) {
            var list = items();
            var from = -1;
            var to = list.indexOf(item);
            list.forEach(function (el, i) { if (idOf(el) === lastClickedId) from = i; });
            if (from === -1) { toggleItem(item); return; }

            var start = Math.min(from, to);
            var end = Math.max(from, to);
            for (var i = start; i <= end; i++) setSelected(list[i], true);
            updateBar();
        }

        function toggleAll() {
            var list = items();
            var everything = list.length > 0 && count() >= list.length;
            list.forEach(function (item) { setSelected(item, !everything); });
            lastClickedId = null;
            updateBar();
        }

        /* ---------- delete ---------- */
        function startDelete() {
            var ids = selectedIds();
            if (ids.length === 0 || !options.onDelete) return;

            var run = options.onDelete(ids, {
                count: ids.length,
                label: labelFor(ids.length),
                done: function () { setActive(false); }
            });
            return run;
        }

        /* ---------- events ---------- */
        // Clicks while selecting toggle the item instead of opening it.
        root.addEventListener('click', function (event) {
            if (!active) return;
            var item = event.target.closest(itemSelector);
            if (!item || !root.contains(item)) return;

            event.preventDefault();
            event.stopPropagation();

            if (Date.now() < suppressClickUntil) return; // the click that ends a long press

            if (event.shiftKey && lastClickedId !== null) selectRange(item);
            else toggleItem(item);
        }, true);

        // While selecting, items are not draggable (e.g. task cards)
        root.addEventListener('dragstart', function (event) {
            if (active && event.target.closest && event.target.closest(itemSelector)) {
                event.preventDefault();
                event.stopPropagation();   // the item's own drag code must not run (it would never get a dragend)
            }
        }, true);

        // Keyboard: Space/Enter toggles the focused item
        root.addEventListener('keydown', function (event) {
            if (!active) return;
            if (event.key !== 'Enter' && event.key !== ' ') return;
            var item = event.target.closest(itemSelector);
            if (!item || event.target !== item) return;
            event.preventDefault();
            event.stopPropagation();
            toggleItem(item);
        }, true);

        document.addEventListener('keydown', function (event) {
            if (!active) return;
            var tag = (event.target && event.target.tagName || '').toLowerCase();
            var typing = tag === 'input' || tag === 'textarea' || tag === 'select' ||
                (event.target && event.target.isContentEditable);

            if (event.key === 'Escape' && !typing) {
                // Let open dialogs close first
                if (document.querySelector('.sqd-dialog-backdrop.open, .swal2-container')) return;
                setActive(false);
            } else if ((event.ctrlKey || event.metaKey) && (event.key === 'a' || event.key === 'A') && !typing) {
                event.preventDefault();
                items().forEach(function (item) { setSelected(item, true); });
                updateBar();
            } else if (event.key === 'Delete' && !typing && count() > 0) {
                event.preventDefault();
                startDelete();
            }
        });

        // Long press starts select mode
        function cancelPress() {
            if (press && press.timer) clearTimeout(press.timer);
            press = null;
        }

        root.addEventListener('pointerdown', function (event) {
            if (event.pointerType === 'mouse' && event.button !== 0) return;
            if (event.target.closest('input, textarea, select, [contenteditable="true"]')) return;
            if (options.ignoreLongPress && event.target.closest(options.ignoreLongPress)) return;

            var item = event.target.closest(itemSelector);
            if (!item || !root.contains(item)) return;
            if (active) return; // in select mode a normal click is enough

            cancelPress();
            press = {
                x: event.clientX,
                y: event.clientY,
                timer: setTimeout(function () {
                    var target = item;
                    press = null;
                    setActive(true);
                    setSelected(target, true);
                    lastClickedId = idOf(target);
                    updateBar();
                    suppressClickUntil = Date.now() + 700;
                    if (navigator.vibrate) { try { navigator.vibrate(15); } catch (e) {} }
                }, LONG_PRESS_MS)
            };
        });

        root.addEventListener('pointermove', function (event) {
            if (!press) return;
            if (Math.abs(event.clientX - press.x) > MOVE_TOLERANCE || Math.abs(event.clientY - press.y) > MOVE_TOLERANCE) {
                cancelPress();
            }
        });
        ['pointerup', 'pointercancel', 'pointerleave'].forEach(function (name) {
            root.addEventListener(name, cancelPress);
        });
        window.addEventListener('scroll', cancelPress, true);

        // Prevent context menus after a long press.
        root.addEventListener('contextmenu', function (event) {
            if (press || Date.now() < suppressClickUntil) event.preventDefault();
        });


        /* ---------- marquee selection ---------- */
        var CLICK_GUARD_MS = 120;   // the click that ends a drag fires right after pointerup
        var DRAG_THRESHOLD = 5;

        function initMarquee(area) {
            var drag = null;
            var box = null;
            var raf = 0;
            var clickGuardUntil = 0;

            function interactive(target) {
                return target.closest('button, a, input, textarea, select, label, summary, [contenteditable="true"], .bs-bar');
            }

            function copy(map) {
                var out = {};
                Object.keys(map).forEach(function (key) { out[key] = true; });
                return out;
            }

            function onScrollbar(el, x, y) {
                var r = el.getBoundingClientRect();
                var wide = el.offsetWidth - el.clientWidth - el.clientLeft * 2 > 0;
                var tall = el.offsetHeight - el.clientHeight - el.clientTop * 2 > 0;
                return (wide && x - r.left - el.clientLeft >= el.clientWidth) ||
                       (tall && y - r.top - el.clientTop >= el.clientHeight);
            }

            function onDown(event) {
                if (drag) return;
                if (event.pointerType === 'touch') return;      // touch keeps scrolling the page
                if (event.button !== 0) return;
                var target = event.target;
                if (!target || !target.closest) return;
                if (interactive(target)) return;                 // buttons, links, inputs keep working
                // Allow marquee starts on items only when configured.
                if (!options.marqueeFromItems && target.closest(itemSelector)) return;

                // Ignore scrollbars
                if (onScrollbar(target, event.clientX, event.clientY) || onScrollbar(area, event.clientX, event.clientY)) return;

                drag = {
                    id: event.pointerId,
                    x: event.clientX,
                    y: event.clientY,
                    cx: event.clientX,
                    cy: event.clientY,
                    started: false,
                    wasActive: active,
                    additive: event.ctrlKey || event.metaKey || event.shiftKey,
                    base: {}
                };

                document.addEventListener('dragstart', blockSelect);   // no ghost image / native drag while selecting
                document.addEventListener('pointermove', onMove);
                document.addEventListener('pointerup', onUp);
                document.addEventListener('pointercancel', onCancel);
                document.addEventListener('scroll', schedule, true);
            }

            function begin() {
                drag.started = true;
                cancelPress();
                if (!active) setActive(true);

                // Preserve selection only for additive drags.
                drag.base = drag.additive ? copy(selected) : {};
                if (!drag.additive) {
                    selected = {};
                    lastClickedId = null;
                    items().forEach(paint);
                    updateBar();
                }

                if (!box) {
                    box = document.createElement('div');
                    box.className = 'bs-marquee';
                    box.setAttribute('aria-hidden', 'true');
                    document.body.appendChild(box);
                }
                box.hidden = false;
                document.documentElement.classList.add('bs-marqueeing');
                document.addEventListener('selectstart', blockSelect);
                if (window.getSelection) window.getSelection().removeAllRanges();
            }

            function blockSelect(event) { event.preventDefault(); }

            function update() {
                if (!drag || !drag.started) return;
                if (!active) { finish(false); return; }

                var left = Math.min(drag.x, drag.cx);
                var top = Math.min(drag.y, drag.cy);
                var right = Math.max(drag.x, drag.cx);
                var bottom = Math.max(drag.y, drag.cy);

                box.style.left = left + 'px';
                box.style.top = top + 'px';
                box.style.width = (right - left) + 'px';
                box.style.height = (bottom - top) + 'px';

                // Select items touched by the marquee.
                var hit = {};
                items().forEach(function (item) {
                    var r = item.getBoundingClientRect();
                    if (r.width === 0 || r.height === 0) return;
                    if (r.right >= left && r.left <= right && r.bottom >= top && r.top <= bottom) {
                        hit[idOf(item)] = true;
                    }
                });

                items().forEach(function (item) {
                    var id = idOf(item);
                    setSelected(item, !!(drag.base[id] || hit[id]));
                });
                updateBar();
            }

            function schedule() {
                if (raf || !drag || !drag.started) return;
                raf = requestAnimationFrame(function () { raf = 0; update(); });
            }

            function onMove(event) {
                if (!drag || event.pointerId !== drag.id) return;
                drag.cx = event.clientX;
                drag.cy = event.clientY;

                if (!drag.started) {
                    if (Math.abs(drag.cx - drag.x) < DRAG_THRESHOLD && Math.abs(drag.cy - drag.y) < DRAG_THRESHOLD) return;
                    begin();
                }
                schedule();
            }

            function onUp(event) {
                if (!drag || event.pointerId !== drag.id) return;
                drag.cx = event.clientX;
                drag.cy = event.clientY;
                if (drag.started) update();
                finish(false);
            }

            function onCancel(event) {
                if (!drag || event.pointerId !== drag.id) return;
                finish(true);
            }

            // Restore the original selection when cancelled.
            function finish(restore) {
                if (!drag) return;
                var state = drag;
                drag = null;

                document.removeEventListener('pointermove', onMove);
                document.removeEventListener('pointerup', onUp);
                document.removeEventListener('pointercancel', onCancel);
                document.removeEventListener('scroll', schedule, true);
                document.removeEventListener('selectstart', blockSelect);
                document.removeEventListener('dragstart', blockSelect);
                document.documentElement.classList.remove('bs-marqueeing');
                if (raf) { cancelAnimationFrame(raf); raf = 0; }
                if (box) box.hidden = true;

                if (!state.started) return;   // it was only a click on empty space
                clickGuardUntil = Date.now() + CLICK_GUARD_MS;

                if (restore && active) {
                    selected = state.base;
                    items().forEach(paint);
                }
                // Exit select mode when a drag selects nothing.
                if (active && !state.wasActive && count() === 0) setActive(false);
                else updateBar();
            }

            // Suppress the click after a drag.
            area.addEventListener('click', function (event) {
                if (Date.now() < clickGuardUntil) {
                    clickGuardUntil = 0;   // swallow only that one click
                    event.preventDefault();
                    event.stopPropagation();
                }
            }, true);

            area.addEventListener('pointerdown', onDown);
            abortMarquee = function () { if (drag) finish(true); };
        }

        if (options.marqueeArea) initMarquee(options.marqueeArea);

        // Keep selection in sync with list changes.
        if (window.MutationObserver) {
            var pending = false;
            new MutationObserver(function () {
                if (!active || pending) return;
                pending = true;
                requestAnimationFrame(function () { pending = false; syncItems(); });
            }).observe(root, { childList: true, subtree: true });
        }

        (options.toggleButtons || []).forEach(function (btn) {
            if (!btn) return;
            btn.addEventListener('click', function (event) {
                event.preventDefault();
                setActive(!active);
            });
        });

        return {
            enter: function () { setActive(true); },
            exit: function () { setActive(false); },
            isActive: function () { return active; },
            selectedIds: selectedIds,
            refresh: syncItems
        };
    }

    window.SquirBulkSelect = { init: init };
})();
