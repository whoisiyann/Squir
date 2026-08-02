<?php

// namespace App\Models;

// use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Illuminate\Database\Eloquent\Model;
// use Illuminate\Database\Eloquent\Relations\BelongsTo;
// use Illuminate\Database\Eloquent\Relations\MorphMany;

// class Note extends Model
// {
//     use HasFactory;

//     protected $fillable = ['user_id', 'folder_id', 'title', 'content'];

//     public function user(): BelongsTo
//     {
//         return $this->belongsTo(User::class);
//     }

//     public function folder(): BelongsTo
//     {
//         return $this->belongsTo(Folder::class);
//     }

//     public function favorites(): MorphMany
//     {
//         return $this->morphMany(Favorite::class, 'favoritable');
//     }
// }







namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Note extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'folder_id',
        'title',
        'content',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function folder()
    {
        return $this->belongsTo(Folder::class);
    }

    public function favorites()
    {
        return $this->morphMany(Favorite::class, 'favoritable');
    }

    public function isFavoritedBy(int $userId): bool
    {
        return $this->favorites()->where('user_id', $userId)->exists();
    }
}
