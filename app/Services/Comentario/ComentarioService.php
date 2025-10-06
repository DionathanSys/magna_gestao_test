<?php

namespace App\Services\Comentario;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class ComentarioService
{
    public function adicionarComentario(array $comentavel, array $data)
    {
        [$id, $modelClass] = $comentavel;

        $data = Arr::only($data, ['conteudo', 'veiculo_id']);

        $modelClass::findOrFail($id)->comentarios()->create($data);

    }

}
