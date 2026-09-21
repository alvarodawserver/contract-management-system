<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained()->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->string('reference', 30)->unique();
            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->string('type')->nullable();
            $table->string('responsible')->nullable();
            $table->date('expected_date')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->decimal('expected_amount', 12, 2)->nullable();
            $table->decimal('amount', 12, 2)->nullable();
            $table->date('formalization_deadline')->nullable();
            $table->date('formalized_at')->nullable();
            $table->timestamp('last_reminder_sent_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('formalization_deadline');
            $table->index(['start_date', 'end_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};
