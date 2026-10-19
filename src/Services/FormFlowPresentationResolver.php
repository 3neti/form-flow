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

    /** @var list<string> */
    private array $screens = ['form', 'splash', 'completion', 'missing_handler'];

    /** @param list<string> $screens */
    public function registerScreens(array $screens): void
    {
        config()->set('form-flow.presentation.screen_keys', array_values(array_unique([...$this->screenKeys(), ...$screens])));
    }

    /** @return list<string> */
    private function screenKeys(): array
    {
        return array_values(array_unique([...$this->screens, ...(array) config('form-flow.presentation.screen_keys', [])]));
    }

    /** @return array<string, mixed> */
    public function defaults(): array
    {
        return [
            'schema' => self::Schema,
            'tokens' => [],
            'screens' => [],
            'notices' => [],
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
            'artwork' => [
                'background' => ['enabled' => false, 'opacity' => 0.45, 'blur' => 18, 'overlay' => 0.65],
                'foreground' => ['position' => 'top', 'top_spacing' => 24, 'gap' => 20, 'padding' => 20, 'max_width' => 672],
            ],
            'brand' => ['name' => null, 'logo' => null, 'placement' => 'top_left', 'logo_height' => 32, 'logo_max_width' => 112],
            'layout' => ['density' => 'compact', 'capture_surface' => 'fill_available', 'scrolling' => 'when_needed', 'artwork_aspect_ratio' => 'natural'],
            'actions' => ['placement' => 'viewport_bottom', 'primary_position' => 'right', 'secondary_position' => 'left', 'reserve_content_space' => true, 'primary_label' => null, 'secondary_label' => null],
            'progress' => ['show' => true],
            'diagnostics' => ['show_versions' => false],
        ];
    }

    /** @param array<string, mixed> $value */
    private function validate(array $value, string $path, bool $screenOverride = false): void
    {
        if (isset($value['screens']) && ! is_array($value['screens'])) {
            throw ValidationException::withMessages([$path.'.screens' => 'Screens must be a mapping.']);
        }
        foreach (($value['screens'] ?? []) as $key => $override) {
            if ($screenOverride || ! in_array($key, $this->screenKeys(), true) || ! is_array($override)
                || array_intersect(['screens', 'profile', 'schema', 'screen'], array_keys($override))) {
                throw ValidationException::withMessages([$path.'.screens' => 'Unknown screen or recursive screen configuration.']);
            }
            $this->validate($override, $path.'.screens.'.$key, true);
        }
        $rules = [
            "$path.screen" => 'sometimes|in:'.implode(',', $this->screenKeys()),
            "$path.screens" => 'sometimes|array',
            "$path.tokens" => 'sometimes|array:content_width,spacing,typography,control_size',
            "$path.tokens.content_width" => 'sometimes|in:narrow,standard,wide',
            "$path.tokens.spacing" => 'sometimes|in:compact,comfortable,spacious',
            "$path.tokens.typography" => 'sometimes|in:small,standard,large',
            "$path.tokens.control_size" => 'sometimes|in:standard,large',
            "$path.actions.roles" => 'sometimes|array:continue,back,capture,retake,clear,send_code,verify,retry,confirm_claim,cancel,open_link,create_payment,download,submit',
            "$path.notices" => 'sometimes|array:info,pending,success,attention,error',

            "$path.artwork" => 'sometimes|array:background,foreground',
            "$path.artwork.background" => 'sometimes|array:enabled,opacity,blur,overlay',
            "$path.artwork.background.enabled" => 'sometimes|boolean',
            "$path.artwork.background.opacity" => 'sometimes|numeric|min:0|max:1',
            "$path.artwork.background.blur" => 'sometimes|integer|min:0|max:40',
            "$path.artwork.background.overlay" => 'sometimes|numeric|min:0|max:1',
            "$path.artwork.foreground" => 'sometimes|array:position,top_spacing,gap,padding,max_width',
            "$path.artwork.foreground.position" => 'sometimes|in:top,center',
            "$path.artwork.foreground.top_spacing" => 'sometimes|integer|min:0|max:96',
            "$path.artwork.foreground.gap" => 'sometimes|integer|min:0|max:48',
            "$path.artwork.foreground.padding" => 'sometimes|integer|min:8|max:40',
            "$path.artwork.foreground.max_width" => 'sometimes|integer|min:240|max:1200',

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
        foreach (['continue', 'back', 'capture', 'retake', 'clear', 'send_code', 'verify', 'retry', 'confirm_claim', 'cancel', 'open_link', 'create_payment', 'download', 'submit'] as $role) {
            $rules["$path.actions.roles.$role"] = 'sometimes|array:label,processing_label';
            foreach (['label', 'processing_label'] as $label) {
                $rules["$path.actions.roles.$role.$label"] = ['sometimes', 'string', 'min:1', 'max:100', 'not_regex:/[<>]/'];
            }
        }
        foreach (['info', 'pending', 'success', 'attention', 'error'] as $role) {
            $rules["$path.notices.$role"] = 'sometimes|array:background,foreground,border,icon';
            foreach (['background', 'foreground', 'border'] as $color) {
                $rules["$path.notices.$role.$color"] = ['sometimes', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'];
            }
            $rules["$path.notices.$role.icon"] = 'sometimes|in:receipt,clipboard-check,clock,circle-check,triangle-alert,circle-alert,shield-check';
        }
        Validator::make([$path => $value], [...$rules,

            $path => 'array:schema,profile,screen,screens,tokens,notices,brand,layout,actions,progress,diagnostics,appearance,icons,artwork',
            "$path.schema" => 'sometimes|in:'.self::Schema,
            "$path.profile" => 'sometimes|string|max:100',
            "$path.brand" => 'sometimes|array:name,logo,placement,logo_height,logo_max_width',
            "$path.brand.name" => 'nullable|string|max:100',
            "$path.brand.logo" => 'nullable|string|max:2048',
            "$path.brand.placement" => 'sometimes|in:top_left,center',
            "$path.brand.logo_height" => 'sometimes|integer|min:8|max:96',
            "$path.brand.logo_max_width" => 'sometimes|integer|min:32|max:240',
            "$path.layout" => 'sometimes|array:density,capture_surface,scrolling,artwork_aspect_ratio',
            "$path.layout.density" => 'sometimes|in:default,compact,immersive',
            "$path.layout.capture_surface" => 'sometimes|in:fill_available,contained',
            "$path.layout.artwork_aspect_ratio" => 'sometimes|in:natural,1200/630,1/1,4/3,3/2,16/9',
            "$path.layout.scrolling" => 'sometimes|in:when_needed',
            "$path.actions" => 'sometimes|array:placement,primary_position,secondary_position,reserve_content_space,primary_label,secondary_label,roles',
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
    public function resolve(array $driver, array $step = [], ?string $screen = null): array
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
        $base = array_replace_recursive($this->defaults(), $profile, $driver);
        $screen = $step['screen'] ?? $screen ?? $driver['screen'] ?? null;
        if ($screen !== null && ! in_array($screen, $this->screenKeys(), true)) {
            throw ValidationException::withMessages(['presentation.screen' => 'Unknown presentation screen.']);
        }
        $resolved = array_replace_recursive($base, $base['screens'][$screen] ?? [], $step);
        if ($screen !== null) {
            $resolved['screen'] = $screen;
        }
        if (! app()->environment(['local', 'testing'])) {
            foreach ($resolved['screens'] as &$override) {
                $override['diagnostics']['show_versions'] = false;
            }
            unset($override);
        }
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
            $resolved = $this->resolve($instructions->presentation ?? [], $step->config['presentation'] ?? [], in_array($step->handler, $this->screenKeys(), true) ? $step->handler : null);
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
