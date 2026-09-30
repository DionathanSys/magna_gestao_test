<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cte_email_requests', function (Blueprint $table): void {
            $table->string('origin', 30)->default('manual')->index()->after('status');
            $table->foreignId('shipment_document_group_id')
                ->nullable()
                ->unique()
                ->after('viagem_id')
                ->constrained('shipment_document_groups')
                ->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable()->after('completed_at');
            $table->foreignId('cancelled_by')->nullable()->after('cancelled_at')->constrained('users')->nullOnDelete();
            $table->text('cancellation_reason')->nullable()->after('cancelled_by');
        });
    }

    public function down(): void
    {
        Schema::table('cte_email_requests', function (Blueprint $table): void {
            $table->dropForeign(['shipment_document_group_id']);
            $table->dropUnique(['shipment_document_group_id']);
            $table->dropForeign(['cancelled_by']);
            $table->dropColumn([
                'origin',
                'shipment_document_group_id',
                'cancelled_at',
                'cancelled_by',
                'cancellation_reason',
            ]);
        });
    }
};
