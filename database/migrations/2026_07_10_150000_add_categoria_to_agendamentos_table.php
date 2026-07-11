<?php

use App\Enum\Agendamento\CategoriaAgendamentoEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('agendamentos')) {
            return;
        }

        if (! Schema::hasColumn('agendamentos', 'categoria')) {
            Schema::table('agendamentos', function (Blueprint $table): void {
                $table->string('categoria')
                    ->default(CategoriaAgendamentoEnum::MANUAL->value)
                    ->after('servico_id')
                    ->index();
            });
        }

        DB::table('agendamentos')
            ->whereNull('categoria')
            ->update(['categoria' => CategoriaAgendamentoEnum::MANUAL->value]);

        DB::table('agendamentos')
            ->where('servico_id', config('agendamento.checklist_service_id'))
            ->update(['categoria' => CategoriaAgendamentoEnum::CHECKLIST->value]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('agendamentos') || ! Schema::hasColumn('agendamentos', 'categoria')) {
            return;
        }

        Schema::table('agendamentos', function (Blueprint $table): void {
            $table->dropColumn('categoria');
        });
    }
};
