/* Export format and scope dialog. */
(function () {
    if (window.SquirExportDialog) return;

    var FORMATS = [
        { value: 'csv', icon: 'ti-table',       label: 'CSV',  hint: 'Raw data for spreadsheets and analysis.' },
        { value: 'xlsx', icon: 'ti-table-export', label: 'Excel', hint: 'Organized, editable spreadsheet for managing records.' },
        { value: 'doc', icon: 'ti-file-text',   label: 'Docs', hint: 'Editable text and customizable layout.' },
        { value: 'pdf', icon: 'ti-file-report', label: 'PDF',  hint: 'Print-ready, non-editable official report.' }
    ];

    var ui = null;

    function el(tag, className, text) {
        var node = document.createElement(tag);
        if (className) node.className = className;
        if (text !== undefined && text !== null) node.textContent = text;
        return node;
    }

    function build() {
        var backdrop = document.createElement('div');
        backdrop.className = 'exd-backdrop';
        backdrop.setAttribute('aria-hidden', 'true');

        var options = FORMATS.map(function (f) {
            return '<label class="exd-option">' +
                '<input type="radio" name="exdFormat" value="' + f.value + '">' +
                '<span class="exd-option-icon"><i class="ti ' + f.icon + '" aria-hidden="true"></i></span>' +
                '<span class="exd-option-text"><strong>' + f.label + '</strong><small>' + f.hint + '</small></span>' +
            '</label>';
        }).join('');

        backdrop.innerHTML =
            '<div class="exd-dialog" role="dialog" aria-modal="true" aria-labelledby="exdTitle">' +
                '<div class="exd-header">' +
                    '<h2 id="exdTitle">Export</h2>' +
                    '<button type="button" class="exd-close" aria-label="Close" data-exd-cancel><i class="ti ti-x"></i></button>' +
                '</div>' +
                '<p class="exd-intro"></p>' +
                '<div class="exd-scopes" hidden></div>' +
                '<fieldset class="exd-options">' + options + '</fieldset>' +
                '<p class="exd-error" role="alert"></p>' +
                '<div class="exd-actions">' +
                    '<button type="button" class="exd-btn" data-exd-secondary>Cancel</button>' +
                    '<button type="button" class="exd-btn exd-btn-primary" data-exd-primary>Export</button>' +
                '</div>' +
            '</div>';

        document.body.appendChild(backdrop);

        ui = {
            backdrop: backdrop,
            title: backdrop.querySelector('#exdTitle'),
            intro: backdrop.querySelector('.exd-intro'),
            error: backdrop.querySelector('.exd-error'),
            scopesBox: backdrop.querySelector('.exd-scopes'),
            formatsBox: backdrop.querySelector('.exd-options'),
            radios: backdrop.querySelectorAll('input[name="exdFormat"]'),
            secondaryBtn: backdrop.querySelector('[data-exd-secondary]'),
            primaryBtn: backdrop.querySelector('[data-exd-primary]'),
            step: 'format',
            hasScopes: false,
            scopeIntro: '',
            formatIntro: '',
            scopeRefs: [],
            scope: undefined,
            onExport: null
        };

        Array.prototype.forEach.call(backdrop.querySelectorAll('[data-exd-cancel]'), function (btn) {
            btn.addEventListener('click', close);
        });

        backdrop.addEventListener('click', function (event) {
            if (event.target === backdrop) close();
        });

        Array.prototype.forEach.call(ui.radios, function (radio) {
            radio.addEventListener('change', function () { ui.error.textContent = ''; });
        });

        // Cancel or return to the previous step
        ui.secondaryBtn.addEventListener('click', function () {
            if (ui.step === 'format' && ui.hasScopes) {
                showStep('scope');
            } else {
                close();
            }
        });

        ui.primaryBtn.addEventListener('click', function () {
            if (ui.step === 'scope') {
                var scope = collectScope();
                if (!scope) return;
                ui.scope = scope;
                showStep('format');
                return;
            }

            var chosen = null;
            Array.prototype.forEach.call(ui.radios, function (r) { if (r.checked) chosen = r.value; });

            if (!chosen) {
                ui.error.textContent = 'Please choose a file format.';
                return;
            }

            var callback = ui.onExport;
            var scopeChoice = ui.scope;
            close();
            if (callback) callback(chosen, scopeChoice);
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && ui.backdrop.classList.contains('open')) close();
        });
    }

    function showStep(step) {
        ui.step = step;
        ui.error.textContent = '';

        var scopeStep = step === 'scope';
        ui.scopesBox.hidden = !scopeStep;
        ui.formatsBox.hidden = scopeStep;

        if (scopeStep) {
            ui.intro.textContent = ui.scopeIntro;
            ui.secondaryBtn.textContent = 'Cancel';
            ui.primaryBtn.textContent = 'Next';
        } else {
            ui.intro.textContent = ui.formatIntro;
            ui.secondaryBtn.textContent = ui.hasScopes ? 'Back' : 'Cancel';
            ui.primaryBtn.textContent = 'Export';
        }
    }

    // Build export scope cards
    function buildScopes(scopes) {
        ui.scopesBox.innerHTML = '';
        ui.scopeRefs = [];

        scopes.forEach(function (scope) {
            var items = scope.items || [];
            var hasItems = items.length > 0;
            var card = el('div', 'exd-scope');


            var head = el('label', 'exd-scope-head');
            var check = el('input');
            check.type = 'checkbox';
            check.disabled = !hasItems;
            check.checked = hasItems;
            head.appendChild(check);

            var icon = el('span', 'exd-option-icon');
            var iconEl = el('i', 'ti ' + (scope.icon || 'ti-file'));
            iconEl.setAttribute('aria-hidden', 'true');
            icon.appendChild(iconEl);
            head.appendChild(icon);

            var text = el('span', 'exd-option-text');
            text.appendChild(el('strong', null, scope.label));
            text.appendChild(el('small', null, hasItems ? items.length + ' ' + (items.length === 1 ? scope.singular : scope.plural) : 'Nothing to export yet.'));
            head.appendChild(text);
            card.appendChild(head);


            var mode = el('div', 'exd-scope-mode');
            var modeName = 'exdMode-' + scope.key;

            var allLabel = el('label', 'exd-pill');
            var allRadio = el('input');
            allRadio.type = 'radio'; allRadio.name = modeName; allRadio.value = 'all'; allRadio.checked = true;
            allLabel.appendChild(allRadio);
            allLabel.appendChild(el('span', null, 'All'));
            mode.appendChild(allLabel);

            var pickLabel = el('label', 'exd-pill');
            var pickRadio = el('input');
            pickRadio.type = 'radio'; pickRadio.name = modeName; pickRadio.value = 'pick';
            pickLabel.appendChild(pickRadio);
            pickLabel.appendChild(el('span', null, 'Choose specific'));
            mode.appendChild(pickLabel);
            card.appendChild(mode);


            var list = el('div', 'exd-scope-list');
            var tools = el('div', 'exd-scope-tools');
            var selectAll = el('button', 'exd-link', 'Select all');
            selectAll.type = 'button';
            var clearAll = el('button', 'exd-link', 'Clear');
            clearAll.type = 'button';
            tools.appendChild(selectAll);
            tools.appendChild(clearAll);
            list.appendChild(tools);

            var itemChecks = items.map(function (item) {
                var row = el('label', 'exd-scope-item');
                var box = el('input');
                box.type = 'checkbox';
                box.value = String(item.id);
                row.appendChild(box);
                row.appendChild(el('span', 'exd-scope-item-label', item.label));
                if (item.hint) row.appendChild(el('small', null, item.hint));
                list.appendChild(row);
                box.addEventListener('change', function () { ui.error.textContent = ''; });
                return box;
            });
            card.appendChild(list);

            function sync() {
                var on = check.checked && hasItems;
                mode.hidden = !on;
                list.hidden = !(on && pickRadio.checked);
                card.classList.toggle('is-off', !on);
                ui.error.textContent = '';
            }

            check.addEventListener('change', sync);
            allRadio.addEventListener('change', sync);
            pickRadio.addEventListener('change', sync);
            selectAll.addEventListener('click', function () { itemChecks.forEach(function (b) { b.checked = true; }); ui.error.textContent = ''; });
            clearAll.addEventListener('click', function () { itemChecks.forEach(function (b) { b.checked = false; }); });

            sync();
            ui.scopesBox.appendChild(card);
            ui.scopeRefs.push({ key: scope.key, noun: scope.plural, check: check, pickRadio: pickRadio, itemChecks: itemChecks });
        });
    }

    // Read the selected sections
    function collectScope() {
        var result = {};
        var any = false;

        for (var i = 0; i < ui.scopeRefs.length; i++) {
            var ref = ui.scopeRefs[i];
            if (!ref.check.checked || ref.check.disabled) continue;

            if (!ref.pickRadio.checked) {
                result[ref.key] = 'all';
                any = true;
                continue;
            }

            var ids = [];
            ref.itemChecks.forEach(function (box) { if (box.checked) ids.push(Number(box.value)); });

            if (!ids.length) {
                ui.error.textContent = 'Select at least one of your ' + ref.noun + ', or choose "All".';
                return null;
            }

            result[ref.key] = ids;
            any = true;
        }

        if (!any) {
            ui.error.textContent = 'Please choose what you want to export.';
            return null;
        }

        return result;
    }

    function close() {
        if (!ui) return;
        ui.backdrop.classList.remove('open');
        ui.backdrop.setAttribute('aria-hidden', 'true');
    }

    function open(options) {
        options = options || {};
        if (!ui) build();

        ui.title.textContent = options.title || 'Export';
        ui.formatIntro = options.intro || 'Choose the file format for your export.';
        ui.scopeIntro = options.scopeIntro || 'Choose what you want to export.';
        ui.error.textContent = '';
        ui.onExport = options.onExport || null;
        ui.scope = undefined;

        // Filter available formats
        var allowed = options.formats || ['csv', 'doc', 'pdf'];
        Array.prototype.forEach.call(ui.radios, function (r) {
            var show = allowed.indexOf(r.value) !== -1;
            r.closest('.exd-option').style.display = show ? '' : 'none';
            r.disabled = !show;
        });


        Array.prototype.forEach.call(ui.radios, function (r) { r.checked = r.value === (options.defaultFormat || 'pdf'); });

        // Show the scope step when scopes are provided
        ui.hasScopes = !!(options.scopes && options.scopes.length);
        if (ui.hasScopes) buildScopes(options.scopes);
        showStep(ui.hasScopes ? 'scope' : 'format');

        ui.backdrop.classList.add('open');
        ui.backdrop.setAttribute('aria-hidden', 'false');
        ui.primaryBtn.focus();
    }

    window.SquirExportDialog = { open: open, close: close };
})();
