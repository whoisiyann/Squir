<?php
/** @var array $dashboard */
/** @var string $csrfToken */
/** @var string|null $flashSuccess */

if (!isset($dashboard) || !is_array($dashboard) || empty($dashboard['user'])) {
    header('Location: ./login');
    exit;
}

$escape = static fn (?string $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');

$user = $dashboard['user'];
$counts = $dashboard['counts'];
$board = $dashboard['taskBoard'];
$firstName = explode(' ', trim($user['full_name']))[0] ?: $user['username'];
$initials = userInitials($user['full_name']);

$hour = (int) date('G');
if ($hour < 12) {
    $greeting = 'Good morning';
} elseif ($hour < 18) {
    $greeting = 'Good afternoon';
} else {
    $greeting = 'Good evening';
}

// New credential modal data
$data = ['folders' => $dashboard['vaultFolders']];
$errors = $errors ?? [];
$returnTo = './dashboard';

$plural = static fn (int $n, string $word): string => $n . ' ' . $word . ($n === 1 ? '' : 's');

$stats = [
    ['href' => './vault',   'icon' => 'ti-lock',     'label' => 'Credentials', 'value' => $counts['vault'],       'caption' => 'Passwords saved'],
    ['href' => './notes',   'icon' => 'ti-notes',    'label' => 'Notes',       'value' => $counts['notes'],       'caption' => 'Notes saved'],
    ['href' => './tasks',   'icon' => 'ti-checkbox', 'label' => 'Tasks',       'value' => $dashboard['pendingTasks'], 'caption' => 'Tasks to finish'],
    ['href' => './folders', 'icon' => 'ti-folder',   'label' => 'Folders',     'value' => $counts['folders'],     'caption' => 'Total folders'],
];

$columns = [
    'todo'        => ['label' => 'To Do',       'class' => 'task-col-todo'],
    'in_progress' => ['label' => 'In Progress', 'class' => 'task-col-progress'],
    'done'        => ['label' => 'Done',        'class' => 'task-col-done'],
];

$pageData = [
    'csrf' => $csrfToken,
    'clocks' => $dashboard['worldClocks'],
    'tzLabels' => $dashboard['timezoneLabels'],
    'maxClocks' => WorldClock::MAX_PER_USER,
    'flash' => $flashSuccess,
];
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Squir - Dashboard</title>
    <script>
        (function () {
            try {
                if (window.localStorage.getItem('squir-dashboard-theme') === 'dark') {
                    document.documentElement.classList.add('dashboard-dark-preload');
                }
            } catch (error) {}
        })();
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@500;700&display=swap">
    <link rel="stylesheet" href="./dist/assets/fonts/tabler-icons.min.css">
    <link rel="stylesheet" href="./assets/css/style.css">
    <link rel="stylesheet" href="./assets/css/dashboard.css?v=3">
    <link rel="stylesheet" href="./assets/css/vault.css">
</head>
<body class="dashboard-page">
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

        <main class="content-area">
            <!-- Add note action -->
            <form id="dashNewNoteForm" method="post" action="./notes" hidden>
                <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                <input type="hidden" name="action" value="create">
                <input type="hidden" name="folder" value="">
                <input type="hidden" name="folder_id" value="">
                <input type="hidden" name="q" value="">
            </form>

            <div class="dash-layout">
                <section class="dash-hero" aria-label="Overview">
                    <div class="dash-hero-head">
                        <div>
                            <h1><?= $escape($greeting) ?>, <?= $escape($firstName) ?>.</h1>
                            <p>Here's what's happening with your vault today.</p>
                        </div>
                        <div class="add-menu" id="addMenu">
                            <button class="add-menu-btn" id="addMenuBtn" type="button" aria-haspopup="menu" aria-expanded="false" aria-controls="addMenuPanel">
                                <i class="ti ti-plus"></i><span>Add Something</span><i class="ti ti-chevron-down add-menu-chevron"></i>
                            </button>
                            <div class="add-menu-panel" id="addMenuPanel" role="menu">
                                <button type="button" class="add-menu-item" id="openCreateModal" role="menuitem"><i class="ti ti-lock"></i><span>Vault</span></button>
                                <button type="submit" form="dashNewNoteForm" class="add-menu-item" role="menuitem"><i class="ti ti-notes"></i><span>Note</span></button>
                                <a class="add-menu-item" href="./tasks?new=1" role="menuitem"><i class="ti ti-checkbox"></i><span>Task</span></a>
                                <a class="add-menu-item" href="./folders?new=1" role="menuitem"><i class="ti ti-folder"></i><span>Folder</span></a>
                            </div>
                        </div>
                    </div>

                    <div class="dash-stats" aria-label="Vault summary">
                        <?php foreach ($stats as $stat): ?>
                            <a class="stat-card" href="<?= $escape($stat['href']) ?>">
                                <span class="stat-icon"><i class="ti <?= $escape($stat['icon']) ?>"></i></span>
                                <span class="stat-text"><strong><?= $escape($stat['label']) ?></strong><b><?= (int) $stat['value'] ?></b></span>
                                <small><?= $escape($stat['caption']) ?></small>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </section>

                <section class="dash-card dash-clocks" id="worldClocks" aria-labelledby="worldClocksTitle">
                    <div class="dash-card-head">
                        <h2 id="worldClocksTitle"><i class="ti ti-history"></i> World Clocks</h2>
                        <span class="live-pill"><span class="live-dot"></span>Live</span>
                    </div>
                    <ul class="clock-list" id="clockList"></ul>
                    <button type="button" class="clock-add-btn" id="openClockModal">+ Add city or country ></button>
                </section>

                <section class="dash-card dash-tasks" id="tasks" aria-labelledby="taskOverviewTitle">
                    <div class="dash-card-head">
                        <h2 id="taskOverviewTitle"><i class="ti ti-calendar-event"></i> Task Overview</h2>
                        <div class="task-alert-pill">
                            <span class="tap-item"><span class="tap-dot <?= $board['overdue'] > 0 ? 'tap-overdue' : 'is-zero' ?>"></span><?= $escape($plural($board['overdue'], 'task')) ?> overdue</span>
                            <span class="tap-sep"></span>
                            <span class="tap-item"><span class="tap-dot <?= $board['approaching'] > 0 ? 'tap-approaching' : 'is-zero' ?>"></span><?= $escape($plural($board['approaching'], 'task')) ?> approaching</span>
                            <a class="tap-link" href="./tasks">View tasks <i class="ti ti-chevron-right"></i></a>
                        </div>
                    </div>

                    <div class="task-columns">
                        <?php foreach ($columns as $key => $column): ?>
                            <section class="task-col <?= $escape($column['class']) ?>" aria-label="<?= $escape($column['label']) ?>">
                                <header class="task-col-head">
                                    <span class="task-col-dot"></span>
                                    <h3><?= $escape($column['label']) ?></h3>
                                    <span class="task-col-count"><?= (int) $board['counts'][$key] ?></span>
                                </header>
                                <div class="task-col-list">
                                    <?php if ($board['columns'][$key] === []): ?>
                                        <div class="mini-task-empty">No task</div>
                                    <?php else: ?>
                                        <?php foreach ($board['columns'][$key] as $task): ?>
                                            <a class="mini-task" href="./tasks?task=<?= (int) $task['task_id'] ?>">
                                                <strong title="<?= $escape($task['title']) ?>"><?= $escape($task['title']) ?></strong>
                                                <?php if ($key !== 'done'): ?>
                                                    <?php
                                                    $dueText = $task['due_label'] ?? 'No due date';
                                                    if ($task['due_state'] === 'overdue') {
                                                        $dueText = '(Overdue) ' . $dueText;
                                                    } elseif ($task['due_state'] === 'approaching') {
                                                        $dueText = '(Approaching) ' . $dueText;
                                                    }
                                                    ?>
                                                    <span class="mini-task-due<?= $task['due_state'] ? ' is-' . $escape($task['due_state']) : '' ?>"><i class="ti ti-calendar-event"></i><?= $escape($dueText) ?></span>
                                                <?php endif; ?>
                                            </a>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </section>
                        <?php endforeach; ?>
                    </div>
                </section>

                <section class="dash-card dash-recent" id="recent" aria-labelledby="recentTitle">
                    <div class="dash-card-head">
                        <h2 id="recentTitle">Recent Credentials</h2>
                    </div>

                    <?php if (empty($dashboard['recentItems'])): ?>
                        <div class="empty-state">
                            <i class="ti ti-inbox"></i>
                            <p>No credentials yet</p>
                            <small>Use Add Something &gt; Vault to save your first one.</small>
                        </div>
                    <?php else: ?>
                        <ul class="recent-list">
                            <?php foreach ($dashboard['recentItems'] as $item): ?>
                                <li class="recent-item">
                                    <a class="recent-item-link" href="./vault?highlight=<?= (int) $item['vault_id'] ?>">
                                        <span class="recent-icon">
                                            <?php if (!empty($item['icon_url'])): ?>
                                                <img src="<?= $escape($item['icon_url']) ?>" alt="" onerror="this.replaceWith(Object.assign(document.createElement('i'), {className:'ti ti-key'}))">
                                            <?php else: ?>
                                                <i class="ti ti-key"></i>
                                            <?php endif; ?>
                                        </span>
                                        <span class="recent-info">
                                            <strong><?= $escape($item['title']) ?></strong>
                                            <small><?= $escape($item['subtitle']) ?></small>
                                        </span>
                                    </a>
                                    <button type="button" class="recent-star-btn<?= $item['is_favorite'] ? ' is-favorite' : '' ?>" data-vault-id="<?= (int) $item['vault_id'] ?>" aria-pressed="<?= $item['is_favorite'] ? 'true' : 'false' ?>" aria-label="<?= $item['is_favorite'] ? 'Remove from favorites' : 'Add to favorites' ?>">
                                        <i class="<?= $item['is_favorite'] ? 'fa-solid' : 'fa-regular' ?> fa-star"></i>
                                    </button>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <?php if (count($dashboard['recentItems']) >= 10): ?>
                            <a class="recent-see-more" href="./vault">See more <i class="ti ti-arrow-right"></i></a>
                        <?php endif; ?>
                    <?php endif; ?>
                </section>
            </div>
        </main>
    </div>
</div>

<!-- Add world clock dialog -->
<div class="clock-modal-backdrop" id="clockModalBackdrop" aria-hidden="true">
    <div class="clock-modal" role="dialog" aria-modal="true" aria-labelledby="clockModalTitle">
        <button type="button" class="clock-modal-close" data-clock-close aria-label="Close"><i class="ti ti-x"></i></button>
        <h2 id="clockModalTitle">Add World Clock City</h2>
        <div class="clock-search">
            <i class="ti ti-search"></i>
            <input type="search" id="clockSearchInput" placeholder="Search city or country..." autocomplete="off" aria-label="Search city or country">
        </div>
        <p class="clock-modal-error" id="clockModalError" role="alert"></p>
        <ul class="clock-catalog" id="clockCatalog"></ul>
    </div>
</div>

<!-- New credential dialog -->
<?php require __DIR__ . '/../vault/create.php'; ?>

<script>
    window.VAULT_CSRF_TOKEN = <?= json_encode($csrfToken) ?>;
    window.SQUIR_DASH = <?= json_encode($pageData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>;
</script>
<script src="./assets/js/dashboard.js?v=3"></script>
<script src="./assets/js/vault.js?v=2"></script>
<script src="./assets/js/dashboard-home.js?v=1"></script>
</body>
</html>