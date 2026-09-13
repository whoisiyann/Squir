<?php
/** @var array $data */
/** @var array $user */
/** @var string $initials */
/** @var string $csrfToken */
/** @var array $errors */
/** @var array|null $activeNote */
/** @var int|null $activeNoteId */

$escape = static fn (?string $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Squir - Notes</title>
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
    <link rel="stylesheet" href="./assets/css/notes.css">
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

        <main class="content-area notes-content-area">
            <div class="notes-layout">

                <!-- ===== LEFT: notes list ===== -->
                <section class="notes-list-panel" aria-label="Notes list">
                    <div class="notes-list-heading">
                        <div>
                            <h1>Notes</h1>
                            <p>Write it down. Keep it organized.</p>
                        </div>
                        <a class="icon-btn" href="#folders" data-tooltip="Manage folders" aria-label="Manage folders"><i class="ti ti-folder"></i></a>
                    </div>

                    <div class="notes-toolbar">
                        <form method="get" class="notes-search" role="search" id="notesSearchForm" autocomplete="off">
                            <i class="ti ti-search"></i>
                            <input type="search" name="q" id="notesSearchInput" placeholder="Search notes..." value="<?= $escape($data['search']) ?>">
                            <input type="hidden" name="folder" value="<?= $data['activeFolder'] !== null ? (int) $data['activeFolder'] : '' ?>">
                        </form>

                        <form method="post" action="./notes.php">
                            <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                            <input type="hidden" name="action" value="create">
                            <input type="hidden" name="folder" value="<?= $data['activeFolder'] !== null ? (int) $data['activeFolder'] : '' ?>">
                            <input type="hidden" name="q" value="<?= $escape($data['search']) ?>">
                            <button type="submit" class="notes-add-btn" aria-label="Create note"><i class="ti ti-plus"></i></button>
                        </form>
                    </div>

                    <div class="notes-folder-pills" role="group" aria-label="Filter by folder">
                        <a class="folder-pill <?= $data['activeFolder'] === null ? 'active' : '' ?>"
                           href="?<?= http_build_query(array_filter(['q' => $data['search']])) ?>">All</a>
                        <?php foreach ($data['folderCounts'] as $folder): ?>
                            <a class="folder-pill <?= $data['activeFolder'] === (int) $folder['folder_id'] ? 'active' : '' ?>"
                               href="?<?= http_build_query(array_filter(['folder' => (int) $folder['folder_id'], 'q' => $data['search']])) ?>">
                                <?= $escape($folder['folder_name']) ?>
                            </a>
                        <?php endforeach; ?>
                    </div>

                    <?php if ($data['items'] === []): ?>
                        <div class="empty-state notes-empty">
                            <i class="ti ti-notes"></i>
                            <p>No notes yet</p>
                            <small>Tap the + button to write your first note.</small>
                        </div>
                    <?php else: ?>
                        <ul class="notes-list" id="notesList">
                            <?php foreach ($data['items'] as $item): ?>
                                <?php $isActive = $activeNoteId === (int) $item['note_id']; ?>
                                <li class="note-card <?= $isActive ? 'active' : '' ?>" data-note-id="<?= (int) $item['note_id'] ?>" data-favorite="<?= $item['is_favorite'] ? '1' : '0' ?>">
                                    <a class="note-card-link" href="?<?= http_build_query(array_filter(['folder' => $data['activeFolder'], 'q' => $data['search'], 'note' => $item['note_id']])) ?>">
                                        <span class="note-card-icon"><i class="ti ti-file-text"></i></span>
                                        <span class="note-card-body">
                                            <span class="note-card-top">
                                                <strong><?= $escape(Note::titleOrDefault($item['title'])) ?></strong>
                                                <button type="button" class="note-favorite-btn <?= $item['is_favorite'] ? 'is-fav' : '' ?>" data-tooltip="<?= $item['is_favorite'] ? 'Unfavorite' : 'Favorite' ?>" aria-label="Favorite">
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
                                            <form method="post" action="./notes.php" onsubmit="return confirm('Delete this note?');">
                                                <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="note_id" value="<?= (int) $item['note_id'] ?>">
                                                <button type="submit" class="note-menu-delete"><i class="ti ti-trash"></i> Delete</button>
                                            </form>
                                        </div>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </section>

                <!-- ===== RIGHT: note detail / editor ===== -->
                <section class="note-detail-panel" aria-label="Note detail">
                    <?php if (!$activeNote): ?>
                        <div class="empty-state note-detail-empty">
                            <i class="ti ti-file-text"></i>
                            <p>No note selected</p>
                            <small>Choose a note from the list or create a new one.</small>
                            <form method="post" action="./notes.php">
                                <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                                <input type="hidden" name="action" value="create">
                                <button type="submit" class="quick-add"><i class="ti ti-plus"></i> Create note</button>
                            </form>
                        </div>
                    <?php else: ?>
                        <div class="note-editor" id="noteEditor" data-note-id="<?= (int) $activeNote['note_id'] ?>" data-csrf="<?= $escape($csrfToken) ?>">
                            <div class="note-editor-topline">
                                <span class="note-breadcrumb">Notes <i class="ti ti-chevron-right"></i> <span id="noteBreadcrumbTitle"><?= $escape(Note::titleOrDefault($activeNote['title'])) ?></span></span>
                                <div class="note-editor-actions">
                                    <span class="note-save-status" id="noteSaveStatus"></span>
                                    <span class="note-last-modified"><i class="ti ti-clock"></i> Last modified <?= $escape(date('F j, g:i A', strtotime($activeNote['updated_at']))) ?></span>
                                    <div class="note-menu">
                                        <button type="button" class="icon-btn note-menu-btn note-detail-menu-btn" aria-label="More actions"><i class="ti ti-dots-vertical"></i></button>
                                        <div class="note-menu-dropdown">
                                            <button type="button" class="note-menu-favorite">
                                                <i class="fa-solid fa-star"></i> <?= $activeNote['is_favorite'] ? 'Unfavorite' : 'Favorite' ?>
                                            </button>
                                            <form method="post" action="./notes.php" onsubmit="return confirm('Delete this note?');">
                                                <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="note_id" value="<?= (int) $activeNote['note_id'] ?>">
                                                <button type="submit" class="note-menu-delete"><i class="ti ti-trash"></i> Delete</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <input type="text" class="note-title-input" id="noteTitleInput" value="<?= $escape($activeNote['title']) ?>" placeholder="Untitled note" maxlength="100">

                            <div class="note-folder-row">
                                <label for="noteFolderSelect" class="visually-hidden">Folder</label>
                                <span class="note-folder-select-wrap">
                                    <select id="noteFolderSelect" class="note-folder-select">
                                        <option value="">No folder</option>
                                        <?php foreach ($data['folders'] as $folder): ?>
                                            <option value="<?= (int) $folder['folder_id'] ?>" <?= (int) $activeNote['folder_id'] === (int) $folder['folder_id'] ? 'selected' : '' ?>>
                                                <?= $escape($folder['folder_name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <i class="ti ti-chevron-down note-folder-select-icon" aria-hidden="true"></i>
                                </span>
                            </div>

                                <div class="note-toolbar" role="toolbar" aria-label="Formatting">
                                    <button type="button" class="note-heading-btn" data-cmd="formatBlock" data-value="h1" data-tooltip="Heading 1" aria-label="Heading 1">H1</button>
                                    <button type="button" class="note-heading-btn" data-cmd="formatBlock" data-value="h2" data-tooltip="Heading 2" aria-label="Heading 2">H2</button>
                                    <button type="button" class="note-heading-btn" data-cmd="formatBlock" data-value="h3" data-tooltip="Heading 3" aria-label="Heading 3">H3</button>
                                    <span class="note-toolbar-sep"></span>
                                    <button type="button" data-cmd="bold" data-tooltip="Bold" aria-label="Bold"><i class="ti ti-bold"></i></button>
                                    <button type="button" data-cmd="italic" data-tooltip="Italic" aria-label="Italic"><i class="ti ti-italic"></i></button>
                                    <button type="button" data-cmd="underline" data-tooltip="Underline" aria-label="Underline"><i class="ti ti-underline"></i></button>
                                    <button type="button" data-cmd="strikeThrough" data-tooltip="Strikethrough" aria-label="Strikethrough"><i class="ti ti-strikethrough"></i></button>
                                    <span class="note-toolbar-sep"></span>
                                    <button type="button" class="note-highlight-btn" data-cmd="hiliteColor" data-value="var(--highlight-color)" data-tooltip="Highlight" aria-label="Highlight"><i class="ti ti-palette"></i></button>
                                    <span class="note-toolbar-sep"></span>
                                    <button type="button" data-cmd="justifyLeft" data-tooltip="Align left" aria-label="Align left"><i class="ti ti-align-left"></i></button>
                                    <button type="button" data-cmd="justifyCenter" data-tooltip="Align center" aria-label="Align center"><i class="ti ti-align-center"></i></button>
                                    <button type="button" data-cmd="justifyRight" data-tooltip="Align right" aria-label="Align right"><i class="ti ti-align-right"></i></button>
                                    <button type="button" data-cmd="justifyFull" data-tooltip="Justify" aria-label="Justify"><i class="ti ti-align-justified"></i></button>
                                    <span class="note-toolbar-sep"></span>
                                    <button type="button" data-cmd="insertUnorderedList" data-tooltip="Bullet list" aria-label="Bullet list"><i class="ti ti-list"></i></button>
                                    <button type="button" data-cmd="insertOrderedList" data-tooltip="Numbered list" aria-label="Numbered list"><i class="ti ti-list-numbers"></i></button>
                                    <span class="note-toolbar-sep"></span>
                                    <button type="button" data-cmd="outdent" data-tooltip="Decrease indent" aria-label="Decrease indent"><i class="ti ti-indent-decrease"></i></button>
                                    <button type="button" data-cmd="indent" data-tooltip="Increase indent" aria-label="Increase indent"><i class="ti ti-indent-increase"></i></button>
                                    <span class="note-toolbar-sep"></span>
                                    <button type="button" data-cmd="createLink" data-tooltip="Insert link" aria-label="Insert link"><i class="ti ti-link"></i></button>
                                </div>
                            <!--
                                Content is stored as sanitized HTML (see NoteController::sanitizeContent),
                                so it is safe to output raw here rather than htmlspecialchars-escaped.
                            -->
                            <div class="note-content-editable" id="noteContentEditable" contenteditable="true"><?= $activeNote['content'] !== null && $activeNote['content'] !== '' ? $activeNote['content'] : '<p></p>' ?></div>
                        </div>
                    <?php endif; ?>
                </section>

            </div>
        </main>
    </div>
</div>
<script src="./assets/js/dashboard.js?v=2"></script>
<script src="./assets/js/notes.js"></script>
</body>
</html>