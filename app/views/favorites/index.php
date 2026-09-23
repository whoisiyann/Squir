<?php
/** @var array $data */
/** @var array $user */
/** @var string $initials */
/** @var string $csrfToken */
/** @var int $pinUnlockSeconds */

$escape = static fn (?string $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$type = $data['type'];

$typeLabel = [
    'passwords' => 'password',
    'notes'     => 'note',
    'folders'   => 'folder',
][$type];
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Squir - Favorites</title>
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
    <link rel="stylesheet" href="./assets/css/notes.css">
    <link rel="stylesheet" href="./assets/css/folders.css">
    <link rel="stylesheet" href="./assets/css/pin-modal.css">
    <link rel="stylesheet" href="./assets/css/move-folder.css">
    <link rel="stylesheet" href="./assets/css/squir-dialogs.css">
    <link rel="stylesheet" href="./assets/css/favorites.css">
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
                    <h1>Favorites</h1>
                    <p>Everything you've starred.</p>
                </div>
            </div>

            <div class="vault-toolbar favorites-toolbar">
                <div class="favorites-toolbar-left">
                    <div class="vault-view-toggle" role="group" aria-label="Switch layout">
                        <button type="button" class="view-toggle-btn" data-view="grid" aria-label="Grid view" aria-pressed="false"><i class="ti ti-layout-grid"></i></button>
                        <button type="button" class="view-toggle-btn" data-view="list" aria-label="List view" aria-pressed="false"><i class="ti ti-list"></i></button>
                    </div>

                    <form method="get" class="vault-tags-filter favorites-type-filter" action="./favorites">
                        <select name="type" onchange="this.form.submit()" aria-label="Favorites type">
                            <option value="passwords" <?= $type === 'passwords' ? 'selected' : '' ?>>Passwords</option>
                            <option value="notes" <?= $type === 'notes' ? 'selected' : '' ?>>Notes</option>
                            <option value="folders" <?= $type === 'folders' ? 'selected' : '' ?>>Folders</option>
                        </select>
                        <input type="hidden" name="q" value="<?= $escape($data['search']) ?>">
                        <i class="ti ti-star"></i>
                        <i class="ti ti-chevron-down chevron"></i>
                    </form>
                </div>

                <div class="vault-toolbar-right">
                    <form method="get" class="vault-search favorites-search" role="search" action="./favorites" autocomplete="off">
                        <i class="ti ti-search"></i>
                        <input type="search" id="favoritesSearchInput" name="q" placeholder="Search favorites..." value="<?= $escape($data['search']) ?>">
                        <input type="hidden" name="type" value="<?= $escape($type) ?>">
                    </form>
                </div>
            </div>

            <?php if ($data['items'] === []): ?>
                <div class="empty-state favorites-empty">
                    <i class="ti ti-star"></i>
                    <p>No favorites yet</p>
                    <small>Tap the star icon on a <?= $escape($typeLabel) ?> to see it here.</small>
                </div>

            <?php elseif ($type === 'passwords'): ?>
                <div class="vault-table-wrap favorites-wrap" id="favoritesWrap" data-item-type="vault">
                    <table class="vault-table">
                        <thead>
                            <tr>
                                <th class="col-item">Item</th>
                                <th class="col-user">Username / Email</th>
                                <th class="col-password">Password</th>
                                <th class="col-actions">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data['items'] as $item): ?>
                                <?php
                                $favicon = Vault::faviconUrlFor($item['website_url']);
                                $itemTags = array_filter(array_map('trim', explode(',', (string) ($item['tags'] ?? ''))));
                                $websiteHref = $item['website_url']
                                    ? (preg_match('~^https?://~i', $item['website_url']) ? $item['website_url'] : 'https://' . $item['website_url'])
                                    : null;
                                ?>
                                <tr data-item-id="<?= (int) $item['vault_id'] ?>" data-favorite="1">
                                    <td class="col-item">
                                        <div class="vault-item-cell">
                                            <span class="vault-icon">
                                                <?php if ($favicon): ?>
                                                    <img src="<?= $escape($favicon) ?>" alt="" onerror="this.replaceWith(Object.assign(document.createElement('i'), {className:'ti ti-key'}))">
                                                <?php else: ?>
                                                    <i class="ti ti-key"></i>
                                                <?php endif; ?>
                                            </span>
                                            <div class="vault-item-text">
                                                <strong><?= $escape($item['title']) ?></strong>
                                                <small class="vault-item-website"><?= $escape($item['website_url'] ?: 'No website linked') ?></small>
                                                <small class="vault-item-username"><?= $escape($item['account_username'] ?: '—') ?></small>
                                            </div>
                                            <?php if ($itemTags !== []): ?>
                                                <div class="vault-tag-chips">
                                                    <?php foreach ($itemTags as $tagName): ?>
                                                        <span class="vault-tag-chip">#<?= $escape($tagName) ?></span>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="col-user" data-label="Username / Email"><?= $escape($item['account_username'] ?: '—') ?></td>
                                    <td class="col-password" data-label="Password"><span class="vault-password" data-revealed="false">**********</span></td>
                                    <td class="col-actions">
                                        <div class="vault-actions">
                                            <button type="button" class="icon-btn vault-reveal-btn" data-tooltip="Show password" aria-label="Show password"><i class="ti ti-eye"></i></button>
                                            <button type="button" class="icon-btn vault-copy-btn" data-tooltip="Copy" aria-label="Copy password"><i class="ti ti-copy"></i></button>
                                            <button type="button" class="icon-btn vault-favorite-btn is-fav" data-tooltip="Unfavorite" aria-label="Remove from favorites" aria-pressed="true">
                                                <i class="fa-solid fa-star"></i>
                                            </button>
                                            <div class="vault-menu">
                                                <button type="button" class="icon-btn vault-menu-btn" data-tooltip="More" aria-label="More actions"><i class="ti ti-dots-vertical"></i></button>
                                                <div class="vault-menu-dropdown">
                                                    <?php if (!empty($item['account_username'])): ?>
                                                        <button type="button" class="vault-menu-copy-username" data-username="<?= $escape($item['account_username']) ?>">
                                                            <i class="ti ti-user"></i> Copy username
                                                        </button>
                                                    <?php endif; ?>
                                                    <?php if ($websiteHref): ?>
                                                        <a href="<?= $escape($websiteHref) ?>" target="_blank" rel="noopener noreferrer">
                                                            <i class="ti ti-external-link"></i> Open website
                                                        </a>
                                                    <?php endif; ?>
                                                    <button type="button" class="vault-menu-move-folder" data-move-folder-trigger
                                                            data-item-id="<?= (int) $item['vault_id'] ?>"
                                                            data-item-title="<?= $escape($item['title']) ?>"
                                                            data-folder-id="<?= $item['folder_id'] !== null ? (int) $item['folder_id'] : '' ?>">
                                                        <i class="ti ti-folder"></i> Move to folder
                                                    </button>
                                                    <a href="./vault?edit=<?= (int) $item['vault_id'] ?>">
                                                        <i class="ti ti-edit"></i> Edit
                                                    </a>
                                                    <form method="post" action="./vault" data-confirm-delete>
                                                        <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="vault_id" value="<?= (int) $item['vault_id'] ?>">
                                                        <input type="hidden" name="return_to" value="./favorites?type=passwords">
                                                        <button type="submit" class="vault-menu-delete">
                                                            <i class="ti ti-trash"></i> Delete
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="vault-pagination">
                    <span class="vault-pagination-count">
                        <i class="ti ti-star"></i>
                        <?= (int) $data['total'] ?> favorite password<?= (int) $data['total'] === 1 ? '' : 's' ?>
                    </span>
                </div>

            <?php elseif ($type === 'notes'): ?>
                <ul class="notes-list favorites-wrap" id="favoritesWrap" data-item-type="note">
                    <?php foreach ($data['items'] as $item): ?>
                        <li class="note-card" data-item-id="<?= (int) $item['note_id'] ?>" data-favorite="1">
                            <a class="note-card-link" href="./notes?note=<?= (int) $item['note_id'] ?>">
                                <span class="note-card-icon"><i class="ti ti-file-text"></i></span>
                                <span class="note-card-body">
                                    <span class="note-card-top">
                                        <strong><?= $escape(Note::titleOrDefault($item['title'])) ?></strong>
                                        <button type="button" class="note-favorite-btn is-fav" data-tooltip="Unfavorite" aria-label="Remove from favorites">
                                            <i class="fa-solid fa-star"></i>
                                        </button>
                                    </span>
                                    <small class="note-card-excerpt"><?= $escape(Note::excerptOf($item['content'])) ?></small>
                                    <span class="note-card-meta">
                                        <?php if (!empty($item['folder_name'])): ?>
                                            <span class="note-folder-chip"><i class="ti ti-folder"></i> <?= $escape($item['folder_name']) ?></span>
                                        <?php else: ?>
                                            <span></span>
                                        <?php endif; ?>
                                        <span class="note-card-date"><?= $escape(date('F j, g:i A', strtotime($item['updated_at']))) ?></span>
                                    </span>
                                </span>
                            </a>
                            <div class="note-menu">
                                <button type="button" class="icon-btn note-menu-btn" data-tooltip="More" aria-label="More actions"><i class="ti ti-dots-vertical"></i></button>
                                <div class="note-menu-dropdown">
                                    <div class="note-menu-main">
                                        <button type="button" class="note-menu-move-folder" data-move-folder-trigger
                                                data-item-id="<?= (int) $item['note_id'] ?>"
                                                data-item-title="<?= $escape(Note::titleOrDefault($item['title'])) ?>"
                                                data-folder-id="<?= $item['folder_id'] !== null ? (int) $item['folder_id'] : '' ?>">
                                            <i class="ti ti-folder"></i> Move to folder
                                        </button>
                                        <form method="post" action="./notes" data-confirm-delete>
                                            <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="note_id" value="<?= (int) $item['note_id'] ?>">
                                            <input type="hidden" name="return_to" value="./favorites?type=notes">
                                            <button type="submit" class="note-menu-delete"><i class="ti ti-trash"></i> Delete</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <div class="vault-pagination">
                    <span class="vault-pagination-count">
                        <i class="ti ti-star"></i>
                        <?= (int) $data['total'] ?> favorite note<?= (int) $data['total'] === 1 ? '' : 's' ?>
                    </span>
                </div>

            <?php else: /* folders */ ?>
                <div class="folders-grid favorites-wrap" id="favoritesWrap" data-item-type="folder">
                    <?php foreach ($data['items'] as $folder): ?>
                        <?php
                        $folderColor = (string) ($folder['color'] ?? '');
                        if (!preg_match('/^[A-Za-z]+$/', $folderColor) || !is_file(__DIR__ . '/../../../assets/images/folderImages/Folder-' . $folderColor . '.png')) {
                            $folderColor = 'brown';
                        }
                        ?>
                        <div class="folder-card" data-item-id="<?= (int) $folder['folder_id'] ?>" data-favorite="1">
                            <span class="folder-icon-wrap">
                                <span class="folder-icon-btn">
                                    <img src="./assets/images/folderImages/Folder-<?= $escape($folderColor) ?>.png" alt="">
                                </span>
                            </span>

                            <a class="folder-card-link" href="./folders?folder=<?= (int) $folder['folder_id'] ?>">
                                <span class="folder-name"><?= $escape($folder['folder_name']) ?></span>
                                <span class="folder-item-count"><?= (int) $folder['item_count'] ?> item<?= (int) $folder['item_count'] === 1 ? '' : 's' ?></span>
                            </a>

                            <span class="folder-date"><?= $escape(date('F j, g:i A', strtotime($folder['updated_at']))) ?></span>

                            <div class="folder-card-actions">
                                <button type="button" class="folder-favorite-btn is-fav" data-tooltip="Unfavorite" aria-label="Remove from favorites"><i class="fa-solid fa-star"></i></button>
                                <div class="folder-menu">
                                    <button type="button" class="folder-menu-btn" aria-label="More actions"><i class="ti ti-dots-vertical"></i></button>
                                    <div class="folder-menu-dropdown">
                                        <form method="post" action="./folders" data-confirm-delete data-confirm-text="Are you sure you want to delete this folder? All items inside will be deleted too.">
                                            <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="folder_id" value="<?= (int) $folder['folder_id'] ?>">
                                            <input type="hidden" name="return_to" value="./favorites?type=folders">
                                            <button type="submit" class="folder-menu-delete"><i class="ti ti-trash"></i> Delete</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="vault-pagination">
                    <span class="vault-pagination-count">
                        <i class="ti ti-star"></i>
                        <?= (int) $data['total'] ?> favorite folder<?= (int) $data['total'] === 1 ? '' : 's' ?>
                    </span>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<?php require __DIR__ . '/../vault/pin-modal.php'; ?>

<?php if ($type === 'passwords'): ?>
    <?php
    $moveFolders  = $data['folders'];
    $moveAction   = './vault';
    $moveIdField  = 'vault_id';
    $moveReturnTo = './favorites?type=passwords';
    require __DIR__ . '/../partials/move-folder-modal.php';
    ?>
<?php elseif ($type === 'notes'): ?>
    <?php
    $moveFolders  = $data['folders'];
    $moveAction   = './notes';
    $moveIdField  = 'note_id';
    $moveReturnTo = './favorites?type=notes';
    require __DIR__ . '/../partials/move-folder-modal.php';
    ?>
<?php endif; ?>

<script>
    window.VAULT_CSRF_TOKEN = <?= json_encode($csrfToken) ?>;
    window.SQUIR_PIN_TTL    = <?= (int) ($pinUnlockSeconds ?? 0) ?>;
    window.FAVORITES_TYPE   = <?= json_encode($type) ?>;
</script>
<script src="./assets/js/dashboard.js?v=3"></script>
<script src="./assets/js/pin-gate.js"></script>
<script src="./assets/js/move-folder.js"></script>
<script src="./assets/js/squir-dialogs.js"></script>
<script src="./assets/js/favorites.js"></script>
</body>
</html>