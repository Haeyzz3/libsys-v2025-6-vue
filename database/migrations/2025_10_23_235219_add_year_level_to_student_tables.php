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
        Schema::table('undergraduate_students', function (Blueprint $table) {
            // Adds the new column. You can place it after any existing column.
            $table->string('year_level')->nullable()->after('major_id');
        });

        Schema::table('graduate_students', function (Blueprint $table) {
            // Adds the new column. You can place it after any existing column.
            $table->string('year_level')->nullable()->after('major_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('undergraduate_students', function (Blueprint $table) {
            $table->dropColumn('year_level');
        });

        Schema::table('graduate_students', function (Blueprint $table) {
            $table->dropColumn('year_level');
        });
    }
};
