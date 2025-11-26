<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('active_call_orphans', function (Blueprint $table) {
            $table->foreignId('organisation_id')->after('id')->constrained('organisations');
        });
    }

    public function down(): void
    {
        Schema::table('active_call_orphans', function (Blueprint $table) {
            $table->dropColumn(['organisation_id']);
        });
    }
};
