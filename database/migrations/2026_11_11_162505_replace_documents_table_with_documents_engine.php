<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Replaces the placeholder documents table (candidate_id / type /
     * file_path, sample data only) with the polymorphic documents engine:
     * documents, versioned document_files and document_prints.
     */
    public function up(): void
    {
        Schema::dropIfExists('documents');

        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->morphs('documentable');
            $table->foreignId('document_type_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('title')->nullable();
            $table->unsignedInteger('current_version')->default(1);
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('uploaded_at')->nullable();
            $table->timestamps();

            // One document per type per owner (extra documents have a null type).
            $table->unique(['documentable_type', 'documentable_id', 'document_type_id'], 'documents_owner_type_unique');
        });

        Schema::create('document_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->string('file_path');
            $table->string('original_name');
            $table->string('mime', 100);
            $table->unsignedBigInteger('size');
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->nullable();

            $table->unique(['document_id', 'version']);
        });

        Schema::create('document_prints', function (Blueprint $table) {
            $table->id();
            $table->morphs('documentable');
            $table->foreignId('authority_id')->constrained()->restrictOnDelete();
            $table->string('file_path');
            $table->foreignId('printed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('printed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_prints');
        Schema::dropIfExists('document_files');
        Schema::dropIfExists('documents');

        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('agency_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 50);
            $table->string('file_path')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }
};
