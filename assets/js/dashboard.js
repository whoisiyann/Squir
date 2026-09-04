(function () {
    var shell = document.getElementById('appShell');
    var collapseButton = document.getElementById('collapseBtn');
    var mobileButton = document.getElementById('mobileBtn');
    var backdrop = document.getElementById('sidebarBackdrop');
    var themeButton = document.getElementById('themeToggle');

    collapseButton.addEventListener('click', function () {
        shell.classList.toggle('sidebar-collapsed');
    });

    mobileButton.addEventListener('click', function () {
        shell.classList.toggle('mobile-open');
    });

    backdrop.addEventListener('click', function () {
        shell.classList.remove('mobile-open');
    });

    themeButton.addEventListener('click', function () {
        document.body.classList.toggle('dashboard-dark');
        themeButton.querySelector('i').className = document.body.classList.contains('dashboard-dark') ? 'ti ti-sun' : 'ti ti-moon';
    });
})();
