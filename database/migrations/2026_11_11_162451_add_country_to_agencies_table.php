<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The existing agencies table (linked to agency portal users and
     * candidates) becomes the "external agencies" reference list. The old
     * 2-letter "country" column is mapped to country_id by CountrySeeder.
     */
    public function up(): void
    {
        Schema::table('agencies', function (Blueprint $table) {
            $table->foreignId('country_id')->nullable()->after('name_en')->constrained()->restrictOnDelete();
            $table->string('contact_person')->nullable()->after('country_id');
        });
    }

    public function down(): void
    {
        Schema::table('agencies', function (Blueprint $table) {
            $table->dropConstrainedForeignId('country_id');
            $table->dropColumn('contact_person');
        });
    }
};
