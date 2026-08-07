<?php

declare(strict_types=1);

namespace LBHurtado\FormFlowManager\Contracts;

use LBHurtado\FormFlowManager\Data\FormFlowStepData;

interface FormHandlerPreviewInterface
{
    /**
     * @param  array<string, mixed>  $context
     * @return array{component: string, props: array<string, mixed>}
     */
    public function preview(FormFlowStepData $step, array $context = []): array;
}
