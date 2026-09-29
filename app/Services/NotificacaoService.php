<?php

namespace App\Services;

use App\Jobs\Telegram\EnviarMensagemTelegram;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;

class NotificacaoService
{
    private Collection|User $usersNotify;

    public function __construct(
        protected string $tipo, protected string $titulo, protected string $mensagem)
    {
        $this->usersNotify = User::query()->get();
    }

    public function sendToDataBase($user = null): void
    {
        $recipients = $user === null ? $this->usersNotify : $this->resolveUser($user);

        Notification::make()
            ->title($this->tipo)
            ->body($this->mensagem)
            ->status($this->tipo)
            ->sendToDataBase($recipients);

        $this->sendToTelegram($recipients);
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
        if ($user instanceof Collection || $user instanceof User) {
            return $user;
        }

        if (is_array($user) || is_int($user)) {
            return User::find($user);
        }

        return null;

    }

    private function sendToTelegram(Collection|User|null $recipients): void
    {
        if (! (bool) config('services.telegram.enabled', false) || blank(config('services.telegram.bot_token'))) {
            return;
        }

        $users = $recipients instanceof User ? collect([$recipients]) : ($recipients ?? collect());
        $message = trim($this->titulo."\n\n".$this->mensagem);

        $users
            ->filter(fn (User $user): bool => filled($user->telegram_chat_id))
            ->each(fn (User $user) => EnviarMensagemTelegram::dispatch(
                (string) $user->telegram_chat_id,
                $message,
            ));
    }

    public static function error(string $titulo = 'Falha no processamento', string $mensagem = '', bool $toDataBase = false, Collection|User|array|int|null $user = null): void
    {
        $instance = new self('danger', $titulo, $mensagem);

        if ($toDataBase) {
            $instance->sendToDataBase($user);
        }

        $instance->sendToast();
    }

    public static function success(string $titulo = 'Sucesso', string $mensagem = '', bool $toDataBase = false, Collection|User|array|int|null $user = null): void
    {
        $instance = new self('success', $titulo, $mensagem);

        if ($toDataBase) {
            $instance->sendToDataBase($user);
        }

        $instance->sendToast();
    }

    public static function alert(string $titulo = 'Alerta', string $mensagem = '', bool $toDataBase = false, Collection|User|array|int|null $user = null): void
    {
        $instance = new self('warning', $titulo, $mensagem);

        if ($toDataBase) {
            $instance->sendToDataBase($user);
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
