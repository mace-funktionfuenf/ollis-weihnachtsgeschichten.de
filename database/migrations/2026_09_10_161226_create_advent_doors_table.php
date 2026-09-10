<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('advent_doors', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('day')->unique();
            $table->string('title');
            $table->longText('story_html');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('advent_doors');
    }
};
