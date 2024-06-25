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
        Schema::table('order_items', function (Blueprint $table) {
            // Remove product_name column
            $table->dropColumn('product_name');

            // Add price column
            $table->decimal('price', 8, 2)->after('quantity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            // Reverse: Add product_name column
            $table->string('product_name')->after('product_id');

            // Reverse: Remove price column
            $table->dropColumn('price');
        });
    }
};
