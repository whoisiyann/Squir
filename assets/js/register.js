// assets/js/register.js

document.querySelectorAll('.toggle-password').forEach(function (button) {
    button.addEventListener('click', function () {
        var input = document.getElementById(button.dataset.target);
        var visible = input.type === 'text';
        input.type = visible ? 'password' : 'text';
        button.setAttribute('aria-label', visible ? 'Show password' : 'Hide password');
        button.querySelector('i').className = visible ? 'ti ti-eye' : 'ti ti-eye-off';
    });
});

document.querySelectorAll('.input-group-squir input').forEach(function (input) {
    input.addEventListener('input', function () {
        var error = document.getElementById(input.id + '-error');
        if (error) {
            error.hidden = true;
        }
    });
});

var terms = document.getElementById('terms');
if (terms) {
    terms.addEventListener('change', function () {
        var error = document.getElementById('terms-error');
        if (error) {
            error.hidden = true;
        }
    });
}
