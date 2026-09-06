// assets/js/auth-transition.js

(function () {
    var leaving = false;

    document.addEventListener('keydown', function (event) {
        if (!(event.ctrlKey || event.metaKey)) {
            return;
        }

        if (event.key === '+' || event.key === '-' || event.key === '=' || event.key === '0') {
            event.preventDefault();
        }
    });

    document.addEventListener('wheel', function (event) {
        if (event.ctrlKey) {
            event.preventDefault();
        }
    }, { passive: false });

    document.addEventListener('gesturestart', function (event) {
        event.preventDefault();
    }, { passive: false });

    document.addEventListener('click', function (event) {
        var link = event.target.closest('a[href]');
        if (!link || leaving || link.target === '_blank' || link.hasAttribute('download')) {
            return;
        }

        var url = new URL(link.href, window.location.href);
        var isAuthNavigation = url.origin === window.location.origin
            && (url.pathname.endsWith('/index.php') || url.pathname.endsWith('/register.php'))
            && url.pathname !== window.location.pathname;

        if (!isAuthNavigation) {
            return;
        }

        event.preventDefault();
        leaving = true;
        document.body.classList.add('auth-page-leaving');
        window.setTimeout(function () {
            window.location.href = url.href;
        }, 280);
    });

    document.addEventListener('submit', function (event) {
        if (leaving || !event.target.matches('form')) {
            return;
        }

        event.preventDefault();
        leaving = true;
        document.body.classList.add('auth-page-leaving');
        window.setTimeout(function () {
            HTMLFormElement.prototype.submit.call(event.target);
        }, 280);
    });

    window.addEventListener('pageshow', function () {
        leaving = false;
        document.body.classList.remove('auth-page-leaving');
    });
})();
