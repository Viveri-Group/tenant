<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('file_defaults', function (Blueprint $table) {
            $table->foreignId('organisation_id')->after('id')->constrained();
        });
    }

    public function down(): void
    {
        Schema::table('file_defaults', function (Blueprint $table) {
            $table->dropForeign(['organisation_id']);
            $table->dropColumn('organisation_id');
        });
    }
};
