<?php

namespace App\Traits;

trait ServiceResponseTrait
{
    /**
     * Indica se houve erro na operação
     */
    protected bool $hasError = false;

    /**
     * Mensagem para exibir no front-end
     */
    protected string $message = '';

    /**
     * Tipo da mensagem (success, error, warning, info)
     */
    protected string $messageType = 'success';

    /**
     * Dados adicionais para retornar
     */
    protected array $data = [];

    /**
     * Define um erro
     */
    public function setError(string $message, array $data = []): self
    {
        $this->hasError = true;
        $this->message = $message;
        $this->messageType = 'error';
        $this->data = $data;

        return $this;
    }

    /**
     * Define uma mensagem de sucesso
     */
    public function setSuccess(string $message, array $data = []): self
    {
        $this->hasError = false;
        $this->message = $message;
        $this->messageType = 'success';
        $this->data = $data;

        return $this;
    }

    /**
     * Define uma mensagem de aviso
     */
    public function setWarning(string $message, array $data = []): self
    {
        $this->hasError = false;
        $this->message = $message;
        $this->messageType = 'warning';
        $this->data = $data;

        return $this;
    }

    /**
     * Define uma mensagem informativa
     */
    public function setInfo(string $message, array $data = []): self
    {
        $this->hasError = false;
        $this->message = $message;
        $this->messageType = 'info';
        $this->data = $data;

        return $this;
    }

    /**
     * Verifica se há erro
     */
    public function hasError(): bool
    {
        return $this->hasError;
    }

    /**
     * Retorna a mensagem
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * Retorna o tipo da mensagem
     */
    public function getMessageType(): string
    {
        return $this->messageType;
    }

    /**
     * Retorna os dados adicionais
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Retorna a resposta completa
     */
    public function getResponse(): array
    {
        return [
            'success' => !$this->hasError,
            'message' => $this->message,
            'type' => $this->messageType,
            'data' => $this->data,
        ];
    }

    /**
     * Limpa o estado da resposta
     */
    public function clearResponse(): self
    {
        $this->hasError = false;
        $this->message = '';
        $this->messageType = 'success';
        $this->data = [];

        return $this;
    }
}
