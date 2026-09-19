<?php
/**
 * Notes "Manage folders" popup: folder list + New folder, Rename Folder dialog, Delete confirm dialog.
 *
 * @var array  $data         (NoteController::index) -> $data['folderCounts'] = folder_id, folder_name, note_count
 * @var int    $totalNotes   bilang ng lahat ng notes ng user (para sa "All")
 * @var string $csrfToken
 */
$nfEsc = static fn (?string $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
?>
<!-- ===== Folders panel ===== -->
<div class="nf-backdrop" id="nfPanelBackdrop" aria-hidden="true">
    <div class="nf-panel" role="dialog" aria-modal="true" aria-labelledby="nfPanelTitle">
        <div class="nf-panel-header">
            <button type="button" class="nf-square-btn" id="nfBackBtn" aria-label="Back"><i class="ti ti-arrow-left"></i></button>
            <h2 id="nfPanelTitle">Folders</h2>
            <button type="button" class="nf-square-btn" id="nfDeleteBtn" aria-label="Delete selected folder" disabled><i class="ti ti-trash"></i></button>
        </div>

        <div class="nf-list" id="nfList">
            <div class="nf-row" data-folder-id="" data-folder-name="All">
                <button type="button" class="nf-row-select">
                    <i class="ti ti-circle-check nf-row-check" aria-hidden="true"></i>
                    <span class="nf-row-name">All</span>
                </button>
                <span class="nf-row-count"><?= (int) $totalNotes ?></span>
            </div>

            <?php foreach ($data['folderCounts'] as $nfFolder): ?>
                <div class="nf-row" data-folder-id="<?= (int) $nfFolder['folder_id'] ?>" data-folder-name="<?= $nfEsc($nfFolder['folder_name']) ?>">
                    <button type="button" class="nf-row-select">
                        <i class="ti ti-circle-check nf-row-check" aria-hidden="true"></i>
                        <span class="nf-row-name"><?= $nfEsc($nfFolder['folder_name']) ?></span>
                    </button>
                    <button type="button" class="nf-row-rename" aria-label="Rename folder"><i class="ti ti-pencil"></i></button>
                    <span class="nf-row-count"><?= (int) $nfFolder['note_count'] ?></span>
                </div>
            <?php endforeach; ?>
        </div>

        <button type="button" class="nf-new" id="nfNewBtn">
            <i class="ti ti-circle-plus" aria-hidden="true"></i>
            <span>New folder</span>
        </button>
    </div>
</div>

<!-- ===== Rename / New folder dialog ===== -->
<div class="sqd-dialog-backdrop" id="nfNameBackdrop" aria-hidden="true">
    <div class="sqd-dialog" role="dialog" aria-modal="true" aria-labelledby="nfNameTitle">
        <div class="sqd-dialog-header">
            <h2 id="nfNameTitle">Rename Folder</h2>
            <button type="button" class="sqd-dialog-close" id="nfNameClose" aria-label="Close"><i class="ti ti-x"></i></button>
        </div>
        <label class="sqd-label" for="nfNameInput" id="nfNameLabel">Rename to</label>
        <input type="text" class="sqd-input" id="nfNameInput" maxlength="100" autocomplete="off" placeholder="Folder name">
        <p class="sqd-error" id="nfNameError" role="alert"></p>
        <div class="sqd-dialog-actions">
            <button type="button" class="sqd-btn sqd-btn-outline" id="nfNameCancel">Cancel</button>
            <button type="button" class="sqd-btn sqd-btn-outline" id="nfNameOk">OK</button>
        </div>
    </div>
</div>

<!-- ===== Delete confirm dialog ===== -->
<div class="sqd-dialog-backdrop" id="nfDeleteBackdrop" aria-hidden="true">
    <div class="sqd-dialog sqd-dialog-center" role="alertdialog" aria-modal="true" aria-labelledby="nfDeleteTitle" aria-describedby="nfDeleteText">
        <i class="ti ti-trash sqd-delete-icon" aria-hidden="true"></i>
        <h2 id="nfDeleteTitle">Delete</h2>
        <p id="nfDeleteText">Are you sure you want to delete this?</p>
        <p class="sqd-error" id="nfDeleteError" role="alert"></p>
        <div class="sqd-dialog-actions">
            <button type="button" class="sqd-btn sqd-btn-outline" id="nfDeleteCancel">Cancel</button>
            <button type="button" class="sqd-btn sqd-btn-danger" id="nfDeleteConfirm">Delete</button>
        </div>
    </div>
</div>
