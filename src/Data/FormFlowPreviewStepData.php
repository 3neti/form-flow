<?php

declare(strict_types=1);

namespace LBHurtado\FormFlowManager\Data;

use Spatie\LaravelData\Data;

final class FormFlowPreviewStepData extends Data
{
    /**
     * @param  array<string, mixed>  $props
     */
    public function __construct(
        public int $index,
        public string $handler,
        public string $component,
        public array $props,
    ) {}
}
