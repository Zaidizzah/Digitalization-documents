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
        Schema::table('files', function (Blueprint $table) {
            $table->bigInteger('user_id');
            $table->unsignedBigInteger('size');
            $table->enum('type', ['image/jpg', 'image/jpeg', 'image/png', 'image/webp', 'application/pdf']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('files', function (Blueprint $table) {
            $table->dropColumn('user_id');
            $table->dropColumn('size');
            $table->dropColumn('type');
        });
    }
};
