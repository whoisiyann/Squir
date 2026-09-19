<?php
/** @var array $data */
/** @var array $user */
/** @var string $initials */
/** @var string $csrfToken */
/** @var array $errors */

$escape = static fn (?string $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$colorLabel = static fn (string $color): string => trim(preg_replace('/(?<!^)[A-Z]/', ' $0', $color));
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Squir - Folders</title>
    <script>
        (function () {
            try {
                if (window.localStorage.getItem('squir-dashboard-theme') === 'dark') {
                    document.documentElement.classList.add('dashboard-dark-preload');
                }

                if (window.localStorage.getItem('squir-folder-view') === 'list') {
                    document.documentElement.classList.add('folders-list-view-preload');
                }
            } catch (error) {}
        })();
    </script>
    <link rel="stylesheet" href="./dist/assets/fonts/tabler-icons.min.css">
    <link rel="stylesheet" href="./assets/css/style.css">
    <link rel="stylesheet" href="./assets/css/dashboard.css">
    <link rel="stylesheet" href="./assets/css/folders.css">
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

        <main class="content-area">
            <div class="page-heading">
                <div>
                    <h1>Folders</h1>
                    <p>This is where you monitor your tasks.</p>
                </div>
            </div>

            <?php if (!empty($errors['form'])): ?>
                <div class="folder-alert folder-alert-danger" role="alert"><?= $escape($errors['form']) ?></div>
            <?php endif; ?>

            <div class="folders-toolbar">
                <div class="folders-view-toggle" role="group" aria-label="Switch layout">
                    <button type="button" class="view-toggle-btn active" data-view="grid" aria-label="Grid view" aria-pressed="true"><i class="ti ti-layout-grid"></i></button>
                    <button type="button" class="view-toggle-btn" data-view="list" aria-label="List view" aria-pressed="false"><i class="ti ti-list"></i></button>
                </div>

                <div class="folders-type-tabs" role="group" aria-label="Item type">
                    <a class="type-tab <?= $data['activeType'] === 'passwords' ? 'active' : '' ?>" href="?<?= http_build_query(array_filter(['type' => 'passwords', 'q' => $data['search']])) ?>">Passwords</a>
                    <a class="type-tab <?= $data['activeType'] === 'notes' ? 'active' : '' ?>" href="?<?= http_build_query(array_filter(['type' => 'notes', 'q' => $data['search']])) ?>">Notes</a>
                </div>

                <div class="folders-toolbar-right">
                    <form method="get" class="folders-search" role="search">
                        <i class="ti ti-search"></i>
                        <input type="search" name="q" placeholder="Search folders..." value="<?= $escape($data['search']) ?>">
                        <input type="hidden" name="type" value="<?= $escape($data['activeType']) ?>">
                    </form>
                    <button type="button" class="folders-sort-btn" id="foldersSortBtn" aria-label="Sort"><i class="ti ti-arrows-sort"></i></button>
                    <button class="quick-add" type="button" id="openCreateFolderModal"><i class="ti ti-plus"></i> <span>Create A Folder</span></button>
                </div>
            </div>

            <?php if ($data['folders'] === []): ?>
                <div class="empty-state folders-empty">
                    <i class="ti ti-folder-plus"></i>
                    <p>No folders yet</p>
                    <small>Click "Create A Folder" to organize your vault.</small>
                </div>
            <?php else: ?>
                <div class="folders-count-row">
                    <p class="folders-count"><?= (int) $data['total'] ?> item<?= (int) $data['total'] === 1 ? '' : 's' ?></p>
                    <span class="folders-list-head">Date modified</span>
                </div>

                <div class="folders-grid" id="foldersGrid">
                    <?php foreach ($data['folders'] as $folder): ?>
                        <?php $isFav = !empty($folder['is_favorite']); ?>
                        <div class="folder-card" data-folder-id="<?= (int) $folder['folder_id'] ?>" data-favorite="<?= $isFav ? '1' : '0' ?>">
                            <span class="folder-icon-wrap">
                                <button type="button" class="folder-icon-btn" data-color="<?= $escape($folder['color']) ?>" aria-label="Change folder color">
                                    <img src="./assets/images/folderImages/Folder-<?= $escape($folder['color']) ?>.png" alt="">
                                </button>
                            </span>

                            <a class="folder-card-link" href="./folders?folder=<?= (int) $folder['folder_id'] ?>">
                                <span class="folder-name"><?= $escape($folder['folder_name']) ?></span>
                                <span class="folder-item-count"><?= (int) $folder['item_count'] ?> item<?= (int) $folder['item_count'] === 1 ? '' : 's' ?></span>
                            </a>

                            <span class="folder-date"><?= $escape(date('F j, g:i A', strtotime($folder['updated_at']))) ?></span>

                            <div class="folder-card-actions">
                                <button type="button" class="folder-favorite-btn <?= $isFav ? 'is-fav' : '' ?>" aria-label="<?= $isFav ? 'Remove from favorites' : 'Add to favorites' ?>" aria-pressed="<?= $isFav ? 'true' : 'false' ?>"><i class="fa-regular fa-star"></i></button>
                                <div class="folder-menu">
                                    <button type="button" class="folder-menu-btn" aria-label="More actions"><i class="ti ti-dots-vertical"></i></button>
                                    <div class="folder-menu-dropdown">
                                        <button type="button" class="folder-menu-rename"><i class="ti ti-edit"></i> Rename</button>
                                        <form method="post" action="./folders" onsubmit="return confirm('Delete this folder? Items inside will not be deleted.');">
                                            <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="folder_id" value="<?= (int) $folder['folder_id'] ?>">
                                            <button type="submit" class="folder-menu-delete"><i class="ti ti-trash"></i> Delete</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<!-- ===== Create Folder modal ===== -->
<div class="folder-modal-backdrop" id="createFolderModalBackdrop">
    <div class="folder-modal" role="dialog" aria-modal="true" aria-labelledby="createFolderTitle">
        <div class="folder-modal-header">
            <div>
                <h2 id="createFolderTitle">New Folder</h2>
                <p class="folder-modal-subtitle">Group your passwords and notes in one place.</p>
            </div>
            <button type="button" class="folder-modal-close" data-close-folder-modal aria-label="Close"><i class="ti ti-x"></i></button>
        </div>

        <form method="post" action="./folders" id="createFolderForm">
            <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
            <input type="hidden" name="action" value="create">
            <input type="hidden" name="color" id="createFolderColor" value="brown">
            <input type="hidden" name="folder_type" value="<?= $escape($data['activeType']) ?>">

            <div class="folder-field">
                <label for="folder_name">Folder name</label>
                <input id="folder_name" name="folder_name" type="text" maxlength="100" placeholder="e.g. Personal" required autocomplete="off">
            </div>
            <?php if (isset($errors['folder_name'])): ?><p class="folder-invalid-feedback"><?= $escape($errors['folder_name']) ?></p><?php endif; ?>

            <div class="folder-field">
                <label>Color</label>
                <div class="folder-color-swatches" id="createFolderSwatches">
                    <?php foreach (Folder::COLORS as $color): ?>
                        <button type="button" class="folder-swatch <?= $color === 'brown' ? 'active' : '' ?>" data-color="<?= $escape($color) ?>" aria-label="<?= $escape($colorLabel($color)) ?>">
                            <img src="./assets/images/folderImages/Folder-<?= $escape($color) ?>.png" alt="">
                        </button>
                    <?php endforeach; ?>
                </div>
                <small class="folder-field-hint">Pick a color to make the folder easier to spot.</small>
            </div>

            <div class="folder-modal-actions">
                <button type="button" class="folder-btn-outline" data-close-folder-modal>Cancel</button>
                <button type="submit" class="folder-btn"><i class="ti ti-folder-plus"></i> Create Folder</button>
            </div>
        </form>
    </div>
</div>

<!-- ===== Color picker popover ===== -->
<div class="folder-color-popover" id="folderColorPopover">
    <div class="folder-color-swatches">
        <?php foreach (Folder::COLORS as $color): ?>
            <button type="button" class="folder-swatch" data-color="<?= $escape($color) ?>" aria-label="<?= $escape($colorLabel($color)) ?>">
                <img src="./assets/images/folderImages/Folder-<?= $escape($color) ?>.png" alt="">
            </button>
        <?php endforeach; ?>
    </div>
</div>

<script>
    window.FOLDERS_CSRF_TOKEN = <?= json_encode($csrfToken) ?>;
    window.FOLDERS_HAS_ERRORS = <?= json_encode(isset($errors) && $errors !== []) ?>;
</script>
<script src="./assets/js/dashboard.js?"></script>
<script src="./assets/js/folders.js?v=3"></script>
</body>
</html>