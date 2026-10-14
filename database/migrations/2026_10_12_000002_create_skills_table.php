<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Skill ids never change. 
    // Text columns are json with en/ar.
    public function up(): void
    {
        Schema::create('skills', function (Blueprint $table) {
            $table->id();
            $table->json('name');
            $table->json('description'); // :x, :y, ... are filled from levels
            $table->json('levels')->nullable(); // [{level, x, y, ...}, ...]
            $table->string('image'); // path under public/images/skills
            $table->foreignId('skill_tree_id')->constrained()->restrictOnDelete();
            $table->unsignedTinyInteger('grid_row')->nullable();
            $table->unsignedTinyInteger('grid_column')->nullable();
            $table->unsignedTinyInteger('job_tier');
            $table->json('s_type');
            $table->integer('max_points');
            $table->json('weapon');
            $table->integer('fguardian_points')->nullable();
            $table->integer('sguardian_points')->nullable();
            $table->integer('tguardian_points')->nullable();
            $table->integer('required_points')->nullable(); // points in its own tree
            $table->unsignedSmallInteger('required_total_base_points')->nullable(); // fourth tree: total in the 3 base trees
            $table->foreignId('required_investment_tree_id')->nullable()->constrained('skill_trees')->restrictOnDelete();
            $table->unsignedSmallInteger('required_investment_points')->nullable(); // fourth tree: points in that one tree
            $table->json('attribute')->nullable();
            $table->integer('mp_use')->nullable();
            $table->json('attack_type')->nullable();
            $table->json('selected_target')->nullable();
            $table->json('apply_target')->nullable();
            $table->integer('cooltime')->nullable(); // seconds
            $table->unique(['skill_tree_id', 'grid_row', 'grid_column'], 'skills_tree_grid_position_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('skills');
    }
};
