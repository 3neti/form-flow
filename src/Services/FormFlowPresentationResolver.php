<?php

declare(strict_types=1);

namespace LBHurtado\FormFlowManager\Services;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use LBHurtado\FormFlowManager\Data\FormFlowInstructionsData;
use LBHurtado\FormFlowManager\Data\FormFlowStepData;

final class FormFlowPresentationResolver
{
    public const Schema = 'form-flow.presentation.v1';

    /** @return array<string, mixed> */
    public function defaults(): array
    {
        return [
            'schema' => self::Schema,
            'appearance' => [
                'mode' => 'light',
                'light' => ['canvas' => '#ffffff', 'surface' => '#ffffff', 'foreground' => '#0f172a', 'muted' => '#64748b', 'border' => '#e2e8f0', 'accent' => '#0f172a', 'accent_foreground' => '#ffffff'],
                'dark' => ['canvas' => '#0f172a', 'surface' => '#1e293b', 'foreground' => '#f8fafc', 'muted' => '#cbd5e1', 'border' => '#475569', 'accent' => '#f8fafc', 'accent_foreground' => '#0f172a'],
            ],
            'icons' => [
                'size' => 48,
                'review' => ['name' => 'receipt', 'color' => 'foreground'],
                'pending' => ['name' => 'clock', 'color' => '#d97706'],
                'success' => ['name' => 'circle-check', 'color' => '#16a34a'],
                'attention' => ['name' => 'triangle-alert', 'color' => '#d97706'],
                'error' => ['name' => 'circle-alert', 'color' => '#dc2626'],
            ],
            'brand' => ['name' => null, 'logo' => null, 'placement' => 'top_left', 'logo_height' => 32, 'logo_max_width' => 112],
            'layout' => ['density' => 'compact', 'capture_surface' => 'fill_available', 'scrolling' => 'when_needed'],
            'actions' => ['placement' => 'viewport_bottom', 'primary_position' => 'right', 'secondary_position' => 'left', 'reserve_content_space' => true, 'primary_label' => null, 'secondary_label' => null],
            'progress' => ['show' => true],
            'diagnostics' => ['show_versions' => false],
        ];
    }

