<?php

declare(strict_types=1);

namespace LBHurtado\FormFlowManager\Services;

use Illuminate\Support\Facades\File;
use LBHurtado\FormFlowManager\Data\FormFlowInstructionsData;
use LBHurtado\FormFlowManager\Handlers\FormHandler;
use LBHurtado\FormFlowManager\Handlers\MissingHandler;
use Symfony\Component\Yaml\Yaml;

/**
 * Driver Service
 *
 * Transforms voucher-like instruction sources to FormFlowInstructionsData using YAML configuration.
 * This service uses declarative YAML files to define form flow transformations.
 */
class DriverService
{
    protected array $config;

    protected ?TemplateProcessor $templateProcessor = null;

    /**
     * Load driver config from YAML file.
     */
    public function loadConfig(string $driverName = 'voucher-redemption'): void
    {
        $path = config_path("form-flow-drivers/{$driverName}.yaml");

        if (! File::exists($path)) {
            throw new \RuntimeException("Driver config not found: {$path}");
        }

        $this->config = Yaml::parseFile($path);
    }

    /**
     * Get or create TemplateProcessor instance.
     */
    protected function getTemplateProcessor(): TemplateProcessor
    {
        if (! $this->templateProcessor) {
            $this->templateProcessor = new TemplateProcessor;
        }

        return $this->templateProcessor;
    }

    /**
     * Transform source object to form flow instructions using YAML driver.
     */
    public function transform(object $voucher): FormFlowInstructionsData
    {
        if (! isset($this->config)) {
            $this->loadConfig();
        }

        $context = $this->buildContext($voucher);

        return FormFlowInstructionsData::from([
            'reference_id' => $this->processReferenceId($context),
            'steps' => $this->processSteps($context),
            'callbacks' => $this->processCallbacks($context),
        ]);
    }

    /**
     * Build context from source object for template processing.
     */
    protected function buildContext(object $voucher): array
    {
        $instructions = $voucher->instructions;
        $inputFields = $instructions->inputs->fields ?? [];

        $fieldNames = array_map(
            fn ($field) => is_object($field) && isset($field->value)
                ? $field->value
                : (string) $field,
            $inputFields
        );

        $allowedMobile = $instructions->cash->validation->mobile ?? null;
        $hasAllowedMobile = filled($allowedMobile);

        return [
            'code' => $voucher->code,
            'amount' => (float) ($instructions->cash->amount ?? 0),
            'currency' => $instructions->cash->currency ?? 'PHP',
            'allowed_mobile' => $allowedMobile,
            'has_allowed_mobile' => $hasAllowedMobile ? 'true' : 'false',
            'should_persist_mobile' => $hasAllowedMobile ? 'false' : 'true',
            'owner_name' => $voucher->owner->name ?? 'Unknown',
            'base_url' => url(''),
            'timestamp' => time(),

            'splash_enabled' => config('splash.enabled', true) ? 'true' : 'false',

            'has_name' => in_array('name', $fieldNames, true),
            'has_email' => in_array('email', $fieldNames, true),
            'has_birth_date' => in_array('birth_date', $fieldNames, true),
            'has_address' => in_array('address', $fieldNames, true),
            'has_location' => in_array('location', $fieldNames, true),
            'has_selfie' => in_array('selfie', $fieldNames, true),
            'has_signature' => in_array('signature', $fieldNames, true),
            'has_kyc' => in_array('kyc', $fieldNames, true),
            'has_otp' => in_array('otp', $fieldNames, true),
            'has_reference_code' => in_array('reference_code', $fieldNames, true),
            'has_gross_monthly_income' => in_array('gross_monthly_income', $fieldNames, true),

            'rider' => [
                'message' => $instructions->rider->message ?? null,
                'url' => $instructions->rider->url ?? null,
                'redirect_timeout' => $instructions->rider->redirect_timeout ?? null,
                'splash' => $instructions->rider->splash ?? null,
                'splash_timeout' => $instructions->rider->splash_timeout ?? null,
            ],

            'slice_mode' => method_exists($voucher, 'getSliceMode') ? $voucher->getSliceMode() : null,
            'min_withdrawal' => method_exists($voucher, 'getMinWithdrawal') ? $voucher->getMinWithdrawal() : null,
            'available_balance' => method_exists($voucher, 'getRemainingBalance')
                ? ($voucher->getRemainingBalance() ?: (float) ($instructions->cash->amount ?? 0))
                : (float) ($instructions->cash->amount ?? 0),
            'max_slices' => method_exists($voucher, 'getMaxSlices') ? $voucher->getMaxSlices() : null,

            'voucher' => [
                'code' => $voucher->code,
                'instructions' => [
                    'cash' => [
                        'amount' => $instructions->cash->amount ?? 0,
                        'currency' => $instructions->cash->currency ?? 'PHP',
                    ],
                ],
            ],
        ];
    }

    protected function processReferenceId(array $context): string
    {
        $template = $this->config['reference_id'] ?? 'disburse-{{ code }}-{{ timestamp }}';

        return $this->getTemplateProcessor()->process($template, $context);
    }

