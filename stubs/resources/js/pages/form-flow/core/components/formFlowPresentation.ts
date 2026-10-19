import { computed, inject, type ComputedRef, type InjectionKey } from "vue";

export interface FormFlowPresentation {
  schema: "form-flow.presentation.v1";
  profile?: string;
  screen?: string;
  screens?: Record<string, PresentationOverride>;
  tokens?: {
    content_width?: "narrow" | "standard" | "wide";
    spacing?: "compact" | "comfortable" | "spacious";
    typography?: "small" | "standard" | "large";
    control_size?: "standard" | "large";
  };
  notices?: Partial<Record<NoticeRole, Partial<NoticeStyle>>>;
  appearance?: {
    mode: "light" | "dark" | "system";
    light: ClaimPalette;
    dark: ClaimPalette;
  };
  icons?: { size: number } & Record<
    ClaimIconRole,
    { name: ClaimIconName; color: string }
  >;
  artwork?: {
    background: {
      enabled: boolean;
      opacity: number;
      blur: number;
      overlay: number;
    };
    foreground: {
      position: "top" | "center";
      top_spacing: number;
      gap: number;
      padding: number;
      max_width: number;
    };
  };
  brand: {
    name: string | null;
    logo: string | null;
    placement: "top_left" | "center";
    logo_height: number;
    logo_max_width: number;
  };
  layout: {
    density: "default" | "compact" | "immersive";
    capture_surface: "fill_available" | "contained";
    scrolling: "when_needed";
    artwork_aspect_ratio?:
      | "natural"
      | "1200/630"
      | "1/1"
      | "4/3"
      | "3/2"
      | "16/9";
  };
  actions: {
    placement: "inline" | "bottom" | "bottom_sticky" | "viewport_bottom";
    roles?: Partial<
      Record<ActionRole, { label?: string; processing_label?: string }>
    >;
    primary_label: string | null;
    secondary_label: string | null;
    reserve_content_space: boolean;
  };
  progress: { show: boolean; current?: number; total?: number };
  diagnostics: { show_versions: boolean };
}

export const formFlowPresentationKey: InjectionKey<
  ComputedRef<FormFlowPresentation | null>
> = Symbol("form-flow-presentation");
export function useFormFlowPresentation(): ComputedRef<FormFlowPresentation | null> {
  return inject(
    formFlowPresentationKey,
    computed(() => null),
  );
}

export type ClaimPalette = Record<
  | "canvas"
  | "surface"
  | "foreground"
  | "muted"
  | "border"
  | "accent"
  | "accent_foreground",
  string
>;
export type ClaimIconRole =
  | "review"
  | "pending"
  | "success"
  | "attention"
  | "error";
export type ClaimIconName =
  | "receipt"
  | "clipboard-check"
  | "clock"
  | "circle-check"
  | "triangle-alert"
  | "circle-alert"
  | "shield-check";

export const formFlowArtworkKey: InjectionKey<{
  setSource: (source: string | null) => void;
}> = Symbol("form-flow-artwork");

export type ActionRole =
  | "continue"
  | "back"
  | "capture"
  | "retake"
  | "clear"
  | "send_code"
  | "verify"
  | "retry"
  | "confirm_claim"
  | "cancel"
  | "open_link"
  | "create_payment"
  | "download"
  | "submit";
export type NoticeRole = "info" | "pending" | "success" | "attention" | "error";
export type NoticeStyle = {
  background: string;
  foreground: string;
  border: string;
  icon: ClaimIconName;
};
type DeepPartial<T> = T extends object
  ? { [K in keyof T]?: DeepPartial<T[K]> }
  : T;
export type PresentationOverride = DeepPartial<
  Omit<FormFlowPresentation, "schema" | "profile" | "screen" | "screens">
>;

function mergePresentation(
  base: Record<string, unknown>,
  extra: Record<string, unknown>,
): Record<string, unknown> {
  const result = { ...base };
  for (const [key, value] of Object.entries(extra)) {
    result[key] =
      value && typeof value === "object" && !Array.isArray(value)
        ? mergePresentation(
            (base[key] as Record<string, unknown>) ?? {},
            value as Record<string, unknown>,
          )
        : value;
  }
  return result;
}

/** Resolve only frozen overrides; a server-resolved step retains its explicit settings. */
export function presentationForScreen(
  profile: FormFlowPresentation | null | undefined,
  screen?: string,
): FormFlowPresentation | null {
  if (!profile || !screen || profile.screen === screen) return profile ?? null;
  return {
    ...mergePresentation(
      profile as unknown as Record<string, unknown>,
      (profile.screens?.[screen] as Record<string, unknown>) ?? {},
    ),
    screen,
  } as unknown as FormFlowPresentation;
}

export function actionLabel(
  profile: FormFlowPresentation | null | undefined,
  role: ActionRole | undefined,
  fallback: string,
  processing = false,
  secondary = false,
): string {
  const specific = role ? profile?.actions.roles?.[role] : undefined;
  if (processing) return specific?.processing_label ?? fallback;
  if (specific?.label) return specific.label;
  // A generic navigation label must never rename a capture/verification/financial action.
  const legacy = !role || role === "continue" || role === "back";
  return legacy
    ? ((secondary
        ? profile?.actions.secondary_label
        : profile?.actions.primary_label) ?? fallback)
    : fallback;
}
