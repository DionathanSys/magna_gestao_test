<?php

namespace App\Services\Comentario;

use App\Traits\UserCheckTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class ComentarioService
{
    use UserCheckTrait;

    public function adicionarComentario(array $comentavel, array $data)
    {
        [$id, $modelClass] = $comentavel;

        $data['created_by'] = $this->getUserIdChecked();

        $data = Arr::only($data, ['conteudo', 'veiculo_id', 'created_by']);

        $modelClass::findOrFail($id)->comentarios()->create($data);

    }

}
