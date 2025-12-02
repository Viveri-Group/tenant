<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('phone_line_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organisation_id')->constrained('organisations');
            $table->string('author_id')->nullable()->comment('External ID of who created the schedule.');
            $table->string('competition_phone_number', 12)->comment('The competition phone number which will be moved.');
            $table->unsignedBigInteger('competition_id')->comment('The id of the NEW competition id.');
            $table->dateTime('action_at')->comment('When the change should be carried out.');
            $table->boolean('processed')->default(false)->comment('Does this sill need processing');
            $table->dateTime('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('success')->nullable()->comment('Did the command finish successfully');

            $table->timestamps();

            $table->unique(['competition_phone_number', 'action_at'], 'phone_line_schedules_number_action_unique');
            $table->index(['processed', 'action_at']);
            $table->index('competition_phone_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phone_line_schedules');
    }
};
