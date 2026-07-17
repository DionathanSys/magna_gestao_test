<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('veiculo_documentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('veiculo_id')->constrained('veiculos')->cascadeOnDelete();
            $table->string('nome', 150);
            $table->text('descricao')->nullable();
            $table->date('data_inicio')->nullable();
            $table->date('data_fim')->nullable();
            $table->unsignedSmallInteger('dias_alerta')->default(30);
            $table->json('anexos')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['nome', 'data_fim']);
            $table->index(['veiculo_id', 'nome']);
        });

        $now = now();
        $documentos = [];

        DB::table('veiculos')
            ->select('id', 'informacoes_complementares')
            ->whereNotNull('informacoes_complementares')
            ->orderBy('id')
            ->chunkById(500, function ($veiculos) use (&$documentos, $now): void {
                foreach ($veiculos as $veiculo) {
                    $info = json_decode($veiculo->informacoes_complementares, true);

                    if (! is_array($info)) {
                        continue;
                    }

                    if (! empty($info['teste_fumaca'])) {
                        $dataInicio = $this->parseDate($info['teste_fumaca']);

                        if ($dataInicio) {
                            $documentos[] = [
                                'veiculo_id' => $veiculo->id,
                                'nome' => 'Teste de Fumaça',
                                'descricao' => 'Migrado do cadastro do veículo.',
                                'data_inicio' => $dataInicio->toDateString(),
                                'data_fim' => $dataInicio->copy()->addDays(180)->toDateString(),
                                'dias_alerta' => 30,
                                'anexos' => null,
                                'created_at' => $now,
                                'updated_at' => $now,
                            ];
                        }
                    }

                    if (! empty($info['afericao_tacografo'])) {
                        $dataFim = $this->parseDate($info['afericao_tacografo']);

                        if ($dataFim) {
                            $documentos[] = [
                                'veiculo_id' => $veiculo->id,
                                'nome' => 'Aferição Tacógrafo',
                                'descricao' => 'Migrado do cadastro do veículo.',
                                'data_inicio' => $dataFim->copy()->subYears(2)->toDateString(),
                                'data_fim' => $dataFim->toDateString(),
                                'dias_alerta' => 30,
                                'anexos' => null,
                                'created_at' => $now,
                                'updated_at' => $now,
                            ];
                        }
                    }
                }

                if (count($documentos) >= 500) {
                    DB::table('veiculo_documentos')->insert($documentos);
                    $documentos = [];
                }
            });

        if ($documentos !== []) {
            DB::table('veiculo_documentos')->insert($documentos);
        }

        DB::statement("UPDATE veiculos SET informacoes_complementares = JSON_REMOVE(informacoes_complementares, '$.teste_fumaca', '$.afericao_tacografo') WHERE informacoes_complementares IS NOT NULL");
    }

    public function down(): void
    {
        Schema::dropIfExists('veiculo_documentos');
    }

    private function parseDate(mixed $value): ?Carbon
    {
        try {
            return filled($value) ? Carbon::parse($value)->startOfDay() : null;
        } catch (Throwable) {
            return null;
        }
    }
};
