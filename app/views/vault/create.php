<!-- view/vault/create.php -->

<?php
/** @var array $data */
/** @var array $errors */
/** @var string $csrfToken */
$escape = $escape ?? static fn (?string $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
?>
<div class="vault-modal-backdrop" id="createModalBackdrop">
    <div class="vault-modal" role="dialog" aria-modal="true" aria-labelledby="createModalTitle">
        <div class="vault-modal-header">
            <div>
                <h2 id="createModalTitle">New Credential</h2>
                <p class="vault-modal-subtitle">Save a new credential to keep it secure.</p>
            </div>
            <button type="button" class="icon-btn" data-close-modal="create" aria-label="Close"><i class="ti ti-x"></i></button>
        </div>

        <form method="post" action="./vault.php">
            <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
            <input type="hidden" name="action" value="create">
            <input type="hidden" name="is_favorite" id="createIsFavorite" value="0">

            <div class="vault-field">
                <label for="v_title">Name</label>
                <input id="v_title" name="title" type="text" maxlength="100" placeholder="e.g. GitHub" required>
            </div>
            <?php if (isset($errors['title'])): ?><p class="invalid-feedback-squir"><?= $escape($errors['title']) ?></p><?php endif; ?>

            <div class="vault-field">
                <label for="v_username">Username / Email</label>
                <input id="v_username" name="account_username" type="text" maxlength="100" placeholder="you@example.com">
            </div>

            <div class="vault-field">
                <label for="v_password">Password</label>
                <div class="vault-password-field">
                    <input id="v_password" name="account_password" type="password" placeholder="password" utocomplete="new-password" data-lpignore="true" data-1p-ignore data-bwignore="true" required>
                    <div class="vault-password-actions">
                        <button type="button" class="toggle-password" data-target="v_password" aria-label="Show password"><i class="ti ti-eye"></i></button>
                        <button type="button" class="generate-password" data-target="v_password" aria-label="Generate password"><i class="ti ti-wand"></i></button>
                    </div>
                </div>
            </div>
            <?php if (isset($errors['account_password'])): ?><p class="invalid-feedback-squir"><?= $escape($errors['account_password']) ?></p><?php endif; ?>

            <div class="vault-field">
                <label for="v_url">Website</label>
                <div class="vault-url-field">
                    <input id="v_url" name="website_url" type="text" placeholder="example.com">
                    <span class="vault-favicon-badge" id="createFaviconPreview"><i class="ti ti-key"></i></span>
                </div>
            </div>
            <?php if (isset($errors['website_url'])): ?><p class="invalid-feedback-squir"><?= $escape($errors['website_url']) ?></p><?php endif; ?>

            <div class="vault-field-row">
                <div class="vault-field">
                    <label for="v_folder">Folder</label>
                    <select id="v_folder" name="folder_id">
                        <option value="">No folder</option>
                        <?php foreach ($data['folders'] as $folder): ?>
                            <option value="<?= (int) $folder['folder_id'] ?>"><?= $escape($folder['folder_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="vault-field">
                    <label>Favorite</label>
                    <button type="button" class="vault-favorite-toggle" id="createFavoriteToggle" aria-pressed="false" aria-label="Mark as favorite">
                        <i class="fa-solid fa-star"></i>
                    </button>
                </div>
            </div>

            <div class="vault-field">
                <label for="v_notes">Notes (optional)</label>
                <textarea id="v_notes" name="notes" rows="3" placeholder="Add recovery hints, setup notes, or context."></textarea>
            </div>

            <div class="vault-field">
                <label for="v_tags">Tags</label>
                <input id="v_tags" name="tags" type="text" maxlength="150" placeholder="dev, finance (max 5)">
                <small class="vault-field-hint">Separate with commas. Up to <?= Vault::MAX_TAGS ?> tags.</small>
            </div>
            <?php if (isset($errors['tags'])): ?><p class="invalid-feedback-squir"><?= $escape($errors['tags']) ?></p><?php endif; ?>

            <div class="vault-modal-actions">
                <button type="button" class="btn-outline-squir" data-close-modal="create">Cancel</button>
                <button type="submit" class="btn-squir"><i class="ti ti-lock"></i> Save Credential</button>
            </div>
        </form>
    </div>
</div>