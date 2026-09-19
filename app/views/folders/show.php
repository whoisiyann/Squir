<?php
/**
 * @var array  $data       
 * @var array  $folder     
 * @var array  $user
 * @var string $initials
 * @var string $csrfToken
 * @var array  $errors
 * @var string|null $flashSuccess
 * @var string|null $openModal
 * @var array|null $editItem
 * @var string $returnTo
 * @var string $closeUrl
 */

$escape = static fn (?string $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$lockFolderId = (int) $folder['folder_id'];
$lockFolderName = $folder['folder_name'];
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Squir - <?= $escape($folder['folder_name']) ?></title>
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
    <link rel="stylesheet" href="./assets/css/vault.css">
    <link rel="stylesheet" href="./assets/css/pin-modal.css">
    <link rel="stylesheet" href="./assets/css/folders.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <style>
        .folder-breadcrumb { display: flex; align-items: center; gap: .4rem; font-size: .85rem; color: var(--text-muted, #6b7280); margin: 0 0 .35rem; }
        .folder-breadcrumb a { display: inline-flex; align-items: center; gap: .3rem; color: inherit; text-decoration: none; }
        .folder-breadcrumb a:hover { text-decoration: underline; }
        .folder-heading-icon { width: 28px; height: 28px; vertical-align: middle; margin-right: .4rem; }
    </style>
</head>
<body data-open-modal="<?= $escape($openModal ?? '') ?>">
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
            <div class="page-heading">
                <div>
                    <p class="folder-breadcrumb">
                        <a href="./folders"><i class="ti ti-folder"></i> Folders</a>
                        <i class="ti ti-chevron-right"></i>
                        <span><?= $escape($folder['folder_name']) ?></span>
                    </p>
                    <h1>
                        <img class="folder-heading-icon" src="./assets/images/folderImages/Folder-<?= $escape($folder['color']) ?>.png" alt="">
                        <?= $escape($folder['folder_name']) ?>
                    </h1>
                    <p>Credentials saved inside this folder.</p>
                </div>
            </div>

            <?php if ($flashSuccess): ?>
                <div class="alert-squir alert-success-squir" role="status"><?= $escape($flashSuccess) ?></div>
            <?php endif; ?>
            <?php if (!empty($errors['form'])): ?>
                <div class="alert-squir alert-danger-squir" role="alert"><?= $escape($errors['form']) ?></div>
            <?php endif; ?>

            <div class="vault-toolbar">
                <div class="vault-view-toggle" role="group" aria-label="Switch layout">
                    <button type="button" class="view-toggle-btn" data-view="grid" aria-label="Grid view" aria-pressed="false">
                        <i class="ti ti-layout-grid"></i>
                    </button>
                    <button type="button" class="view-toggle-btn active" data-view="list" aria-label="List view" aria-pressed="true">
                        <i class="ti ti-list"></i>
                    </button>
                </div>

                <div class="vault-toolbar-right">
                    <button class="quick-add" type="button" id="openCreateModal">
                        <i class="ti ti-plus"></i>
                        <span>Add Password</span>
                    </button>
                </div>
            </div>

            <?php $emptyStateButtonLabel = 'Add Password'; require __DIR__ . '/../vault/_table.php'; ?>
        </main>
    </div>
</div>

<?php require __DIR__ . '/../vault/create.php'; ?>
<?php if ($editItem): ?>
    <?php require __DIR__ . '/../vault/edit.php'; ?>
<?php endif; ?>
<?php require __DIR__ . '/../vault/pin-modal.php'; ?>

<script>
    window.VAULT_CSRF_TOKEN = <?= json_encode($csrfToken) ?>;
    window.SQUIR_PIN_TTL    = <?= (int) ($pinUnlockSeconds ?? 0) ?>;
</script>
<script src="./assets/js/dashboard.js?v=2"></script>
<script src="./assets/js/pin-gate.js"></script>
<script src="./assets/js/vault.js?v=2"></script>
</body>
</html>
