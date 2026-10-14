<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('change_logs', function (Blueprint $table) {
            $table->id();
            $table->date('dt'); // patch date
            $table->string('cl'); // warrior, mage, archer
            $table->foreignId('skill_id')->constrained()->cascadeOnDelete();
            $table->json('description'); // may hold the <span> tags for custom colors
            $table->text('first_part')->nullable(); // value before, e.g. 3%/6%/9%; null with second_part = a note
            $table->text('second_part')->nullable(); // value after
            $table->boolean('is_up'); // arrow = the value went up
            $table->boolean('is_buff')->default(true); // colour = good for the player
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('change_logs');
    }
};