    protected function processCallbacks(array $context): array
    {
        $callbacksConfig = $this->config['callbacks'] ?? [];
        $processor = $this->getTemplateProcessor();

        return [
            'on_complete' => $processor->process($callbacksConfig['on_complete'] ?? '', $context),
            'on_cancel' => $processor->process($callbacksConfig['on_cancel'] ?? '', $context),
        ];
    }

    protected function processSteps(array $context): array
    {
        $stepsConfig = $this->config['steps'] ?? [];
        $processor = $this->getTemplateProcessor();
        $steps = [];

        foreach ($stepsConfig as $stepConfig) {
            if (isset($stepConfig['condition'])) {
                $conditionResult = $processor->process($stepConfig['condition'], $context);

                if (! $this->evaluateCondition($conditionResult)) {
                    continue;
                }
            }

            $handlerName = $stepConfig['handler'] ?? 'form';

            if (! $this->isHandlerAvailable($handlerName)) {
                $steps[] = $this->createMissingHandlerStep(
                    $handlerName,
                    $stepConfig['title'] ?? 'Unknown Step',
                    $stepConfig
                );

                continue;
            }

            $step = [
                'handler' => $handlerName,
                'config' => [],
            ];

            if (isset($stepConfig['step_name'])) {
                $step['config']['step_name'] = $stepConfig['step_name'];
            }

            if (isset($stepConfig['title'])) {
                $step['config']['title'] = $processor->process($stepConfig['title'], $context);
            }

            if (isset($stepConfig['description'])) {
                $step['config']['description'] = $processor->process($stepConfig['description'], $context);
            }

            if (($stepConfig['handler'] ?? null) === 'form' && isset($stepConfig['fields'])) {
                $step['config']['fields'] = $this->processFields($stepConfig['fields'], $context);
            }

            if (isset($stepConfig['config'])) {
                $step['config'] = array_merge(
                    $step['config'],
                    $processor->processArray($stepConfig['config'], $context)
                );
            }

            if ($step['handler'] !== 'form' || ! empty($step['config']['fields'])) {
                $steps[] = $step;
            }
        }

        return $steps;
    }

    protected function processFields(array $fields, array $context): array
    {
        $processor = $this->getTemplateProcessor();
        $processedFields = [];

        foreach ($fields as $field) {
            if (isset($field['condition'])) {
                $conditionResult = $processor->process($field['condition'], $context);

                if (! $this->evaluateCondition($conditionResult)) {
                    continue;
                }
            }

            $fieldCopy = $field;
            unset($fieldCopy['condition']);

            $processedField = $processor->processArray($fieldCopy, $context);
            $processedFields[] = $this->normalizeFieldTypes($processedField);
        }

        return $processedFields;
    }

    protected function normalizeFieldTypes(array $field): array
    {
        foreach (['persist', 'readonly', 'required', 'disabled'] as $booleanKey) {
            if (! array_key_exists($booleanKey, $field)) {
                continue;
            }

            $value = $field[$booleanKey];

            if (is_bool($value)) {
                continue;
            }

            if (! is_string($value)) {
                continue;
            }

            $normalized = strtolower(trim($value));

            if ($normalized === 'true') {
                $field[$booleanKey] = true;
            }

            if ($normalized === 'false' || $normalized === '') {
                $field[$booleanKey] = false;
            }
        }

        return $field;
    }

    protected function evaluateCondition(string $result): bool
    {
        $result = trim($result);

        if ($result === '' || $result === 'false' || $result === '0') {
            return false;
        }

        return true;
    }

    protected function isHandlerAvailable(string $handlerName): bool
    {
        $handlerClass = $this->getHandlerClass($handlerName);

        return $handlerClass && class_exists($handlerClass);
    }

    protected function getHandlerClass(string $handlerName): ?string
    {
        $configHandlers = config('form-flow.handlers', []);

        $builtInHandlers = [
            'form' => FormHandler::class,
            'missing' => MissingHandler::class,
        ];

        $handlers = array_merge($builtInHandlers, $configHandlers);

        return $handlers[$handlerName] ?? null;
    }

    protected function createMissingHandlerStep(
        string $handlerName,
        string $title,
        array $originalConfig
    ): array {
        $config = [
            'missing_handler_name' => $handlerName,
            'missing_handler_title' => $title,
            'original_config' => $originalConfig,
            'install_hint' => $this->getInstallHint($handlerName),
        ];

        if (isset($originalConfig['step_name'])) {
            $config['step_name'] = $originalConfig['step_name'];
        }

        return [
            'handler' => 'missing',
            'config' => $config,
        ];
    }

    protected function getInstallHint(string $handlerName): string
    {
        $packageMap = [
            'kyc' => 'lbhurtado/form-handler-kyc',
            'location' => 'lbhurtado/form-handler-location',
            'otp' => 'lbhurtado/form-handler-otp',
            'signature' => 'lbhurtado/form-handler-signature',
            'selfie' => 'lbhurtado/form-handler-selfie',
        ];

        $package = $packageMap[$handlerName] ?? "lbhurtado/form-handler-{$handlerName}";

        return "composer require {$package}";
    }
}
