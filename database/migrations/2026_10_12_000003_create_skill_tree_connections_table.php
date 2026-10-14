<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('skill_tree_connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_skill_id')->constrained('skills')->cascadeOnDelete();
            $table->foreignId('to_skill_id')->constrained('skills')->cascadeOnDelete();
            $table->json('route')->nullable();
            $table->unique(['from_skill_id', 'to_skill_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('skill_tree_connections');
    }
};
