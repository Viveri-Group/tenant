<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organisation_id')->constrained('organisations');
            $table->uuid()->nullable()->unique();
            $table->string('call_id')->nullable()->comment('unique call id provided by the shout system');
            $table->foreignId('competition_id')->constrained('competitions');
            $table->boolean('is_free_entry')->default(false);
            $table->string('station_name')->nullable();
            $table->string('competition_phone_number', 12)->nullable();
            $table->foreignId('competition_draw_id')->nullable()->constrained('competition_draws');
            $table->string('telephone', 12)->index()->comment('telephone number of the participant');
            $table->dateTime('drawn_at')->index()->nullable()->comment('date of drawn at');
            $table->string('round_start')->nullable();
            $table->string('round_end')->nullable();
            $table->dateTime('call_start')->index()->nullable()->comment('time the call originally came in');
            $table->dateTime('call_end')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('participants');
    }
};
