
// Toggle registration password visibility
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

// Validate email before submit
function emailProblem(email) {
    var parts = email.split('@');
    var generic = 'Please enter a valid email address.';

    if (email === '' || parts.length !== 2 || parts[0] === '' || parts[1] === '' || parts[0].length > 64) {
        return generic;
    }

    var local = parts[0];
    var domain = parts[1];

    // Gmail usernames only allow letters, numbers and periods
    var isGmail = domain === 'gmail.com' || domain === 'googlemail.com';

    if (isGmail) {
        if (!/^[a-z0-9.]+$/.test(local)) {
            return 'Sorry, only letters (a-z), numbers (0-9), and periods (.) are allowed.';
        }
    } else if (!/^[a-z0-9._+-]+$/.test(local)) {
        return 'Email can only use letters, numbers, and . _ + - before the @.';
    }

    if (!/^[a-z0-9]/.test(local) || !/[a-z0-9]$/.test(local)) {
        return 'Email must start and end with a letter or number before the @.';
    }

    if (/[._+-]{2,}/.test(local)) {
        return 'Email cannot have two special characters in a row, like .. or --.';
    }

    if (isGmail && (local.length < 6 || local.length > 30)) {
        return 'Gmail usernames must be between 6 and 30 characters.';
    }

    if (!/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,24}$/.test(domain)) {
        return 'Please enter a valid email domain, like gmail.com.';
    }

    return '';
}

var emailInput = document.getElementById('email');
if (emailInput && emailInput.form) {
    emailInput.form.addEventListener('submit', function (event) {
        var problem = emailProblem(emailInput.value.trim().toLowerCase());
        if (!problem) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();

        var error = document.getElementById('email-error');
        if (!error) {
            error = document.createElement('p');
            error.className = 'invalid-feedback-squir';
            error.id = 'email-error';
            emailInput.closest('.input-group-squir').insertAdjacentElement('afterend', error);
        }

        error.textContent = problem;
        error.hidden = false;
        emailInput.focus();
    });
}
