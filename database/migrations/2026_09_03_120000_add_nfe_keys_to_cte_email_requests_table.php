<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cte_email_requests', function (Blueprint $table) {
            $table->json('nfe_keys')->nullable()->after('payload');
        });
    }

    public function down(): void
    {
        Schema::table('cte_email_requests', function (Blueprint $table) {
            $table->dropColumn('nfe_keys');
        });
    }
};
