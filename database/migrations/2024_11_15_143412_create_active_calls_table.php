<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('active_calls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organisation_id')->nullable()->constrained('organisations');
            $table->foreignId('competition_id')->nullable()->constrained('competitions');
            $table->string('call_id')->nullable()->comment('the incoming call id from the telephony system');
            $table->foreignId('participant_id')->nullable()->constrained('participants');
            $table->foreignId('competition_phone_line_id')->nullable()->constrained('competition_phone_lines', 'id');
            $table->string('phone_number', 12)->comment('the phone number of the competition');
            $table->string('caller_phone_number', 12)->index()->comment('the callers telephone number');
            $table->string('status')->nullable();
            $table->integer('cli_presentation')->default(2)->comment('if the callers number can be seen by the host');
            $table->string('round_start')->nullable();
            $table->string('round_end')->nullable();
            $table->string('call_end')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('active_calls');
    }
};
