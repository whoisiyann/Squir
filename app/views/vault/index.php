<?php
/** @var array $data */
/** @var array $user */
/** @var string $initials */
/** @var string $csrfToken */
/** @var array $errors */
/** @var string|null $flashSuccess */
/** @var string|null $openModal */
/** @var array|null $editItem */

$escape = static fn (?string $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Squir - Passwords</title>
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
    <link rel="stylesheet" href="./assets/css/move-folder.css">
    <link rel="stylesheet" href="./assets/css/squir-dialogs.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
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
                    <h1>Vault</h1>
                    <p>Manage and secure your saved passwords.</p>
                </div>
            </div>

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
                    <form method="get" class="vault-search" role="search" id="vaultSearchForm" autocomplete="off">
                        <i class="ti ti-search"></i>
                        <input type="search" name="q" id="vaultSearchInput" placeholder="Search vault..." value="<?= $escape($data['search']) ?>">
                        <input type="hidden" name="folder" value="<?= $data['activeFolder'] !== null ? (int) $data['activeFolder'] : '' ?>">
                        <input type="hidden" name="tag" value="<?= $escape($data['activeTag']) ?>">
                        <div class="vault-search-suggestions" id="vaultSearchSuggestions"></div>
                    </form>

                    <form method="get" class="vault-tags-filter">
                        <i class="ti ti-tag"></i>
                        <select name="tag" onchange="this.form.submit()">
                            <option value="">All Tags</option>
                            <?php foreach ($data['tags'] as $tagName => $count): ?>
                                <option value="<?= $escape($tagName) ?>" <?= $data['activeTag'] === $tagName ? 'selected' : '' ?>>
                                    #<?= $escape($tagName) ?> (<?= $count ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <input type="hidden" name="q" value="<?= $escape($data['search']) ?>">
                        <input type="hidden" name="folder" value="<?= $data['activeFolder'] !== null ? (int) $data['activeFolder'] : '' ?>">
                        <i class="ti ti-chevron-down chevron"></i>
                    </form>

                    <button class="quick-add" type="button" id="openCreateModal">
                        <i class="ti ti-plus"></i>
                        <span>Add Vault</span>
                    </button>
                </div>
            </div>

            <?php require __DIR__ . '/_table.php'; ?>
        </main>
    </div>
</div>

<?php require __DIR__ . '/create.php'; ?>
<?php if ($editItem): ?>
    <?php require __DIR__ . '/edit.php'; ?>
<?php endif; ?>
<?php require __DIR__ . '/pin-modal.php'; ?>
<?php
$moveFolders  = $data['folders'];
$moveAction   = './vault';
$moveIdField  = 'vault_id';
$moveReturnTo = $returnTo ?? './vault';
require __DIR__ . '/../partials/move-folder-modal.php';
?>

<script>
    window.VAULT_CSRF_TOKEN = <?= json_encode($csrfToken) ?>;
    window.SQUIR_PIN_TTL    = <?= (int) ($pinUnlockSeconds ?? 0) ?>;
    window.VAULT_FLASH      = <?= json_encode($flashSuccess) ?>;
</script>
<script src="./assets/js/dashboard.js?v=3"></script>
<script src="./assets/js/pin-gate.js"></script>
<script src="./assets/js/vault.js?v=2"></script>
<script src="./assets/js/move-folder.js"></script>
<script src="./assets/js/squir-dialogs.js"></script>