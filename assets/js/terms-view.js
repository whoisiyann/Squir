(function () {
    var formView = document.querySelector('.auth-card .auth-form');
    var termsView = document.getElementById('termsView');
    if (!formView || !termsView) return;
    var transitionDuration = window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 0 : 200;

    function swapViews(fromView, toView) {
        fromView.classList.add('terms-view-leaving');

        window.setTimeout(function () {
            fromView.hidden = true;
            fromView.classList.remove('terms-view-leaving');
            toView.hidden = false;
            toView.classList.add('terms-view-entering');

            window.setTimeout(function () {
                toView.classList.remove('terms-view-entering');
            }, transitionDuration ? 300 : 0);
        }, transitionDuration);
    }

    function showTerms(event) {
        if (event) event.preventDefault();
        if (!formView.hidden) swapViews(formView, termsView);
    }

    function showForm() {
        if (!termsView.hidden) swapViews(termsView, formView);
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