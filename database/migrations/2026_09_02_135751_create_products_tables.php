<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('wp_post_id')->nullable()->unique();
            $table->string('slug')->unique();
            $table->string('title');
            $table->longText('body_html')->nullable();
            $table->string('asin')->nullable()->index();
            $table->string('ean')->nullable();
            $table->string('article_number')->nullable();
            $table->decimal('price', 8, 2)->nullable();
            $table->decimal('price_old', 8, 2)->nullable();
            $table->string('currency', 3)->default('EUR');
            $table->string('portal')->nullable();
            // WordPress' own pre-built affiliate link (already carries the
            // ollisweichnac-21 tag) - preserved byte-for-byte, never rebuilt.
            $table->text('affiliate_link')->nullable();
            $table->unsignedTinyInteger('rating')->nullable();
            $table->unsignedInteger('rating_count')->nullable();
            $table->boolean('available')->default(true);
            $table->string('image_path')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->string('meta_description')->nullable();
            $table->timestamps();
        });

        Schema::create('product_product_audience', function (Blueprint $table) {
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_audience_id')->constrained()->cascadeOnDelete();
            $table->primary(['product_id', 'product_audience_id'], 'product_audience_pivot_primary');
        });

        Schema::create('gift_category_product', function (Blueprint $table) {
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('gift_category_id')->constrained()->cascadeOnDelete();
            $table->primary(['product_id', 'gift_category_id'], 'gift_category_pivot_primary');
        });

        Schema::create('media_type_product', function (Blueprint $table) {
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('media_type_id')->constrained()->cascadeOnDelete();
            $table->primary(['product_id', 'media_type_id'], 'media_type_pivot_primary');
        });

        // wp:postmeta `product_related` - a serialized list of related wp_post_ids.
        Schema::create('product_related', function (Blueprint $table) {
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('related_product_id')->constrained('products')->cascadeOnDelete();
            $table->primary(['product_id', 'related_product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_related');
        Schema::dropIfExists('media_type_product');
        Schema::dropIfExists('gift_category_product');
        Schema::dropIfExists('product_product_audience');
        Schema::dropIfExists('products');
    }
};
