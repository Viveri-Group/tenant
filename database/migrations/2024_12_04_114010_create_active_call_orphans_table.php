<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('active_call_orphans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organisation_id')->constrained('organisations');
            $table->foreignId('competition_id')->nullable()->constrained('competitions');
            $table->string('call_id')->nullable();
            $table->string('phone_number', 12);
            $table->string('caller_phone_number', 12);
            $table->string('status')->nullable();
            $table->dateTime('original_call_time')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('active_call_orphans');
    }
};
