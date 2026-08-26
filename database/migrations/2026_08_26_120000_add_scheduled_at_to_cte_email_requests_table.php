<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cte_email_requests', function (Blueprint $table) {
            $table->timestamp('scheduled_at')->nullable()->index()->after('requested_at');
        });

        DB::table('cte_email_requests')
            ->where('status', 'pending_send')
            ->whereNull('scheduled_at')
            ->update(['scheduled_at' => DB::raw('COALESCE(requested_at, created_at)')]);
    }

    public function down(): void
    {
        Schema::table('cte_email_requests', function (Blueprint $table) {
            $table->dropColumn('scheduled_at');
        });
    }
};
