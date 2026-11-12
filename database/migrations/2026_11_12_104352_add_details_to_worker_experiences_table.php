<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Detailed experience (care giver CV): employer, location, job
     * description and exact dates. Short CVs keep country/city, position
     * and a free-text duration; "country" holds the city for templates
     * whose experience place is a city.
     */
    public function up(): void
    {
        Schema::table('worker_experiences', function (Blueprint $table) {
            $table->string('country', 100)->nullable()->change();
            $table->string('duration', 50)->nullable()->change();
            $table->string('company', 150)->nullable()->after('position');
            $table->string('location', 150)->nullable()->after('company');
            $table->date('started_at')->nullable()->after('location');
            $table->date('ended_at')->nullable()->after('started_at');
            $table->text('description')->nullable()->after('ended_at');
        });
    }

    public function down(): void
    {
        Schema::table('worker_experiences', function (Blueprint $table) {
            $table->dropColumn(['company', 'location', 'started_at', 'ended_at', 'description']);
        });
    }
};
