<?php
/**

 * @var array  $folder 
 * @var array  $notesData 
 * @var array  $user
 * @var string $initials
 * @var string $csrfToken
 */

$escape = static fn (?string $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$folderId = (int) $folder['folder_id'];
$folderColor = preg_match('/^[A-Za-z]+$/', (string) $folder['color']) ? $folder['color'] : 'brown';
$notesTotal = (int) $notesData['total'];
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
                    <p class="folder-breadcrumb">
                        <a href="./folders?type=notes"><i class="ti ti-folder"></i> Folders</a>
                        <i class="ti ti-chevron-right"></i>
                        <span><?= $escape($folder['folder_name']) ?></span>
                    </p>
                    <h1>
                        <img class="folder-heading-icon" src="./assets/images/folderImages/Folder-<?= $escape($folderColor) ?>.png" alt="">
                        <?= $escape($folder['folder_name']) ?>
                    </h1>
                    <p>Notes saved inside this folder.</p>
                </div>
            </div>

            <div class="folder-notes-toolbar">
                <p class="folders-count"><?= $notesTotal ?> note<?= $notesTotal === 1 ? '' : 's' ?></p>

                <form method="post" action="./notes">
                    <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                    <input type="hidden" name="action" value="create">
                    <input type="hidden" name="folder_id" value="<?= $folderId ?>">
                    <input type="hidden" name="folder" value="<?= $folderId ?>">
                    <button type="submit" class="quick-add"><i class="ti ti-plus"></i> <span>Add Note</span></button>
                </form>
            </div>

            <?php if ($notesData['items'] === []): ?>
                <div class="empty-state folders-empty">
                    <i class="ti ti-notes"></i>
                    <p>No notes in this folder yet</p>
                    <small>Click "Add Note" to write your first note here.</small>
                </div>
            <?php else: ?>
                <div class="folder-notes-grid">
                    <?php foreach ($notesData['items'] as $note): ?>
                        <?php $noteHref = './notes?' . http_build_query(['folder' => $folderId, 'note' => (int) $note['note_id']]); ?>
                        <a class="folder-note-card" href="<?= $escape($noteHref) ?>">
                            <span class="folder-note-icon"><i class="ti ti-file-text"></i></span>
                            <span class="folder-note-body">
                                <span class="folder-note-top">
                                    <strong><?= $escape(Note::titleOrDefault($note['title'])) ?></strong>
                                    <?php if (!empty($note['is_favorite'])): ?>
                                        <i class="fa-solid fa-star folder-note-fav" aria-label="Favorite"></i>
                                    <?php endif; ?>
                                </span>
                                <small class="folder-note-excerpt"><?= $escape(Note::excerptOf($note['content'])) ?></small>
                                <span class="folder-note-date"><?= $escape(date('F j, g:i A', strtotime($note['updated_at']))) ?></span>
                            </span>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<script src="./assets/js/dashboard.js?v=3"></script>
</body>
</html>
