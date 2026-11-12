<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * CV "employment background": previous jobs of a worker.
     */
    public function up(): void
    {
        Schema::create('worker_experiences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('worker_id')->index()->constrained()->cascadeOnDelete();
            $table->string('country', 100);
            $table->string('position', 100);
            $table->string('duration', 50);
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('worker_experiences');
    }
};
