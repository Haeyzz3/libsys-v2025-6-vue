<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('library_visits', function (Blueprint $table) {
            if (!Schema::hasColumn('library_visits', 'auto_logged_out')) {
                $table->boolean('auto_logged_out')->default(false)->after('exit_time');
            }
            if (!Schema::hasColumn('library_visits', 'violation_type')) {
                $table->string('violation_type')->nullable()->after('auto_logged_out');
            }
            $table->index(['auto_logged_out']);
        });
    }

    public function down(): void
    {
        Schema::table('library_visits', function (Blueprint $table) {
            if (Schema::hasColumn('library_visits', 'violation_type')) {
                $table->dropColumn('violation_type');
            }
            if (Schema::hasColumn('library_visits', 'auto_logged_out')) {
                $table->dropColumn('auto_logged_out');
            }
        });
    }
};

