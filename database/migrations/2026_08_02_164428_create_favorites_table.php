<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Polymorphic relationship: a favorite can point to either
            // a password_entries row or a notes row.
            // Creates: favoritable_id (bigint) + favoritable_type (string)
            $table->morphs('favoritable');

            $table->timestamps();

            // Prevent favoriting the same item twice
            $table->unique(['user_id', 'favoritable_id', 'favoritable_type'], 'unique_user_favorite');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('favorites');
    }
};