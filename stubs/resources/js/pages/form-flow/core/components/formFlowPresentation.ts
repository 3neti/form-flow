import { computed, inject, type ComputedRef, type InjectionKey } from 'vue';

export interface FormFlowPresentation {
  schema: 'form-flow.presentation.v1';
  profile?: string;
  brand: { name: string | null; logo: string | null; placement: 'top_left' | 'center'; logo_height: number; logo_max_width: number };
  layout: { density: 'default' | 'compact' | 'immersive'; capture_surface: 'fill_available' | 'contained'; scrolling: 'when_needed' };
  actions: { placement: 'inline' | 'bottom' | 'bottom_sticky' | 'viewport_bottom'; primary_label: string | null; secondary_label: string | null; reserve_content_space: boolean };
  progress: { show: boolean; current?: number; total?: number };
  diagnostics: { show_versions: boolean };
}

export const formFlowPresentationKey: InjectionKey<ComputedRef<FormFlowPresentation | null>> = Symbol('form-flow-presentation');
export function useFormFlowPresentation(): ComputedRef<FormFlowPresentation | null> {
  return inject(formFlowPresentationKey, computed(() => null));
}
