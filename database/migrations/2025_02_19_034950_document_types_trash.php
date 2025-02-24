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
        Schema::table('document_types_trashed', function (Blueprint $table) {
            $table->dropColumn('document_type_name');
            $table->dropColumn('is_trashed');
            $table->dropColumn('active_back_on');
            $table->dropColumn('updated_at');
            $table->dropColumn('trashed_document_type_name');
            $table->string('trashed_name', 90);
            $table->string('trashed_table_name', 90);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('document_types_trashed', function (Blueprint $table) {
            $table->string('document_type_name', 90);
            $table->boolean('is_trashed')->default(true);
            $table->dateTime('active_back_on')->nullable();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->dropColumn('trashed_name');
            $table->string('trashed_document_type_name', 90);
            $table->dropColumn('trashed_table_name');
        });
    }
};
