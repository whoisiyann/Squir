<?php
/**
 * Move-to-folder dialog shared by Vault and Notes.
 *
 * Variables:
 * @var array  $moveFolders   list of ['folder_id', 'folder_name', 'color']
 * @var string $moveAction    form action URL
 * @var string $moveIdField   POST field name for the item ID
 * @var string $moveReturnTo  redirect target after moving
 * @var string $csrfToken
 */
$mfEsc = static fn (?string $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$moveReturnTo = $moveReturnTo ?? '';
$folderImgDir = './assets/images/folderImages/';
?>
<div class="move-modal-backdrop" id="moveFolderBackdrop" aria-hidden="true">
    <div class="move-modal" role="dialog" aria-modal="true" aria-labelledby="moveFolderTitle">
        <div class="move-modal-header">
            <span class="move-modal-header-icon" aria-hidden="true"><i class="ti ti-folder"></i></span>
            <div class="move-modal-heading">
                <h2 id="moveFolderTitle">Move to Folder</h2>
                <p class="move-modal-item" id="moveFolderItemName"></p>
            </div>
            <button type="button" class="move-modal-close" data-move-close aria-label="Close"><i class="ti ti-x"></i></button>
        </div>

        <form method="post" action="<?= $mfEsc($moveAction) ?>" id="moveFolderForm">
            <input type="hidden" name="csrf_token" value="<?= $mfEsc($csrfToken) ?>">
            <input type="hidden" name="action" value="move_folder">
            <input type="hidden" name="<?= $mfEsc($moveIdField) ?>" id="moveFolderItemId" value="">
            <input type="hidden" name="target_folder" id="moveFolderTarget" value="">
            <?php if ($moveReturnTo !== ''): ?>
                <input type="hidden" name="return_to" value="<?= $mfEsc($moveReturnTo) ?>">
            <?php endif; ?>

            <div class="move-modal-list">
                <button type="button" class="move-option" data-folder-id="">
                    <span class="move-option-icon"><img src="<?= $folderImgDir ?>Folder-brown.png" alt=""></span>
                    <span class="move-option-name">No folder</span>
                    <span class="move-option-check" aria-hidden="true"><i class="ti ti-check"></i></span>
                </button>

                <?php foreach ($moveFolders as $moveFolder): ?>
                    <?php
                    $moveColor = (string) ($moveFolder['color'] ?? '');
                    if (!preg_match('/^[A-Za-z]+$/', $moveColor) || !is_file(__DIR__ . '/../../../assets/images/folderImages/Folder-' . $moveColor . '.png')) {
                        $moveColor = 'brown';
                    }
                    ?>
                    <button type="button" class="move-option" data-folder-id="<?= (int) $moveFolder['folder_id'] ?>">
                        <span class="move-option-icon"><img src="<?= $folderImgDir ?>Folder-<?= $mfEsc($moveColor) ?>.png" alt=""></span>
                        <span class="move-option-name"><?= $mfEsc($moveFolder['folder_name']) ?></span>
                        <span class="move-option-check" aria-hidden="true"><i class="ti ti-check"></i></span>
                    </button>
                <?php endforeach; ?>
            </div>
        </form>
    </div>
</div>
