<?php
require_once __DIR__ . '/../models/Favorite.php';

class FavoriteController
{
    private Favorite $favoriteModel;
    private PDO $dbh;

    public function __construct(Favorite $favoriteModel, PDO $dbh)
    {
        $this->favoriteModel = $favoriteModel;
        $this->dbh = $dbh;
    }

    // Load user favorites
    public function index(int $userId, string $type, string $search): array
    {
        $type = in_array($type, ['passwords', 'notes', 'folders'], true) ? $type : 'passwords';

        if ($type === 'notes') {
            $items = $this->favoriteModel->notesForUser($userId, $search);
        } elseif ($type === 'folders') {
            $items = $this->favoriteModel->foldersForUser($userId, $search);
            foreach ($items as &$folder) {
                $folder['item_count'] = $folder['folder_type'] === 'notes'
                    ? (int) $folder['note_count']
                    : (int) $folder['vault_count'];
            }
            unset($folder);
        } else {
            $items = $this->favoriteModel->vaultForUser($userId, $search);
        }

        return [
            'type'    => $type,
            'items'   => $items,
            'total'   => count($items),
            'search'  => $search,
            'counts'  => $this->favoriteModel->countsForUser($userId),
            'folders' => $type !== 'folders' ? $this->getFolders($userId, $type) : [],
        ];
    }

    // Load folders for moving items
    private function getFolders(int $userId, string $type): array
    {
        $folderType = $type === 'notes' ? 'notes' : 'passwords';
        $stmt = $this->dbh->prepare(
            "SELECT folder_id, folder_name, color FROM folders WHERE user_id = :uid AND folder_type = :type ORDER BY folder_name"
        );
        $stmt->execute(['uid' => $userId, 'type' => $folderType]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
