export type FormFlowUiVariant = "default" | "compact" | "immersive";

const variants: FormFlowUiVariant[] = ["default", "compact", "immersive"];

export function normalizeFormFlowUiVariant(
  value?: string | null,
): FormFlowUiVariant {
  return variants.includes(value as FormFlowUiVariant)
    ? (value as FormFlowUiVariant)
    : "default";
}
