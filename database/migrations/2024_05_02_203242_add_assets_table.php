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
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('name');
            $table->string('code');
            $table->enum('asset_type', ['action', 'fii']);
            $table->enum('order_type', ['buy', 'sell']);
            $table->decimal('original_price', 8, 2);
            $table->decimal('current_price', 8, 2)->nullable();
            $table->integer('quantity');
            $table->date('order_date')->format('Y-m-d');
            $table->date('sale_date')->format('Y-m-d')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
