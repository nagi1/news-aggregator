<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->index();
            $table->string('title');
            $table->string('api_provider')->index();
            $table->string('source')->index();
            $table->string('author')->nullable();
            $table->string('category')->nullable();
            $table->string('description')->index();
            $table->text('content')->nullable();
            $table->string('url')->nullable();
            $table->string('image')->nullable();
            $table->dateTime('published_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
