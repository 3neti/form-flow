import { computed, inject, type ComputedRef, type InjectionKey } from 'vue';

export interface FormFlowPresentation {
  schema: 'form-flow.presentation.v1';
  profile?: string;
  appearance?: {
    mode: 'light' | 'dark' | 'system';
    light: ClaimPalette;
    dark: ClaimPalette;
  };
  icons?: { size: number } & Record<ClaimIconRole, { name: ClaimIconName; color: string }>;
  artwork?: {
    background: { enabled: boolean; opacity: number; blur: number; overlay: number };
    foreground: { position: 'top' | 'center'; top_spacing: number; gap: number; padding: number; max_width: number };
  };
  brand: { name: string | null; logo: string | null; placement: 'top_left' | 'center'; logo_height: number; logo_max_width: number };
  layout: { density: 'default' | 'compact' | 'immersive'; capture_surface: 'fill_available' | 'contained'; scrolling: 'when_needed'; artwork_aspect_ratio?: 'natural' | '1200/630' | '1/1' | '4/3' | '3/2' | '16/9' };
  actions: { placement: 'inline' | 'bottom' | 'bottom_sticky' | 'viewport_bottom'; primary_label: string | null; secondary_label: string | null; reserve_content_space: boolean };
  progress: { show: boolean; current?: number; total?: number };
  diagnostics: { show_versions: boolean };
}

export const formFlowPresentationKey: InjectionKey<ComputedRef<FormFlowPresentation | null>> = Symbol('form-flow-presentation');
export function useFormFlowPresentation(): ComputedRef<FormFlowPresentation | null> {
  return inject(formFlowPresentationKey, computed(() => null));
}

export type ClaimPalette = Record<'canvas' | 'surface' | 'foreground' | 'muted' | 'border' | 'accent' | 'accent_foreground', string>;
export type ClaimIconRole = 'review' | 'pending' | 'success' | 'attention' | 'error';
export type ClaimIconName = 'receipt' | 'clipboard-check' | 'clock' | 'circle-check' | 'triangle-alert' | 'circle-alert' | 'shield-check';

export const formFlowArtworkKey: InjectionKey<{setSource: (source: string | null) => void}> = Symbol('form-flow-artwork');
