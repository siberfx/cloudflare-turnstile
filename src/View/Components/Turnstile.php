<?php

declare(strict_types=1);

namespace Siberfx\Turnstile\View\Components;

use Illuminate\View\Component;
use Siberfx\Turnstile\Turnstile as TurnstileClient;

class Turnstile extends Component
{
    public string $scriptUrl;

    public function __construct(
        TurnstileClient $turnstile,
        public ?string $siteKey = null,
        public ?string $theme = null,
        public ?string $size = null,
        public ?string $appearance = null,
        public ?string $language = null,
        public ?string $action = null,
        public ?string $cdata = null,
        public ?string $callback = null,
        public ?string $errorCallback = null,
        public ?string $expiredCallback = null,
        public ?string $responseFieldName = null,
        public bool $script = true,
    ) {
        $defaults = (array) config('turnstile.widget', []);

        $this->siteKey ??= $turnstile->siteKey();
        $this->theme ??= $defaults['theme'] ?? null;
        $this->size ??= $defaults['size'] ?? null;
        $this->appearance ??= $defaults['appearance'] ?? null;
        $this->language ??= $defaults['language'] ?? null;
        $this->scriptUrl = $turnstile->scriptUrl();

        if ($this->responseFieldName === null && $turnstile->field() !== TurnstileClient::DEFAULT_FIELD) {
            $this->responseFieldName = $turnstile->field();
        }
    }

    /**
     * @return array<string, string>
     */
    public function dataAttributes(): array
    {
        return array_filter([
            'data-sitekey' => $this->siteKey,
            'data-theme' => $this->theme,
            'data-size' => $this->size,
            'data-appearance' => $this->appearance,
            'data-language' => $this->language,
            'data-action' => $this->action,
            'data-cdata' => $this->cdata,
            'data-callback' => $this->callback,
            'data-error-callback' => $this->errorCallback,
            'data-expired-callback' => $this->expiredCallback,
            'data-response-field-name' => $this->responseFieldName,
        ], static fn (?string $value): bool => $value !== null && $value !== '');
    }

    public function render(): string
    {
        return 'turnstile::components.turnstile';
    }
}
