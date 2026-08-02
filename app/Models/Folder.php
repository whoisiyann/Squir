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

class Folder extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'color',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function passwordEntries()
    {
        return $this->hasMany(PasswordEntry::class);
    }

    public function notes()
    {
        return $this->hasMany(Note::class);
    }
}
