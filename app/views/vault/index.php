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

// pagination math para sa "Showing X to Y of Z items"
$perPage   = Vault::PER_PAGE;
$total     = (int) $data['total'];
$shown     = count($data['items']);
$rangeFrom = $total === 0 ? 0 : (($data['page'] - 1) * $perPage) + 1;
$rangeTo   = $total === 0 ? 0 : $rangeFrom + $shown - 1;
$qs        = static fn (int $page): string => '?page=' . $page
    . '&q=' . urlencode($data['search'])
    . '&folder=' . (int) $data['activeFolder']
    . '&tag=' . urlencode($data['activeTag']);
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
</head>
<body data-open-modal="<?= $escape($openModal ?? '') ?>">
<div class="app-shell" id="appShell">
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

            <?php if ($flashSuccess): ?>
                <div class="alert-squir alert-success-squir" role="status"><?= $escape($flashSuccess) ?></div>
            <?php endif; ?>
            <?php if (!empty($errors['form'])): ?>
                <div class="alert-squir alert-danger-squir" role="alert"><?= $escape($errors['form']) ?></div>
            <?php endif; ?>

            <div class="vault-toolbar">
                <div class="vault-toolbar-left">
                    <div class="vault-view-toggle" role="group" aria-label="Switch layout">
                        <button type="button" class="view-toggle-btn" data-view="grid" aria-label="Grid view" aria-pressed="false">
                            <i class="ti ti-layout-grid"></i>
                        </button>
                        <button type="button" class="view-toggle-btn active" data-view="list" aria-label="List view" aria-pressed="true">
                            <i class="ti ti-list"></i>
                        </button>
                    </div>

                    <form method="get" class="vault-search" role="search">
                        <i class="ti ti-search"></i>
                        <input type="search" name="q" placeholder="Search vault..." value="<?= $escape($data['search']) ?>">
                        <input type="hidden" name="folder" value="<?= (int) $data['activeFolder'] ?>">
                        <input type="hidden" name="tag" value="<?= $escape($data['activeTag']) ?>">
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
                        <input type="hidden" name="folder" value="<?= (int) $data['activeFolder'] ?>">
                        <i class="ti ti-chevron-down chevron"></i>
                    </form>
                </div>

                <button class="quick-add" type="button" id="openCreateModal">
                    <i class="ti ti-plus"></i>
                    <span>Add Vault</span>
                    <span class="quick-add-divider"></span>
                    <i class="ti ti-chevron-down"></i>
                </button>
            </div>

            <?php if ($data['items'] === []): ?>
                <div class="empty-state vault-empty">
                    <i class="ti ti-key"></i>
                    <p>No passwords yet</p>
                    <small>Click "Add Vault" to save your first credential.</small>
                </div>
            <?php else: ?>
                <div class="vault-table-wrap" id="vaultTableWrap">
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
                                $favicon     = Vault::faviconUrlFor($item['website_url']);
                                $isFav       = !empty($item['is_favorite']);
                                $starClass   = 'ti ti-star';
                                $itemTags    = array_filter(array_map('trim', explode(',', (string) ($item['tags'] ?? ''))));
                                $websiteHref = $item['website_url']
                                    ? (preg_match('~^https?://~i', $item['website_url']) ? $item['website_url'] : 'https://' . $item['website_url'])
                                    : null;
                                ?>
                                <tr data-vault-id="<?= (int) $item['vault_id'] ?>" data-favorite="<?= $isFav ? '1' : '0' ?>">
                                    <td class="col-item">
                                        <div class="vault-item-cell">
                                            <span class="vault-icon">
                                                <?php if ($favicon): ?>
                                                    <img src="<?= $escape($favicon) ?>" alt="" onerror="this.replaceWith(Object.assign(document.createElement('i'), {className:'ti ti-key'}))">
                                                <?php else: ?>
                                                    <i class="ti ti-key"></i>
                                                <?php endif; ?>
                                            </span>
                                            <div>
                                                <strong><?= $escape($item['title']) ?></strong>
                                                <small><?= $escape($item['website_url'] ?: 'No website linked') ?></small>
                                                <?php if ($itemTags !== []): ?>
                                                    <div class="vault-tag-chips">
                                                        <?php foreach ($itemTags as $tagName): ?>
                                                            <span class="vault-tag-chip">#<?= $escape($tagName) ?></span>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="col-user" data-label="Username / Email"><?= $escape($item['account_username'] ?: '—') ?></td>
                                    <td class="col-password" data-label="Password"><span class="vault-password" data-revealed="false">**********</span></td>
                                    <td class="col-actions">
                                        <div class="vault-actions">
                                            <button type="button" class="icon-btn vault-reveal-btn" data-tooltip="Show password" aria-label="Show password"><i class="ti ti-eye"></i></button>
                                            <button type="button" class="icon-btn vault-copy-btn" data-tooltip="Copy" aria-label="Copy password"><i class="ti ti-copy"></i></button>
                                            <button type="button" class="icon-btn vault-favorite-btn <?= $isFav ? 'is-fav' : '' ?>" data-tooltip="<?= $isFav ? 'Unfavorite' : 'Favorite' ?>" aria-label="<?= $isFav ? 'Remove from favorites' : 'Add to favorites' ?>" aria-pressed="<?= $isFav ? 'true' : 'false' ?>">
                                                <i class="<?= $starClass ?>"></i>
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
                                                    <a href="?edit=<?= (int) $item['vault_id'] ?>">
                                                        <i class="ti ti-edit"></i> Edit
                                                    </a>
                                                    <form method="post" action="./vault.php" onsubmit="return confirm('Delete this password?');">
                                                        <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="vault_id" value="<?= (int) $item['vault_id'] ?>">
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
                    <span>Showing <?= $rangeFrom ?> to <?= $rangeTo ?> of <?= $total ?> items</span>
                    <div class="vault-pages">
                        <a class="vault-page-nav <?= $data['page'] <= 1 ? 'disabled' : '' ?>"
                           href="<?= $data['page'] <= 1 ? '#' : $qs($data['page'] - 1) ?>"
                           aria-label="Previous page">
                            <i class="ti ti-chevron-left"></i>
                        </a>

                        <?php for ($p = 1; $p <= $data['pages']; $p++): ?>
                            <a class="<?= $p === $data['page'] ? 'active' : '' ?>" href="<?= $qs($p) ?>"><?= $p ?></a>
                        <?php endfor; ?>

                        <a class="vault-page-nav <?= $data['page'] >= $data['pages'] ? 'disabled' : '' ?>"
                           href="<?= $data['page'] >= $data['pages'] ? '#' : $qs($data['page'] + 1) ?>"
                           aria-label="Next page">
                            <i class="ti ti-chevron-right"></i>
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<?php require __DIR__ . '/create.php'; ?>
<?php if ($editItem): ?>
    <?php require __DIR__ . '/edit.php'; ?>
<?php endif; ?>

<script>window.VAULT_CSRF_TOKEN = <?= json_encode($csrfToken) ?>;</script>
<script src="./assets/js/dashboard.js?v=2"></script>
<script src="./assets/js/vault.js"></script>
</body>
</html>