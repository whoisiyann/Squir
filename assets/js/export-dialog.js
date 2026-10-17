/* Opens the export dialog and calls onExport with the selected format. */
(function () {
    if (window.SquirExportDialog) return;

    var FORMATS = [
        { value: 'csv', icon: 'ti-table',       label: 'CSV',  hint: 'Raw data for spreadsheets and analysis.' },
        { value: 'xlsx', icon: 'ti-table-export', label: 'Excel', hint: 'Spreadsheet with columns already sized to fit.' },
        { value: 'doc', icon: 'ti-file-text',   label: 'Docs', hint: 'Editable text and customizable layout.' },
        { value: 'pdf', icon: 'ti-file-report', label: 'PDF',  hint: 'Print-ready, non-editable official report.' }
    ];

    var ui = null;

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
                '<fieldset class="exd-options">' + options + '</fieldset>' +
                '<p class="exd-error" role="alert"></p>' +
                '<div class="exd-actions">' +
                    '<button type="button" class="exd-btn" data-exd-cancel>Cancel</button>' +
                    '<button type="button" class="exd-btn exd-btn-primary" data-exd-export>Export</button>' +
                '</div>' +
            '</div>';

        document.body.appendChild(backdrop);

        ui = {
            backdrop: backdrop,
            title: backdrop.querySelector('#exdTitle'),
            intro: backdrop.querySelector('.exd-intro'),
            error: backdrop.querySelector('.exd-error'),
            radios: backdrop.querySelectorAll('input[name="exdFormat"]'),
            exportBtn: backdrop.querySelector('[data-exd-export]'),
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

        ui.exportBtn.addEventListener('click', function () {
            var chosen = null;
            Array.prototype.forEach.call(ui.radios, function (r) { if (r.checked) chosen = r.value; });

            if (!chosen) {
                ui.error.textContent = 'Please choose a file format.';
                return;
            }

            var callback = ui.onExport;
            close();
            if (callback) callback(chosen);
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && ui.backdrop.classList.contains('open')) close();
        });
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
        ui.intro.textContent = options.intro || 'Choose the file format for your export.';
        ui.error.textContent = '';
        ui.onExport = options.onExport || null;

        // Show only the formats this export supports (xlsx is opt-in).
        var allowed = options.formats || ['csv', 'doc', 'pdf'];
        Array.prototype.forEach.call(ui.radios, function (r) {
            var show = allowed.indexOf(r.value) !== -1;
            r.closest('.exd-option').style.display = show ? '' : 'none';
            r.disabled = !show;
        });

        // Select PDF by default.
        Array.prototype.forEach.call(ui.radios, function (r) { r.checked = r.value === (options.defaultFormat || 'pdf'); });

        ui.backdrop.classList.add('open');
        ui.backdrop.setAttribute('aria-hidden', 'false');
        ui.exportBtn.focus();
    }

    window.SquirExportDialog = { open: open, close: close };
})();
