<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prompts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug', 16)->unique();
            $table->longText('body');
            $table->boolean('is_public')->default(false)->index();
            $table->unsignedBigInteger('view_count')->default(0)->index();
            $table->foreignId('user_id')
                ->constrained('users')
                ->restrictOnDelete();
            $table->timestamps();

            $table->index(['is_public', 'view_count']);
            $table->index(['is_public', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prompts');
    }
};
