<?php

namespace App\Services;

use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;

class NotificacaoService
{
    private Collection|User $usersNotify;

    public function __construct(
        protected string $tipo, protected string $titulo, protected string $mensagem)
    {
        //TODO: Implementar o envio de notificações para usuários ativos, precisa add a coluna de usuários ativos
        $this->usersNotify = User::where('is_active', true)->get();
    }

    public function sendToDataBase($user = null): void
    {
        if($user){
           $user = $this->resolveUser($user);
        }

        Notification::make()
            ->title($this->tipo)
            ->body($this->mensagem)
            ->status($this->tipo)
            ->sendToDataBase($user ?? $this->usersNotify);
    }

    public function sendToast(): void
    {
        Notification::make()
            ->title($this->titulo)
            ->body($this->mensagem)
            ->status($this->tipo)
            ->send();
    }

    private function resolveUser(Collection|User|array|int $user): Collection|User|null
    {
        if($user instanceof Collection || $user instanceof User){
            return $user;
        }

        if(is_array($user) || is_int($user)){
            return User::find($user);
        }

        return null;

    }

    public static function error(string $titulo = 'Falha no processamento', string $mensagem = '', bool $toDataBase = false, Collection|User|array|int|null $user = null): void
    {
        $instance = new self('danger', $titulo, $mensagem);

        if ($toDataBase) {
            $instance->sendToDataBase();
        }

        $instance->sendToast();
    }

    public static function success(string $titulo = 'Sucesso', string $mensagem = '', bool $toDataBase = false, Collection|User|array|int|null $user = null): void
    {
        $instance = new self('success', $titulo, $mensagem);

        if ($toDataBase) {
            $instance->sendToDataBase();
        }

        $instance->sendToast();
    }

    public static function alert(string $titulo = 'Alerta', string $mensagem = '', bool $toDataBase = false, Collection|User|array|int|null $user = null): void
    {
        $instance = new self('warning', $titulo, $mensagem);

        if ($toDataBase) {
            $instance->sendToDataBase();
        }

        $instance->sendToast();
    }

    public static function debug(string $titulo = 'debug', string $mensagem = ''): void
    {
        $instance = new self('info', $titulo, $mensagem);
        $instance->usersNotify = User::where('is_admin', true)->get();
        $instance->sendToDataBase();
    }
}
