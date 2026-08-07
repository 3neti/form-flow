<?php

declare(strict_types=1);

namespace LBHurtado\FormFlowManager\Services;

use LBHurtado\FormFlowManager\Contracts\FormHandlerPreviewInterface;
use LBHurtado\FormFlowManager\Data\FormFlowInstructionsData;
use LBHurtado\FormFlowManager\Data\FormFlowPreviewStepData;
use LBHurtado\FormFlowManager\Data\FormFlowStepData;
use LogicException;

final class FormFlowPreviewCompiler
{
    public function __construct(private readonly FormHandlerRegistry $handlers) {}

    /**
     * @param  array<string, mixed>  $context
     * @return array<int, FormFlowPreviewStepData>
     */
    public function compile(FormFlowInstructionsData $instructions, array $context = []): array
    {
        return collect($instructions->steps)
            ->values()
            ->map(function (FormFlowStepData|array $step, int $index) use ($context): FormFlowPreviewStepData {
                $step = $step instanceof FormFlowStepData ? $step : FormFlowStepData::from($step);
                $handler = $this->handlers->resolve($step->handler);

                if (! $handler instanceof FormHandlerPreviewInterface) {
                    throw new LogicException(sprintf(
                        'FormFlow handler [%s] must implement %s before it can be previewed.',
                        $step->handler,
                        FormHandlerPreviewInterface::class,
                    ));
                }

                $preview = $handler->preview($step, array_merge($context, [
                    'flow_id' => null,
                    'step_index' => $index,
                    'preview_mode' => true,
                ]));

                return new FormFlowPreviewStepData(
                    index: $index,
                    handler: $step->handler,
                    component: $preview['component'],
                    props: array_merge($preview['props'], ['preview_mode' => true]),
                );
            })
            ->all();
    }
}
