<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('incoming_emails', function (Blueprint $table) {
            if (! Schema::hasColumn('incoming_emails', 'in_reply_to')) {
                $table->string('in_reply_to')->nullable()->after('message_id');
                $table->index('in_reply_to');
            }

            if (! Schema::hasColumn('incoming_emails', 'references_header')) {
                $table->text('references_header')->nullable()->after('in_reply_to');
            }
        });

        Schema::table('cte_email_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('cte_email_requests', 'correlation_code')) {
                $table->string('correlation_code', 64)->nullable()->after('documento_transporte');
                $table->unique('correlation_code');
            }

            if (! Schema::hasColumn('cte_email_requests', 'outbound_message_id')) {
                $table->string('outbound_message_id')->nullable()->after('correlation_code');
                $table->unique('outbound_message_id');
            }
        });

        Schema::table('cte_email_request_messages', function (Blueprint $table) {
            if (! Schema::hasColumn('cte_email_request_messages', 'in_reply_to')) {
                $table->string('in_reply_to')->nullable()->after('message_id');
                $table->index('in_reply_to');
            }

            if (! Schema::hasColumn('cte_email_request_messages', 'references_header')) {
                $table->text('references_header')->nullable()->after('in_reply_to');
            }
        });
    }

    public function down(): void
    {
        Schema::table('cte_email_request_messages', function (Blueprint $table) {
            if (Schema::hasColumn('cte_email_request_messages', 'references_header')) {
                $table->dropColumn('references_header');
            }

            if (Schema::hasColumn('cte_email_request_messages', 'in_reply_to')) {
                $table->dropIndex(['in_reply_to']);
                $table->dropColumn('in_reply_to');
            }
        });

        Schema::table('cte_email_requests', function (Blueprint $table) {
            if (Schema::hasColumn('cte_email_requests', 'outbound_message_id')) {
                $table->dropUnique(['outbound_message_id']);
                $table->dropColumn('outbound_message_id');
            }

            if (Schema::hasColumn('cte_email_requests', 'correlation_code')) {
                $table->dropUnique(['correlation_code']);
                $table->dropColumn('correlation_code');
            }
        });

        Schema::table('incoming_emails', function (Blueprint $table) {
            if (Schema::hasColumn('incoming_emails', 'references_header')) {
                $table->dropColumn('references_header');
            }

            if (Schema::hasColumn('incoming_emails', 'in_reply_to')) {
                $table->dropIndex(['in_reply_to']);
                $table->dropColumn('in_reply_to');
            }
        });
    }
};
