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
        Schema::table('user_guides', function ($table) {
            $table->mediumText('content')->change();
            $table->dropColumn('slug');
            $table->string('path', 600)->unique()->after('slug');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_guides', function ($table) {
            $table->longText('content')->change();
            $table->dropColumn('path');
            $table->string('slug', 255)->unique()->after('title');
        });
    }
};
