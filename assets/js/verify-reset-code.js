
(function () {
    var form = document.getElementById('verifyCodeForm');
    if (!form) return;

    var wrap = document.getElementById('codeInputs');
    var boxes = Array.prototype.slice.call(wrap.querySelectorAll('.pin-box'));
    var codeValue = document.getElementById('codeValue');
    var submitBtn = document.getElementById('codeSubmit');
    var hint = document.getElementById('codeHint');

    var length = window.SQUIR_CODE_LENGTH || boxes.length;

    function currentValue() {
        return boxes.map(function (box) { return box.value; }).join('');
    }

    // Refresh code field state
    function refreshState() {
        boxes.forEach(function (box) {
            box.classList.toggle('filled', box.value !== '');
        });
        submitBtn.disabled = currentValue().length !== length;
    }

    function shake() {
        wrap.classList.remove('shake');
        void wrap.offsetWidth;
        wrap.classList.add('shake');
    }

    // Display code validation feedback
    function setHint(message, isError) {
        if (!hint) return;
        hint.textContent = message || '';
        hint.classList.toggle('is-error', !!isError);
    }

    boxes.forEach(function (box, index) {
        box.addEventListener('input', function () {
            box.value = box.value.replace(/\D/g, '').slice(0, 1);
            if (box.value !== '' && index < boxes.length - 1) {
                boxes[index + 1].focus();
            }
            setHint('');
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

        codeValue.value = value;
    });

    refreshState();
    if (boxes[0]) boxes[0].focus();
})();
