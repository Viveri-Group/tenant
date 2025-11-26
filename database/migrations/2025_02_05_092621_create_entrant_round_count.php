<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('entrant_round_count', function (Blueprint $table) {
            $table->id();
            $table->string('hash')->unique()->comment('a hash made up of the competition id, round info caller telephone number');
            $table->foreignId('competition_id')->comment('competition id - not really required just helpful to see')->constrained('competitions');
            $table->string('caller_number', 12)->comment('competition id - not really required just helpful to see');
            $table->integer('total_entry_count')->default(0)->comment('the number of times this entrant has entered this round');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entrant_round_count');
    }
};
