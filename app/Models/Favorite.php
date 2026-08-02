<?php

// namespace App\Models;

// use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Illuminate\Database\Eloquent\Model;
// use Illuminate\Database\Eloquent\Relations\BelongsTo;
// use Illuminate\Database\Eloquent\Relations\MorphTo;

// class Favorite extends Model
// {
//     use HasFactory;

//     protected $fillable = ['user_id', 'favoritable_id', 'favoritable_type'];

//     public function user(): BelongsTo
//     {
//         return $this->belongsTo(User::class);
//     }

//     // Resolves to either a PasswordEntry or a Note depending on favoritable_type
//     public function favoritable(): MorphTo
//     {
//         return $this->morphTo();
//     }
// }







namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Favorite extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'favoritable_id',
        'favoritable_type',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Tumuturo sa alinman sa isang PasswordEntry o Note row
    public function favoritable()
    {
        return $this->morphTo();
    }
}


