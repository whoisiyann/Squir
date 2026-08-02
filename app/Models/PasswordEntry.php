<?php

// namespace App\Models;

// use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Illuminate\Database\Eloquent\Model;
// use Illuminate\Database\Eloquent\Relations\BelongsTo;
// use Illuminate\Database\Eloquent\Relations\HasMany;

// class Folder extends Model
// {
//     use HasFactory;

//     protected $fillable = ['user_id', 'name', 'description', 'color'];

//     public function user(): BelongsTo
//     {
//         return $this->belongsTo(User::class);
//     }

//     public function passwordEntries(): HasMany
//     {
//         return $this->hasMany(PasswordEntry::class);
//     }

//     public function notes(): HasMany
//     {
//         return $this->hasMany(Note::class);
//     }
// }






namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PasswordEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'folder_id',
        'title',
        'username_email',
        'password',
        'website',
    ];

    protected $casts = [
        // ito na yung "store encrypted, never plain text" na sinabi mo sa migration
        'password' => 'encrypted',
    ];

    protected $hidden = [
        'password',
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