    /** @param array<string, mixed> $value */
    private function validate(array $value, string $path): void
    {
        $rules = [
            "$path.appearance" => 'sometimes|array:mode,light,dark',
            "$path.appearance.mode" => 'sometimes|in:light,dark,system',
            "$path.icons" => 'sometimes|array:size,review,pending,success,attention,error',
            "$path.icons.size" => 'sometimes|integer|min:16|max:64',
        ];
        foreach (['light', 'dark'] as $mode) {
            $rules["$path.appearance.$mode"] = 'sometimes|array:canvas,surface,foreground,muted,border,accent,accent_foreground';
            foreach (['canvas', 'surface', 'foreground', 'muted', 'border', 'accent', 'accent_foreground'] as $token) {
                $rules["$path.appearance.$mode.$token"] = ['sometimes', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'];
            }
        }
        foreach (['review', 'pending', 'success', 'attention', 'error'] as $role) {
            $rules["$path.icons.$role"] = 'sometimes|array:name,color';
            $rules["$path.icons.$role.name"] = 'sometimes|in:receipt,clipboard-check,clock,circle-check,triangle-alert,circle-alert,shield-check';
            $rules["$path.icons.$role.color"] = ['sometimes', 'string', 'regex:/^(foreground|#[0-9a-fA-F]{6})$/'];
        }
        Validator::make([$path => $value], [...$rules,

            $path => 'array:schema,profile,brand,layout,actions,progress,diagnostics,appearance,icons',
            "$path.schema" => 'sometimes|in:'.self::Schema,
            "$path.profile" => 'sometimes|string|max:100',
            "$path.brand" => 'sometimes|array:name,logo,placement,logo_height,logo_max_width',
            "$path.brand.name" => 'nullable|string|max:100',
            "$path.brand.logo" => 'nullable|string|max:2048',
            "$path.brand.placement" => 'sometimes|in:top_left,center',
            "$path.brand.logo_height" => 'sometimes|integer|min:8|max:48',
            "$path.brand.logo_max_width" => 'sometimes|integer|min:32|max:240',
            "$path.layout" => 'sometimes|array:density,capture_surface,scrolling',
            "$path.layout.density" => 'sometimes|in:default,compact,immersive',
            "$path.layout.capture_surface" => 'sometimes|in:fill_available,contained',
            "$path.layout.scrolling" => 'sometimes|in:when_needed',
            "$path.actions" => 'sometimes|array:placement,primary_position,secondary_position,reserve_content_space,primary_label,secondary_label',
            "$path.actions.placement" => 'sometimes|in:inline,bottom,bottom_sticky,viewport_bottom',
            "$path.actions.primary_position" => 'sometimes|in:right',
            "$path.actions.secondary_position" => 'sometimes|in:left',
            "$path.actions.reserve_content_space" => 'sometimes|accepted',
            "$path.actions.primary_label" => 'nullable|string|max:100',
            "$path.actions.secondary_label" => 'nullable|string|max:100',
            "$path.progress" => 'sometimes|array:show',
            "$path.progress.show" => 'sometimes|boolean',
            "$path.diagnostics" => 'sometimes|array:show_versions',
            "$path.diagnostics.show_versions" => 'sometimes|boolean',
        ])->validate();
    }

    /** @param array<string, mixed> $driver @param array<string, mixed> $step @return array<string, mixed> */
    public function resolve(array $driver, array $step = []): array
    {
        $this->validate($driver, 'presentation');
        $this->validate($step, 'step_presentation');
        $profileName = $step['profile'] ?? $driver['profile'] ?? null;
        $profile = $profileName === null ? [] : config('form-flow.presentation.profiles.'.$profileName);
        if (! is_array($profile)) {
            throw ValidationException::withMessages(['presentation.profile' => 'Unknown Form Flow presentation profile.']);
        }
        $this->validate($profile, 'profile');
        if (isset($profile['profile'])) {
            throw ValidationException::withMessages(['presentation.profile' => 'Presentation profiles cannot inherit another profile.']);
        }
        $resolved = array_replace_recursive($this->defaults(), $profile, $driver, $step);
        $resolved['diagnostics']['show_versions'] = app()->environment(['local', 'testing'])
            && $resolved['diagnostics']['show_versions'];

        return $resolved;
    }

    public function prepare(FormFlowInstructionsData $instructions): FormFlowInstructionsData
    {
        if ($instructions->presentation === null && ! collect($instructions->steps)->contains(fn ($step): bool => isset(($step instanceof FormFlowStepData ? $step->config : ($step['config'] ?? []))['presentation']))) {
            return $instructions;
        }
        $payload = $instructions->toArray();
        $total = count($instructions->steps);
        $flowPresentation = $this->resolve($instructions->presentation ?? []);
        $flowPresentation['progress'] += ['current' => $total, 'total' => $total];
        $payload['metadata']['form_flow_presentation'] = $flowPresentation;
        $payload['metadata']['show_package_versions'] = $flowPresentation['diagnostics']['show_versions'];
        if (! $flowPresentation['diagnostics']['show_versions']) {
            $payload['metadata']['package_versions'] = [];
        }
        foreach ($instructions->steps as $index => $step) {
            $step = $step instanceof FormFlowStepData ? $step : FormFlowStepData::from($step);
            $resolved = $this->resolve($instructions->presentation ?? [], $step->config['presentation'] ?? []);
            $resolved['progress'] += ['current' => $index + 1, 'total' => $total];
            $resolved['fingerprint'] = hash('sha256', json_encode($resolved, JSON_THROW_ON_ERROR));
            $payload['steps'][$index]['config'] = array_replace($step->config, [
                'presentation' => $resolved,
                'ui_variant' => $resolved['layout']['density'],
                'action_placement' => $resolved['actions']['placement'],
                'ui_layout' => array_replace($step->config['ui_layout'] ?? [], ['density' => $resolved['layout']['density'], 'capture_surface' => $resolved['layout']['capture_surface'] === 'fill_available' ? 'edge_to_edge' : 'contained', 'minimize_scroll' => true]),
                'app_name' => $resolved['brand']['name'],
                'app_logo' => $resolved['brand']['logo'],
                'show_package_versions' => $resolved['diagnostics']['show_versions'],
                'package_versions' => $resolved['diagnostics']['show_versions'] ? ($step->config['package_versions'] ?? []) : [],
            ]);
        }

        return FormFlowInstructionsData::from($payload);
    }
}
