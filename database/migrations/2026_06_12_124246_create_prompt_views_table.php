<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prompt_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prompt_id')
                ->constrained('prompts')
                ->cascadeOnDelete()
                ->index();
            $table->boolean('counted')->default(false)->index();
            $table->string('visitor_hash', 64)->nullable();
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['prompt_id', 'visitor_hash']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prompt_views');
    }
};
