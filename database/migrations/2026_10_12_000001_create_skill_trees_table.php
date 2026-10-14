<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('skill_trees', function (Blueprint $table) {
            $table->id();
            $table->string('class_key', 16); // warrior, mage, archer
            $table->unsignedTinyInteger('position');
            $table->json('name')->nullable(); // en/ar
            $table->unique(['class_key', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('skill_trees');
    }
};
