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
        Schema::table('document_types', function (Blueprint $table) {
            $table->string('table_name', 64)->nullable();
            $table->dropColumn('schema_attributes');
            $table->json('schema_form')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('document_types', function (Blueprint $table) {
            $table->dropColumn('table_name');
            $table->json('schema_attributes')->nullable();
            $table->dropColumn('schema_form');
        });
    }
};
