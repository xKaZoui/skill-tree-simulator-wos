<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('change_log_sources', function (Blueprint $table) {
            $table->id();
            $table->date('dt')->index();
            $table->string('title')->nullable(); // link tooltip
            $table->string('source_url');
            $table->timestamps();
            $table->unique(['dt', 'source_url']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('change_log_sources');
    }
};
