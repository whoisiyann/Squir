(function () {
    var formView = document.querySelector('.auth-card .auth-form');
    var termsView = document.getElementById('termsView');
    if (!formView || !termsView) return;

    function showTerms(event) {
        if (event) event.preventDefault();
        formView.hidden = true;
        termsView.hidden = false;
    }

    function showForm() {
        termsView.hidden = true;
        formView.hidden = false;
    }

    Array.prototype.slice.call(document.querySelectorAll('[data-terms-view]')).forEach(function (trigger) {
        trigger.addEventListener('click', showTerms);
    });

    var closeBtn = document.getElementById('termsViewClose');
    if (closeBtn) closeBtn.addEventListener('click', showForm);

    // Reading and clicking "Continue" counts as agreeing, so tick the checkbox for them.
    var continueBtn = document.getElementById('termsViewContinue');
    if (continueBtn) {
        continueBtn.addEventListener('click', function () {
            var termsCheckbox = document.getElementById('terms');
            if (termsCheckbox) {
                termsCheckbox.checked = true;
                var error = document.getElementById('terms-error');
                if (error) error.hidden = true;
            }
            showForm();
        });
    }
})();