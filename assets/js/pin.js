// assets/js/pin.js — Create Your PIN page (create + confirm)

(function () {
    var form = document.getElementById('pinSetupForm');
    if (!form) return;

    var wrap = document.getElementById('pinInputs');
    var boxes = Array.prototype.slice.call(wrap.querySelectorAll('.pin-box'));
    var pinValue = document.getElementById('pinValue');
    var pinConfirmValue = document.getElementById('pinConfirmValue');
    var submitBtn = document.getElementById('pinSubmit');
    var titleEl = document.getElementById('pinTitle');
    var subtitleEl = document.getElementById('pinSubtitle');
    var hint = document.getElementById('pinHint');

    var length = window.SQUIR_PIN_LENGTH || boxes.length;
    var firstPin = null; // naka-store habang kino-confirm

    var COPY = {
        create: {
            title: 'Create Your PIN',
            subtitle: 'This PIN will be used to unlock your vault<br>and view your saved passwords.'
        },
        confirm: {
            title: 'Confirm Your PIN',
            subtitle: 'Type the same PIN again so we know<br>it was entered correctly.'
        }
    };

    function applyStep(step) {
        titleEl.textContent = COPY[step].title;
        subtitleEl.innerHTML = COPY[step].subtitle;
    }

    function currentValue() {
        return boxes.map(function (box) { return box.value; }).join('');
    }

    function refreshState() {
        boxes.forEach(function (box) {
            box.classList.toggle('filled', box.value !== '');
        });
        submitBtn.disabled = currentValue().length !== length;
    }

    function clearBoxes(focus) {
        boxes.forEach(function (box) { box.value = ''; });
        refreshState();
        if (focus !== false) boxes[0].focus();
    }

    function setHint(message, isError) {
        hint.textContent = message || '';
        hint.classList.toggle('is-error', !!isError);
    }

    function shake() {
        wrap.classList.remove('shake');
        void wrap.offsetWidth;
        wrap.classList.add('shake');
    }

    boxes.forEach(function (box, index) {
        box.addEventListener('input', function () {
            box.value = box.value.replace(/\D/g, '').slice(0, 1);
            if (box.value !== '' && index < boxes.length - 1) {
                boxes[index + 1].focus();
            }
            refreshState();
        });

        box.addEventListener('keydown', function (event) {
            if (event.key === 'Backspace' && box.value === '' && index > 0) {
                event.preventDefault();
                boxes[index - 1].value = '';
                boxes[index - 1].focus();
                refreshState();
                return;
            }
            if (event.key === 'ArrowLeft' && index > 0) {
                event.preventDefault();
                boxes[index - 1].focus();
            }
            if (event.key === 'ArrowRight' && index < boxes.length - 1) {
                event.preventDefault();
                boxes[index + 1].focus();
            }
        });

        box.addEventListener('paste', function (event) {
            event.preventDefault();
            var digits = (event.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '');
            for (var i = 0; i < boxes.length; i++) {
                boxes[i].value = digits[i] || '';
            }
            refreshState();
            boxes[Math.min(digits.length, boxes.length - 1)].focus();
        });

        box.addEventListener('focus', function () { box.select(); });
    });

    form.addEventListener('submit', function (event) {
        var value = currentValue();

        if (value.length !== length) {
            event.preventDefault();
            shake();
            setHint('Enter all ' + length + ' digits.', true);
            return;
        }

        // Step 1: itago muna ang unang PIN at humingi ng confirmation.
        if (firstPin === null) {
            event.preventDefault();
            firstPin = value;
            clearBoxes();
            applyStep('confirm');
            setHint('');
            return;
        }

        // Step 2: dapat magkatugma.
        if (value !== firstPin) {
            event.preventDefault();
            firstPin = null;
            clearBoxes();
            shake();
            applyStep('create');
            setHint('The PINs did not match. Start again.', true);
            return;
        }

        pinValue.value = firstPin;
        pinConfirmValue.value = value;
    });

    refreshState();
})();