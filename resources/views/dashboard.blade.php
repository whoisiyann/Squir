@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-brand-dark">Good morning, {{ explode(' ', $user->name)[0] }}! 👋</h1>
            <p class="text-[#8a7360] text-sm">Here's what's happening with your vault today.</p>
        </div>
        <button class="bg-brand hover:bg-brand-dark text-white px-4 py-2 rounded-lg text-sm font-medium">
            + Quick Add
        </button>
    </div>

    {{-- Stat cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-[#eee0cf] p-5">
            <div class="text-2xl mb-1">🔒</div>
            <div class="text-2xl font-bold text-brand-dark">{{ $stats['passwords'] }}</div>
            <div class="text-sm text-[#8a7360]">Total saved</div>
        </div>
        <div class="bg-white rounded-xl border border-[#eee0cf] p-5">
            <div class="text-2xl mb-1">📝</div>
            <div class="text-2xl font-bold text-brand-dark">{{ $stats['notes'] }}</div>
            <div class="text-sm text-[#8a7360]">Total notes</div>
        </div>
        <div class="bg-white rounded-xl border border-[#eee0cf] p-5">
            <div class="text-2xl mb-1">✅</div>
            <div class="text-2xl font-bold text-brand-dark">{{ $stats['tasks_pending'] }}</div>
            <div class="text-sm text-[#8a7360]">Pending tasks</div>
        </div>
        <div class="bg-white rounded-xl border border-[#eee0cf] p-5">
            <div class="text-2xl mb-1">📁</div>
            <div class="text-2xl font-bold text-brand-dark">{{ $stats['folders'] }}</div>
            <div class="text-sm text-[#8a7360]">Total folders</div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Tasks Overview --}}
        <div class="bg-white rounded-xl border border-[#eee0cf] p-5">
            <div class="flex items-center justify-between mb-3">
                <h2 class="font-semibold text-brand-dark">Tasks Overview</h2>
                @if (Route::has('tasks.index'))
                    <a href="{{ route('tasks.index') }}" class="text-xs text-brand font-medium">View all</a>
                @endif
            </div>

            @forelse ($upcomingTasks as $task)
                <div class="flex items-center justify-between py-2.5 border-b border-[#f3ead9] last:border-0 text-sm">
                    <div class="flex items-center gap-2">
                        <span class="w-4 h-4 rounded-full border-2 border-[#d8c6ae] inline-block"></span>
                        <span>{{ $task->title }}</span>
                    </div>
                    <span class="text-xs font-medium {{ $task->due_label_color }}">{{ $task->due_label }}</span>
                </div>
            @empty
                <p class="text-sm text-[#a08768]">Wala ka pang task. Idagdag mo na yung una mo!</p>
            @endforelse
        </div>

        {{-- Recent Items --}}
        <div class="bg-white rounded-xl border border-[#eee0cf] p-5">
            <div class="flex items-center justify-between mb-3">
                <h2 class="font-semibold text-brand-dark">Recent Items</h2>
                @if (Route::has('favorites.index'))
                    <a href="{{ route('favorites.index') }}" class="text-xs text-brand font-medium">View all</a>
                @endif
            </div>

            @forelse ($recentItems as $item)
                <div class="flex items-center justify-between py-2.5 border-b border-[#f3ead9] last:border-0 text-sm">
                    <div class="flex items-center gap-3">
                        <span class="text-lg">{{ $item->type === 'password' ? '🔒' : '📝' }}</span>
                        <div>
                            <div class="font-medium">{{ $item->title }}</div>
                            <div class="text-xs text-[#a08768]">{{ $item->subtitle }}</div>
                        </div>
                    </div>
                    <span class="{{ $item->is_favorite ? 'text-yellow-500' : 'text-[#e0d3bd]' }}">★</span>
                </div>
            @empty
                <p class="text-sm text-[#a08768]">Wala ka pang laman. Magsimula ka na mag-imbak!</p>
            @endforelse
        </div>
    </div>
@endsection
