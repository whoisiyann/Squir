<?php

namespace App\Http\Controllers;

use App\Models\Folder;
use App\Models\Note;
use App\Models\PasswordEntry;
use App\Models\Task;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $userId = Auth::id();

        $stats = [
            'passwords'     => PasswordEntry::where('user_id', $userId)->count(),
            'notes'         => Note::where('user_id', $userId)->count(),
            'tasks_pending' => Task::where('user_id', $userId)->where('is_completed', false)->count(),
            'folders'       => Folder::where('user_id', $userId)->count(),
        ];

        // Para sa "Tasks Overview" panel - pending tasks, pinakamalapit na due date muna
        $upcomingTasks = Task::where('user_id', $userId)
            ->where('is_completed', false)
            ->orderByRaw('due_date IS NULL, due_date ASC')
            ->take(4)
            ->get();

        // Para sa "Recent Items" panel - pinaghalong passwords at notes, pinakabago muna
        $recentPasswords = PasswordEntry::where('user_id', $userId)
            ->latest()
            ->take(4)
            ->get()
            ->map(fn ($item) => (object) [
                'type'         => 'password',
                'title'        => $item->title,
                'subtitle'     => 'Password',
                'created_at'   => $item->created_at,
                'is_favorite'  => $item->isFavoritedBy($userId),
                'model'        => $item,
            ]);

        $recentNotes = Note::where('user_id', $userId)
            ->latest()
            ->take(4)
            ->get()
            ->map(fn ($item) => (object) [
                'type'         => 'note',
                'title'        => $item->title,
                'subtitle'     => 'Note',
                'created_at'   => $item->created_at,
                'is_favorite'  => $item->isFavoritedBy($userId),
                'model'        => $item,
            ]);

        $recentItems = $recentPasswords->concat($recentNotes)
            ->sortByDesc(fn ($item) => $item->created_at)
            ->take(4)
            ->values();

        return view('dashboard', [
            'user'          => Auth::user(),
            'stats'         => $stats,
            'upcomingTasks' => $upcomingTasks,
            'recentItems'   => $recentItems,
        ]);
    }
}
