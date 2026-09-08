<!-- view/vault/edit.php -->

<?php
/** @var array $data */
/** @var array $errors */
/** @var string $csrfToken */
/** @var array $editItem */
$escape = $escape ?? static fn (?string $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$favicon = Vault::faviconUrlFor($editItem['website_url']);
?>
<div class="vault-modal-backdrop open" id="editModalBackdrop">
    <div class="vault-modal" role="dialog" aria-modal="true" aria-labelledby="editModalTitle">
        <div class="vault-modal-header">
            <div>
                <h2 id="editModalTitle">Edit Vault</h2>
                <p class="vault-modal-subtitle">Update the details of this credential.</p>
            </div>
            <a class="icon-btn" href="./vault.php" aria-label="Close"><i class="ti ti-x"></i></a>
        </div>

        <div class="vault-edit-preview">
            <span class="vault-icon">
                <?php if ($favicon): ?>
                    <img src="<?= $escape($favicon) ?>" alt="" onerror="this.replaceWith(Object.assign(document.createElement('i'), {className:'ti ti-key'}))">
                <?php else: ?>
                    <i class="ti ti-key"></i>
                <?php endif; ?>
            </span>
            <div>
                <strong><?= $escape($editItem['title']) ?></strong>
                <small><?= $escape($editItem['website_url'] ?: 'No website linked') ?></small>
            </div>
        </div>

        <form method="post" action="./vault.php">
            <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="vault_id" value="<?= (int) $editItem['vault_id'] ?>">
            <input type="hidden" name="is_favorite" id="editIsFavorite" value="<?= $editItem['is_favorite'] ? '1' : '0' ?>">

            <div class="vault-field-grid-2">
                <div class="vault-field">
                    <label for="e_title">Name</label>
                    <input id="e_title" name="title" type="text" maxlength="100" value="<?= $escape($editItem['title']) ?>" required>
                </div>
                <div class="vault-field">
                    <label for="e_url">Website</label>
                    <div class="vault-url-field">
                        <input id="e_url" name="website_url" type="text" value="<?= $escape($editItem['website_url']) ?>" placeholder="example.com">
                        <span class="vault-favicon-badge" id="editFaviconPreview">
                            <?php if ($favicon): ?><img src="<?= $escape($favicon) ?>" alt=""><?php else: ?><i class="ti ti-key"></i><?php endif; ?>
                        </span>
                    </div>
                </div>
            </div>
            <?php if (isset($errors['title'])): ?><p class="invalid-feedback-squir"><?= $escape($errors['title']) ?></p><?php endif; ?>
            <?php if (isset($errors['website_url'])): ?><p class="invalid-feedback-squir"><?= $escape($errors['website_url']) ?></p><?php endif; ?>

            <div class="vault-field-grid-2">
                <div class="vault-field">
                    <label for="e_username">Username / Email</label>
                    <input id="e_username" name="account_username" type="text" maxlength="100" value="<?= $escape($editItem['account_username']) ?>">
                </div>
                <div class="vault-field">
                    <label for="e_password">Password</label>
                    <div class="vault-password-field">
                        <input id="e_password" name="account_password" type="password" placeholder="**********"
    autocomplete="new-password" data-lpignore="true" data-1p-ignore data-bwignore="true">
                        <div class="vault-password-actions">
                            <button type="button" class="toggle-password" data-target="e_password" data-vault-id="<?= (int) $editItem['vault_id'] ?>" aria-label="Show password"><i class="ti ti-eye"></i></button>
                            <button type="button" class="generate-password" data-target="e_password" aria-label="Generate password"><i class="ti ti-wand"></i></button>
                        </div>
                    </div>
                </div>
            </div>
            <?php if (isset($errors['account_password'])): ?><p class="invalid-feedback-squir"><?= $escape($errors['account_password']) ?></p><?php endif; ?>

            <div class="vault-field-row">
                <div class="vault-field">
                    <label for="e_folder">Folder</label>
                    <select id="e_folder" name="folder_id">
                        <option value="">No folder</option>
                        <?php foreach ($data['folders'] as $folder): ?>
                            <option value="<?= (int) $folder['folder_id'] ?>" <?= (int) $editItem['folder_id'] === (int) $folder['folder_id'] ? 'selected' : '' ?>>
                                <?= $escape($folder['folder_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="vault-field">
                    <label>Favorite</label>
                    <button type="button" class="vault-favorite-toggle <?= $editItem['is_favorite'] ? 'active' : '' ?>" id="editFavoriteToggle" aria-pressed="<?= $editItem['is_favorite'] ? 'true' : 'false' ?>" aria-label="Mark as favorite">
                        <i class="fa-solid fa-star"></i>
                    </button>
                </div>
            </div>

            <div class="vault-field">
                <label for="e_notes">Notes (optional)</label>
                <textarea id="e_notes" name="notes" rows="3" placeholder="Add recovery hints, setup notes, or context."><?= $escape($editItem['notes']) ?></textarea>
            </div>

            <div class="vault-field">
                <label for="e_tags">Tags</label>
                <input id="e_tags" name="tags" type="text" maxlength="150" value="<?= $escape(Vault::tagsToString($editItem['tags'] ?? '')) ?>" placeholder="dev, finance (max 5)">
                <small class="vault-field-hint">Separate with commas. Up to <?= Vault::MAX_TAGS ?> tags.</small>
            </div>
            <?php if (isset($errors['tags'])): ?><p class="invalid-feedback-squir"><?= $escape($errors['tags']) ?></p><?php endif; ?>

            <div class="vault-modal-actions vault-modal-actions-3">
                <a class="btn-outline-squir" href="./vault.php">Cancel</a>
                <button type="button" class="btn-outline-squir vault-copy-password-btn" data-vault-id="<?= (int) $editItem['vault_id'] ?>">Copy password</button>
                <button type="submit" class="btn-squir">Save Changes</button>
            </div>
        </form>
    </div>
</div>
