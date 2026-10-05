<?php
/** @var array $tasks */
/** @var array $user */
/** @var string $initials */
/** @var string $csrfToken */
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Squir - Calendar</title>
    <link rel="icon" href="./assets/images/squir.png" type="image/x-icon" />
    <script>
        (function () {
            try {
                var serverPrefs = <?= preferencesJson() ?>;
                if (serverPrefs.theme) window.localStorage.setItem('squir-dashboard-theme', serverPrefs.theme);
                if (serverPrefs.accent) window.localStorage.setItem('squir-accent', serverPrefs.accent);
                if (window.localStorage.getItem('squir-dashboard-theme') === 'dark') {
                    document.documentElement.classList.add('dashboard-dark-preload');
                }
                var savedAccent = window.localStorage.getItem('squir-accent');
                if (savedAccent && savedAccent !== 'brown') document.documentElement.setAttribute('data-accent', savedAccent);
            } catch (error) {}
        })();
    </script>
    <link rel="stylesheet" href="./dist/assets/fonts/tabler-icons.min.css">
    <link rel="stylesheet" href="./assets/css/style.css">
    <link rel="stylesheet" href="./assets/css/dashboard.css">
    <link rel="stylesheet" href="./assets/css/tasks.css">
    <link rel="stylesheet" href="./assets/css/calendar.css?v=3">
    <link rel="stylesheet" href="./assets/css/squir-dialogs.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
</head>
<body>
<div class="app-shell" id="appShell">
<script>
    try {
        if (window.localStorage.getItem('squir-sidebar-collapsed') === '1') {
            document.getElementById('appShell').classList.add('sidebar-collapsed');
        }
    } catch (error) {}
</script>
    <?php require __DIR__ . '/../../../includes/sidebar.php'; ?>

    <div class="main-area">
        <?php require __DIR__ . '/../../../includes/header.php'; ?>
        <main class="content-area calendar-page">
            <div class="page-heading">
                <div>
                    <h1>Calendar</h1>
                    <p>Tasks across your schedule.</p>
                </div>

                <div class="cal-toolbar">
                    <div class="tasks-search cal-search" role="search">
                        <i class="ti ti-search"></i>
                        <input type="search" id="calSearchInput" placeholder="Search" aria-label="Search calendar tasks" autocomplete="off">
                    </div>

                    <div class="cal-seg" role="group" aria-label="Calendar view">
                        <button type="button" class="cal-seg-btn" data-view="week">Week</button>
                        <button type="button" class="cal-seg-btn" data-view="month">Month</button>
                        <button type="button" class="cal-seg-btn" data-view="year">Year</button>
                    </div>

                    <div class="cal-nav">
                        <button type="button" class="cal-nav-btn" id="calPrev" aria-label="Previous month"><i class="ti ti-chevron-left"></i></button>
                        <button type="button" class="cal-today-btn" id="calToday">Today</button>
                        <button type="button" class="cal-nav-btn" id="calNext" aria-label="Next month"><i class="ti ti-chevron-right"></i></button>
                    </div>

                    <button type="button" class="cal-add-btn" id="calAddBtn" aria-label="Add task"><i class="ti ti-plus"></i></button>
                </div>
            </div>

            <div class="cal-layout">
                <section class="cal-main" id="calMain" aria-live="polite"></section>
                <aside class="cal-side" id="calSide" aria-label="Calendar summary"></aside>
            </div>
        </main>
    </div>
</div>

<?php require __DIR__ . '/../tasks/form-modal.php'; ?>
<?php require __DIR__ . '/../tasks/view-modal.php'; ?>

<div class="task-modal-backdrop" id="calDayBackdrop" aria-hidden="true">
    <div class="task-modal cal-day-modal" role="dialog" aria-modal="true" aria-labelledby="calDayTitle">
        <button type="button" class="task-modal-close" data-task-close aria-label="Close"><i class="ti ti-x"></i></button>
        <h2 class="task-modal-title" id="calDayTitle"></h2>

        <div class="cal-day-list" id="calDayList"></div>

        <div class="task-modal-actions">
            <button type="button" class="task-btn task-btn-primary" id="calDayAdd">Add task</button>
        </div>
    </div>
</div>

<script>
    window.SQUIR_CALENDAR = <?= json_encode(
        ['csrf' => $csrfToken, 'tasks' => $tasks],
        JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE
    ) ?>;
</script>
<script src="./assets/js/dashboard.js?v=3"></script>
<script src="./assets/js/squir-dialogs.js"></script>
<script src="./assets/js/calendar.js?v=1"></script>
</body>
</html>
