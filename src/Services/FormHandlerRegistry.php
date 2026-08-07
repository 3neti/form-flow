<?php

declare(strict_types=1);

namespace LBHurtado\FormFlowManager\Services;

use LBHurtado\FormFlowManager\Contracts\FormHandlerInterface;
use LBHurtado\FormFlowManager\Handlers\FormHandler;
use LBHurtado\FormFlowManager\Handlers\MissingHandler;
use LBHurtado\FormFlowManager\Handlers\SplashHandler;

final class FormHandlerRegistry
{
    /**
     * @return class-string<FormHandlerInterface>
     */
    public function classFor(string $handler): string
    {
        $handlers = array_merge([
            'form' => FormHandler::class,
            'splash' => SplashHandler::class,
        ], config('form-flow.handlers', []));

        $candidate = $handlers[$handler] ?? MissingHandler::class;

        return is_string($candidate) && is_a($candidate, FormHandlerInterface::class, true)
            ? $candidate
            : MissingHandler::class;
    }

    public function resolve(string $handler): FormHandlerInterface
    {
        return app($this->classFor($handler));
    }
}
