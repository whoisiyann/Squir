<?php
/**
 * @var array  $data
 * @var string $csrfToken
 * @var string $returnTo
 */
$returnTo = $returnTo ?? './vault';
$emptyStateButtonLabel = $emptyStateButtonLabel ?? 'Add Vault';
?>
<?php if ($data['items'] === []): ?>
    <div class="empty-state vault-empty">
        <i class="ti ti-key"></i>
        <p>No passwords yet</p>
        <small>Click "<?= htmlspecialchars($emptyStateButtonLabel, ENT_QUOTES, 'UTF-8') ?>" to save your first credential.</small>
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
                    $starClass   = 'fa-regular fa-star';
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

                            <?php if (!empty($item['notes'])): ?>
                                <p class="vault-item-notes"><?= $escape($item['notes']) ?></p>
                            <?php endif; ?>
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
                                        <button type="button" class="vault-menu-move-folder" data-move-folder-trigger
                                                data-item-id="<?= (int) $item['vault_id'] ?>"
                                                data-item-title="<?= $escape($item['title']) ?>"
                                                data-folder-id="<?= $item['folder_id'] !== null ? (int) $item['folder_id'] : '' ?>">
                                            <i class="ti ti-folder"></i> Move to folder
                                        </button>
                                        <a href="?edit=<?= (int) $item['vault_id'] ?>">
                                            <i class="ti ti-edit"></i> Edit
                                        </a>
                                        <form method="post" action="./vault" data-confirm-delete>
                                            <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="vault_id" value="<?= (int) $item['vault_id'] ?>">
                                            <input type="hidden" name="return_to" value="<?= $escape($returnTo) ?>">
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

    <div class="vault-pagination" id="vaultPagination">
        <span class="vault-pagination-count">
            <i class="ti ti-shield-lock"></i>
            <?= (int) $data['total'] ?> vault item<?= (int) $data['total'] === 1 ? '' : 's' ?>
        </span>
        <span class="vault-pagination-hint" id="vaultScrollHint">
            <i class="ti ti-arrow-down"></i>
            Scroll down to see more items
        </span>
    </div>
<?php endif; ?>
