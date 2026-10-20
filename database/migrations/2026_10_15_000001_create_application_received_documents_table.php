<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The physical documents received against a postal or hybrid application, recorded after the
 * tender has been opened with the committee's codes.
 *
 * Distinct from tender_applications.documents (what the vendor uploaded through the portal)
 * and from tender_applications.postal_quote_scan (the single quote scan captured during bid
 * entry): this table holds the full set of papers that actually arrived in the envelope, which
 * is many files per application and may be added over several sittings.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_received_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tender_id')->constrained('tenders')->onDelete('cascade');
            $table->foreignId('application_id')->constrained('tender_applications')->onDelete('cascade');

            $table->string('file_name');
            $table->string('file_path');
            $table->string('file_type')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();

            // 'upload' = picked from disk (including output of the user's own scanner software),
            // 'scan'   = captured page by page through the in-browser scan interface.
            $table->enum('source', ['upload', 'scan'])->default('upload');
            $table->uuid('batch_id')->nullable();
            $table->unsignedInteger('page_number')->nullable();

            $table->text('remarks')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('received_at')->nullable();
            $table->timestamps();

            $table->index(['tender_id', 'application_id'], 'ard_tender_application_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_received_documents');
    }
};
