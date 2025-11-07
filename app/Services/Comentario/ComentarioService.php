<?php

namespace App\Services\Comentario;

use App\Traits\UserCheckTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class ComentarioService
{
    use UserCheckTrait;

    public function adicionarComentario(array $comentavel, array $data)
    {
        [$id, $modelClass] = $comentavel;

        $data['created_by'] = $this->getUserIdChecked();

        $data = Arr::only($data, ['conteudo', 'veiculo_id', 'created_by']);

        $comentario = $modelClass::findOrFail($id)->comentarios()->create($data);

        Log::info('Comentário adicionado.', [
            'comentavel_type'   => $modelClass,
            'comentavel_id'     => $id,
            'comentario_id'     => $comentario->id,
            'created_by'        => $data['created_by'],
        ]);
    }

}
