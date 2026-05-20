<?php

use App\Enum\Pneu\StatusCicloPneuEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->createCatalogTables();
        $this->createCycleAndInspectionTables();
        $this->extendExistingTables();
        $this->backfillCatalogs();
        $this->backfillPneuReferences();
        $this->backfillCycles();
        $this->backfillCycleReferences();
    }

    public function down(): void
    {
        Schema::table('consertos', function (Blueprint $table) {
            $this->dropForeignIfExists($table, 'consertos_pneu_ciclo_id_foreign');
            if (Schema::hasColumn('consertos', 'pneu_ciclo_id')) {
                $table->dropColumn('pneu_ciclo_id');
            }
        });

        Schema::table('recapagens', function (Blueprint $table) {
            $this->dropForeignIfExists($table, 'recapagens_pneu_ciclo_id_foreign');
            if (Schema::hasColumn('recapagens', 'pneu_ciclo_id')) {
                $table->dropColumn('pneu_ciclo_id');
            }
        });

        Schema::table('historico_movimento_pneus', function (Blueprint $table) {
            $this->dropForeignIfExists($table, 'historico_movimento_pneus_pneu_ciclo_id_foreign');
            $this->dropForeignIfExists($table, 'historico_movimento_pneus_pneu_posicao_veiculo_id_foreign');
            foreach (['pneu_ciclo_id', 'pneu_posicao_veiculo_id', 'tipo_evento'] as $column) {
                if (Schema::hasColumn('historico_movimento_pneus', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('pneu_posicao_veiculo', function (Blueprint $table) {
            $this->dropForeignIfExists($table, 'pneu_posicao_veiculo_pneu_ciclo_id_foreign');
            if (Schema::hasColumn('pneu_posicao_veiculo', 'pneu_ciclo_id')) {
                $table->dropColumn('pneu_ciclo_id');
            }
        });

        Schema::table('pneus', function (Blueprint $table) {
            foreach ([
                'pneus_pneu_marca_id_foreign',
                'pneus_pneu_modelo_id_foreign',
                'pneus_pneu_medida_id_foreign',
                'pneus_pneu_local_id_foreign',
                'pneus_fornecedor_compra_id_foreign',
            ] as $foreign) {
                $this->dropForeignIfExists($table, $foreign);
            }

            foreach ([
                'pneu_marca_id',
                'pneu_modelo_id',
                'pneu_medida_id',
                'pneu_local_id',
                'fornecedor_compra_id',
                'numero_serie',
                'dot',
                'nota_fiscal',
                'sulco_inicial',
                'recapavel',
                'limite_recapagens',
            ] as $column) {
                if (Schema::hasColumn('pneus', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::dropIfExists('pneu_inspecoes');
        Schema::dropIfExists('pneu_ciclos');
        Schema::dropIfExists('pneu_locais');
        Schema::dropIfExists('pneu_medidas');
        Schema::dropIfExists('pneu_modelos');
        Schema::dropIfExists('pneu_marcas');
    }

    private function createCatalogTables(): void
    {
        if (! Schema::hasTable('pneu_marcas')) {
            Schema::create('pneu_marcas', function (Blueprint $table) {
                $table->id();
                $table->string('nome')->unique();
                $table->boolean('ativo')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('pneu_modelos')) {
            Schema::create('pneu_modelos', function (Blueprint $table) {
                $table->id();
                $table->string('nome')->unique();
                $table->boolean('ativo')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('pneu_medidas')) {
            Schema::create('pneu_medidas', function (Blueprint $table) {
                $table->id();
                $table->string('codigo')->unique();
                $table->string('descricao')->nullable();
                $table->boolean('ativo')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('pneu_locais')) {
            Schema::create('pneu_locais', function (Blueprint $table) {
                $table->id();
                $table->string('nome')->unique();
                $table->string('tipo')->nullable();
                $table->foreignId('parceiro_id')->nullable()->constrained('parceiros')->nullOnDelete();
                $table->boolean('ativo')->default(true);
                $table->timestamps();
            });
        }
    }

    private function createCycleAndInspectionTables(): void
    {
        if (! Schema::hasTable('pneu_ciclos')) {
            Schema::create('pneu_ciclos', function (Blueprint $table) {
                $table->id();
                $table->foreignId('pneu_id')->constrained('pneus')->cascadeOnDelete();
                $table->unsignedTinyInteger('numero');
                $table->foreignId('desenho_pneu_id')->nullable()->constrained('desenhos_pneu')->nullOnDelete();
                $table->string('status')->default(StatusCicloPneuEnum::ABERTO->value);
                $table->date('data_abertura')->nullable();
                $table->date('data_fechamento')->nullable();
                $table->unsignedBigInteger('km_inicial')->nullable();
                $table->unsignedBigInteger('km_final')->nullable();
                $table->text('observacao')->nullable();
                $table->timestamps();
                $table->unique(['pneu_id', 'numero']);
            });
        }

        if (! Schema::hasTable('pneu_inspecoes')) {
            Schema::create('pneu_inspecoes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('pneu_id')->constrained('pneus')->cascadeOnDelete();
                $table->foreignId('pneu_ciclo_id')->nullable()->constrained('pneu_ciclos')->nullOnDelete();
                $table->foreignId('veiculo_id')->nullable()->constrained('veiculos')->nullOnDelete();
                $table->foreignId('pneu_posicao_veiculo_id')->nullable()->constrained('pneu_posicao_veiculo')->nullOnDelete();
                $table->foreignId('parceiro_id')->nullable()->constrained('parceiros')->nullOnDelete();
                $table->string('tipo');
                $table->string('resultado');
                $table->date('data_inspecao');
                $table->unsignedBigInteger('km_referencia')->nullable();
                $table->decimal('sulco_interno', 5, 2)->nullable();
                $table->decimal('sulco_centro', 5, 2)->nullable();
                $table->decimal('sulco_externo', 5, 2)->nullable();
                $table->boolean('apto_recapagem')->nullable();
                $table->text('observacao')->nullable();
                $table->json('anexos')->nullable();
                $table->timestamps();
            });
        }
    }

    private function extendExistingTables(): void
    {
        Schema::table('pneus', function (Blueprint $table) {
            if (! Schema::hasColumn('pneus', 'pneu_marca_id')) {
                $table->foreignId('pneu_marca_id')->nullable()->after('numero_fogo')->constrained('pneu_marcas')->nullOnDelete();
            }
            if (! Schema::hasColumn('pneus', 'pneu_modelo_id')) {
                $table->foreignId('pneu_modelo_id')->nullable()->after('pneu_marca_id')->constrained('pneu_modelos')->nullOnDelete();
            }
            if (! Schema::hasColumn('pneus', 'pneu_medida_id')) {
                $table->foreignId('pneu_medida_id')->nullable()->after('pneu_modelo_id')->constrained('pneu_medidas')->nullOnDelete();
            }
            if (! Schema::hasColumn('pneus', 'pneu_local_id')) {
                $table->foreignId('pneu_local_id')->nullable()->after('local')->constrained('pneu_locais')->nullOnDelete();
            }
            if (! Schema::hasColumn('pneus', 'fornecedor_compra_id')) {
                $table->foreignId('fornecedor_compra_id')->nullable()->after('data_aquisicao')->constrained('parceiros')->nullOnDelete();
            }
            if (! Schema::hasColumn('pneus', 'numero_serie')) {
                $table->string('numero_serie')->nullable()->after('medida');
            }
            if (! Schema::hasColumn('pneus', 'dot')) {
                $table->string('dot', 30)->nullable()->after('numero_serie');
            }
            if (! Schema::hasColumn('pneus', 'nota_fiscal')) {
                $table->string('nota_fiscal')->nullable()->after('fornecedor_compra_id');
            }
            if (! Schema::hasColumn('pneus', 'sulco_inicial')) {
                $table->decimal('sulco_inicial', 5, 2)->nullable()->after('valor');
            }
            if (! Schema::hasColumn('pneus', 'recapavel')) {
                $table->boolean('recapavel')->default(true)->after('sulco_inicial');
            }
            if (! Schema::hasColumn('pneus', 'limite_recapagens')) {
                $table->unsignedTinyInteger('limite_recapagens')->default(3)->after('recapavel');
            }
        });

        Schema::table('pneu_posicao_veiculo', function (Blueprint $table) {
            if (! Schema::hasColumn('pneu_posicao_veiculo', 'pneu_ciclo_id')) {
                $table->foreignId('pneu_ciclo_id')->nullable()->after('pneu_id')->constrained('pneu_ciclos')->nullOnDelete();
            }
        });

        Schema::table('historico_movimento_pneus', function (Blueprint $table) {
            if (! Schema::hasColumn('historico_movimento_pneus', 'pneu_ciclo_id')) {
                $table->foreignId('pneu_ciclo_id')->nullable()->after('pneu_id')->constrained('pneu_ciclos')->nullOnDelete();
            }
            if (! Schema::hasColumn('historico_movimento_pneus', 'pneu_posicao_veiculo_id')) {
                $table->foreignId('pneu_posicao_veiculo_id')->nullable()->after('pneu_ciclo_id')->constrained('pneu_posicao_veiculo')->nullOnDelete();
            }
            if (! Schema::hasColumn('historico_movimento_pneus', 'tipo_evento')) {
                $table->string('tipo_evento')->nullable()->after('motivo');
            }
        });

        Schema::table('recapagens', function (Blueprint $table) {
            if (! Schema::hasColumn('recapagens', 'pneu_ciclo_id')) {
                $table->foreignId('pneu_ciclo_id')->nullable()->after('pneu_id')->constrained('pneu_ciclos')->nullOnDelete();
            }
        });

        Schema::table('consertos', function (Blueprint $table) {
            if (! Schema::hasColumn('consertos', 'pneu_ciclo_id')) {
                $table->foreignId('pneu_ciclo_id')->nullable()->after('pneu_id')->constrained('pneu_ciclos')->nullOnDelete();
            }
        });
    }

    private function backfillCatalogs(): void
    {
        $this->insertCatalogValues('pneu_marcas', 'nome', $this->getConfigValues('marcas_pneu')->merge(
            DB::table('pneus')->whereNotNull('marca')->pluck('marca')
        ));

        $this->insertCatalogValues('pneu_modelos', 'nome', $this->getConfigValues('modelos_pneu')->merge(
            DB::table('pneus')->whereNotNull('modelo')->pluck('modelo')
        ));

        $this->insertCatalogValues('pneu_medidas', 'codigo', DB::table('pneus')->whereNotNull('medida')->pluck('medida'));

        $this->insertLocais();
    }

    private function backfillPneuReferences(): void
    {
        DB::table('pneus')->orderBy('id')->chunkById(200, function (Collection $pneus): void {
            foreach ($pneus as $pneu) {
                $updates = [];

                if ($pneu->marca) {
                    $updates['pneu_marca_id'] = DB::table('pneu_marcas')->where('nome', $pneu->marca)->value('id');
                }

                if ($pneu->modelo) {
                    $updates['pneu_modelo_id'] = DB::table('pneu_modelos')->where('nome', $pneu->modelo)->value('id');
                }

                if ($pneu->medida) {
                    $updates['pneu_medida_id'] = DB::table('pneu_medidas')->where('codigo', $pneu->medida)->value('id');
                }

                if ($pneu->local) {
                    $updates['pneu_local_id'] = DB::table('pneu_locais')->where('nome', $pneu->local)->value('id');
                }

                if ($updates !== []) {
                    DB::table('pneus')->where('id', $pneu->id)->update(array_filter($updates, fn ($value) => $value !== null));
                }
            }
        });
    }

    private function backfillCycles(): void
    {
        DB::table('pneus')->orderBy('id')->chunkById(100, function (Collection $pneus): void {
            foreach ($pneus as $pneu) {
                $numero = $this->normalizeInt($pneu->ciclo_vida);
                $recapagemAtual = DB::table('recapagens')
                    ->where('pneu_id', $pneu->id)
                    ->where('ciclo_vida', (string) $numero)
                    ->orderByDesc('data_recapagem')
                    ->first();

                $desenhoId = $recapagemAtual?->desenho_pneu_id;
                if (! $desenhoId && is_numeric((string) $pneu->desenho_pneu_id)) {
                    $desenhoId = (int) $pneu->desenho_pneu_id;
                }

                DB::table('pneu_ciclos')->updateOrInsert(
                    ['pneu_id' => $pneu->id, 'numero' => $numero],
                    [
                        'desenho_pneu_id' => $desenhoId,
                        'status' => $pneu->status === 'SUCATA'
                            ? StatusCicloPneuEnum::ENCERRADO->value
                            : StatusCicloPneuEnum::ABERTO->value,
                        'data_abertura' => $recapagemAtual?->data_recapagem ?? $pneu->data_aquisicao,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        });
    }

    private function backfillCycleReferences(): void
    {
        DB::table('historico_movimento_pneus')
            ->whereNull('tipo_evento')
            ->update(['tipo_evento' => 'REMOCAO']);

        DB::table('historico_movimento_pneus')->orderBy('id')->chunkById(200, function (Collection $rows): void {
            foreach ($rows as $row) {
                $cycleId = $this->getCycleId($row->pneu_id, $row->ciclo_vida);
                DB::table('historico_movimento_pneus')->where('id', $row->id)->update([
                    'pneu_ciclo_id' => $cycleId,
                ]);
            }
        });

        DB::table('recapagens')->orderBy('id')->chunkById(200, function (Collection $rows): void {
            foreach ($rows as $row) {
                $cycleId = $this->getCycleId($row->pneu_id, $row->ciclo_vida);
                DB::table('recapagens')->where('id', $row->id)->update([
                    'pneu_ciclo_id' => $cycleId,
                ]);
            }
        });

        DB::table('consertos')->orderBy('id')->chunkById(200, function (Collection $rows): void {
            foreach ($rows as $row) {
                $cycleId = $this->getCycleId($row->pneu_id, $row->ciclo_vida);
                DB::table('consertos')->where('id', $row->id)->update([
                    'pneu_ciclo_id' => $cycleId,
                ]);
            }
        });

        DB::table('pneu_posicao_veiculo')
            ->whereNotNull('pneu_id')
            ->orderBy('id')
            ->chunkById(200, function (Collection $rows): void {
                foreach ($rows as $row) {
                    $cycleId = DB::table('pneus')
                        ->where('id', $row->pneu_id)
                        ->value('ciclo_vida');

                    DB::table('pneu_posicao_veiculo')->where('id', $row->id)->update([
                        'pneu_ciclo_id' => $this->getCycleId($row->pneu_id, $cycleId),
                    ]);
                }
            });
    }

    private function insertCatalogValues(string $table, string $column, Collection $values): void
    {
        $payload = $values
            ->filter(fn ($value) => filled($value))
            ->map(fn ($value) => trim((string) $value))
            ->unique()
            ->values()
            ->map(function ($value) use ($column, $table) {
                $payload = [
                    $column => $value,
                    'ativo' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                if ($table === 'pneu_medidas') {
                    $payload['descricao'] = $value;
                }

                return $payload;
            })
            ->all();

        if ($payload !== []) {
            $updateColumns = ['updated_at'];
            if ($table === 'pneu_medidas') {
                $updateColumns[] = 'descricao';
            }

            DB::table($table)->upsert($payload, [$column], $updateColumns);
        }
    }

    private function insertLocais(): void
    {
        $locais = [
            ['nome' => 'FROTA', 'tipo' => 'FROTA'],
            ['nome' => 'ESTOQUE CCO', 'tipo' => 'ESTOQUE'],
            ['nome' => 'ESTOQUE CTV', 'tipo' => 'ESTOQUE'],
            ['nome' => 'MANUTENÇÃO', 'tipo' => 'MANUTENCAO'],
            ['nome' => 'SUCATA', 'tipo' => 'SUCATA'],
        ];

        foreach ($locais as $local) {
            DB::table('pneu_locais')->updateOrInsert(
                ['nome' => $local['nome']],
                [
                    'tipo' => $local['tipo'],
                    'ativo' => true,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }

    private function getConfigValues(string $key): Collection
    {
        $raw = DB::table('db_config')
            ->where('group', 'config-pneu')
            ->where('key', $key)
            ->value('settings');

        if (! $raw) {
            return collect();
        }

        $decoded = json_decode($raw, true);

        return collect(is_array($decoded) ? $decoded : []);
    }

    private function getCycleId(int $pneuId, mixed $cycleNumber): ?int
    {
        return DB::table('pneu_ciclos')
            ->where('pneu_id', $pneuId)
            ->where('numero', $this->normalizeInt($cycleNumber))
            ->value('id');
    }

    private function normalizeInt(mixed $value): int
    {
        return (int) preg_replace('/[^0-9]/', '', (string) ($value ?? 0));
    }

    private function dropForeignIfExists(Blueprint $table, string $foreign): void
    {
        try {
            $table->dropForeign($foreign);
        } catch (Throwable) {
        }
    }
};
