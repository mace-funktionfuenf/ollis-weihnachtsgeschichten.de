<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // wp:taxonomy "fuer" - audience facet (Kinder / Familie / Erwachsene)
        Schema::create('product_audiences', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->timestamps();
        });

        // wp:taxonomy "weihnachtsgeschenke" - gift-category facet
        Schema::create('gift_categories', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->timestamps();
        });

        // wp:taxonomy "weihnachtsgeschichten" - media-type facet (Buecher / Hoerbuecher / ...)
        Schema::create('media_types', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_types');
        Schema::dropIfExists('gift_categories');
        Schema::dropIfExists('product_audiences');
    }
};
