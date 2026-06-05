<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('integrados', function (Blueprint $table) {
            $table->string('documento', 20)->nullable()->after('nome');
            $table->index('documento');
        });

        Schema::create('incoming_emails', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 50);
            $table->string('external_id')->nullable();
            $table->string('message_id')->unique();
            $table->string('from_email')->nullable();
            $table->string('from_name')->nullable();
            $table->string('subject')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->string('status')->default('stored');
            $table->json('raw_headers')->nullable();
            $table->json('metadata')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
        });

        Schema::create('incoming_email_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('incoming_email_id')->constrained('incoming_emails')->cascadeOnDelete();
            $table->string('original_filename');
            $table->string('stored_filename');
            $table->string('disk', 50)->default('local');
            $table->string('path');
            $table->string('mime_type')->nullable();
            $table->string('extension', 20)->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->string('checksum', 64)->nullable();
            $table->string('kind', 20)->default('other');
            $table->string('status', 30)->default('stored');
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('received_fiscal_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('incoming_email_id')->constrained('incoming_emails')->cascadeOnDelete();
            $table->foreignId('xml_attachment_id')->nullable()->constrained('incoming_email_attachments')->nullOnDelete();
            $table->foreignId('pdf_attachment_id')->nullable()->constrained('incoming_email_attachments')->nullOnDelete();
            $table->string('tipo_documento', 20)->default('unknown');
            $table->string('chave_nfe')->nullable()->unique();
            $table->string('numero_nota')->nullable();
            $table->string('serie')->nullable();
            $table->timestamp('emitido_em')->nullable();
            $table->string('emitente_nome')->nullable();
            $table->string('emitente_documento', 20)->nullable();
            $table->string('destinatario_nome')->nullable();
            $table->string('destinatario_documento', 20)->nullable();
            $table->string('transportador_nome')->nullable();
            $table->string('transportador_documento', 20)->nullable();
            $table->string('placa_transportador', 20)->nullable();
            $table->decimal('peso_carga', 12, 3)->nullable();
            $table->string('referenced_nfe_key')->nullable();
            $table->string('referenced_sale_number')->nullable();
            $table->foreignId('integrado_id')->nullable()->constrained('integrados')->nullOnDelete();
            $table->timestamp('matched_at')->nullable();
            $table->string('status', 30)->default('parsed');
            $table->json('payload')->nullable();
            $table->timestamps();
        });

        Schema::create('shipment_document_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_document_id')->constrained('received_fiscal_documents')->cascadeOnDelete();
            $table->foreignId('remittance_document_id')->constrained('received_fiscal_documents')->cascadeOnDelete();
            $table->string('sale_nfe_key')->nullable();
            $table->string('remittance_nfe_key')->nullable();
            $table->string('sale_number')->nullable();
            $table->string('remittance_number')->nullable();
            $table->foreignId('integrado_id')->nullable()->constrained('integrados')->nullOnDelete();
            $table->foreignId('viagem_id')->nullable()->constrained('viagens')->nullOnDelete();
            $table->timestamp('matched_at')->nullable();
            $table->string('status', 30)->default('matched');
            $table->json('payload')->nullable();
            $table->timestamps();
            $table->unique(['sale_document_id', 'remittance_document_id'], 'shipment_document_pair_unique');
        });

        Schema::create('viagem_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('viagem_id')->constrained('viagens')->cascadeOnDelete();
            $table->foreignId('incoming_email_attachment_id')->constrained('incoming_email_attachments')->cascadeOnDelete();
            $table->foreignId('received_fiscal_document_id')->nullable()->constrained('received_fiscal_documents')->nullOnDelete();
            $table->string('role', 30);
            $table->timestamps();
            $table->unique(['viagem_id', 'incoming_email_attachment_id'], 'viagem_attachment_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('viagem_attachments');
        Schema::dropIfExists('shipment_document_groups');
        Schema::dropIfExists('received_fiscal_documents');
        Schema::dropIfExists('incoming_email_attachments');
        Schema::dropIfExists('incoming_emails');

        Schema::table('integrados', function (Blueprint $table) {
            $table->dropIndex(['documento']);
            $table->dropColumn('documento');
        });
    }
};
