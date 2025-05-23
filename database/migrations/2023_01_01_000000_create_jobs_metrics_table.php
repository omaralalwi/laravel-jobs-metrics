<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('jobs_metrics', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('job');
            $table->string('queue')->nullable();
            $table->decimal('duration_ms', 8, 2)->nullable();
            $table->decimal('memory_mb', 8, 2)->nullable();
            $table->timestamps();

            $table->index(['job','queue', 'memory_mb']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jobs_metrics');
    }
}; 