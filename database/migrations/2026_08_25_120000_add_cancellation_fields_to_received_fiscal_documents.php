<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('received_fiscal_documents', function (Blueprint $table) {
            $table->timestamp('cancelled_at')->nullable()->after('matched_at');
            $table->foreignId('cancelled_by')->nullable()->after('cancelled_at')->constrained('users')->nullOnDelete();
            $table->text('cancellation_reason')->nullable()->after('cancelled_by');
            $table->string('cancellation_resolution', 30)->nullable()->after('cancellation_reason');
        });
    }

    public function down(): void
    {
        Schema::table('received_fiscal_documents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cancelled_by');
            $table->dropColumn(['cancelled_at', 'cancellation_reason', 'cancellation_resolution']);
        });
    }
};
