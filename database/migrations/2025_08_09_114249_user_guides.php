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
        Schema::table('user_guides', function (Blueprint $table) {
            $table->longText('content')->nullable()->change();
            $table->string('title', 150)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_guides', function (Blueprint $table) {
            $table->longText('content')->change();
            $table->string('title', 250)->change();
        });
    }
};
