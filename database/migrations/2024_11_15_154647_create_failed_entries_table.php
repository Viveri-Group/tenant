<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('failed_entries', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique();
            $table->foreignId('organisation_id')->constrained('organisations');
            $table->unsignedBigInteger('competition_id')->nullable();
            $table->string('station_name')->nullable();
            $table->string('call_id')->nullable();
            $table->string('phone_number', 12)->comment('the phone number of the competition');
            $table->string('caller_phone_number', 12)->index()->comment('the callers telephone number');
            $table->string('reason')->comment('failure reason');
            $table->string('round_start')->nullable();
            $table->string('round_end')->nullable();
            $table->dateTime('call_start')->index()->nullable()->comment('time the call originally came in');
            $table->dateTime('call_end')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('failed_entries');
    }
};
