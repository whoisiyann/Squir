<?php

// namespace App\Models;

// use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Illuminate\Database\Eloquent\Model;
// use Illuminate\Database\Eloquent\Relations\BelongsTo;

// class Task extends Model
// {
//     use HasFactory;

//     protected $fillable = ['user_id', 'title', 'description', 'due_date', 'priority', 'is_completed'];

//     protected $casts = [
//         'due_date' => 'date',
//         'is_completed' => 'boolean',
//     ];

//     public function user(): BelongsTo
//     {
//         return $this->belongsTo(User::class);
//     }
// }







namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'due_date',
        'priority',
        'is_completed',
    ];

    protected $casts = [
        'due_date'     => 'date',
        'is_completed' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Ginagamit ng dashboard view para sa "Due today / Due tomorrow / Due May 15 / No due date"
    public function getDueLabelAttribute(): string
    {
        if (! $this->due_date) {
            return 'No due date';
        }

        if ($this->due_date->isToday()) {
            return 'Due today';
        }

        if ($this->due_date->isTomorrow()) {
            return 'Due tomorrow';
        }

        return 'Due '.$this->due_date->format('M j');
    }

    // Kulay ng label sa Tasks Overview (pula kapag due today/overdue, orange kapag bukas)
    public function getDueLabelColorAttribute(): string
    {
        if (! $this->due_date) {
            return 'text-gray-400';
        }

        if ($this->due_date->isPast() || $this->due_date->isToday()) {
            return 'text-red-500';
        }

        if ($this->due_date->isTomorrow()) {
            return 'text-orange-500';
        }

        return 'text-gray-500';
    }
}
