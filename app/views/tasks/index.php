<?php
/** @var array $tasks */
/** @var array $user */
/** @var string $initials */
/** @var string $csrfToken */

$escape = static fn (?string $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Squir - Tasks</title>
    <script>
        (function () {
            try {
                if (window.localStorage.getItem('squir-dashboard-theme') === 'dark') {
                    document.documentElement.classList.add('dashboard-dark-preload');
                }
            } catch (error) {}
        })();
    </script>
    <link rel="stylesheet" href="./dist/assets/fonts/tabler-icons.min.css">
    <link rel="stylesheet" href="./assets/css/style.css">
    <link rel="stylesheet" href="./assets/css/dashboard.css">
    <link rel="stylesheet" href="./assets/css/tasks.css">
    <link rel="stylesheet" href="./assets/css/squir-dialogs.css">
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
        <main class="content-area tasks-page">
            <div class="page-heading">
                <div>
                    <h1>Tasks</h1>
                    <p>Get things done, one task at a time.</p>
                </div>

                <div class="tasks-search" role="search">
                    <i class="ti ti-search"></i>
                    <input type="search" id="tasksSearchInput" placeholder="Search tasks..." aria-label="Search tasks" autocomplete="off">
                </div>
            </div>

            <!-- Ang board mismo ay ginagawa ng assets/js/tasks.js galing sa window.SQUIR_TASKS -->
            <div class="tasks-board" id="tasksBoard" aria-live="polite"></div>
        </main>
    </div>
</div>

<?php require __DIR__ . '/form-modal.php'; ?>
<?php require __DIR__ . '/view-modal.php'; ?>

<script>
    window.SQUIR_TASKS = <?= json_encode(
        ['csrf' => $csrfToken, 'tasks' => $tasks],
        JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE
    ) ?>;
</script>
<script src="./assets/js/dashboard.js?v=2"></script>
<script src="./assets/js/squir-dialogs.js"></script>
<script src="./assets/js/tasks.js?v=1"></script>
</body>
</html>
