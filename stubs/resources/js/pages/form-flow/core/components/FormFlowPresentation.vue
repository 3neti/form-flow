<script setup lang="ts">
import { computed, provide, ref, onMounted, onBeforeUnmount } from "vue";
import {
  formFlowPresentationKey,
  formFlowArtworkKey,
  type FormFlowPresentation,
  presentationForScreen,
} from "./formFlowPresentation";

import FormFlowArtworkBackdrop from "./FormFlowArtworkBackdrop.vue";

const artworkSource = ref<string | null>(null);
provide(formFlowArtworkKey, {
  setSource: (source) => {
    artworkSource.value = source;
  },
});
const props = withDefaults(
  defineProps<{
    presentation?: FormFlowPresentation | null;
    screen?: string;
    showHeader?: boolean;
  }>(),
  { showHeader: true },
);
const policy = computed(() =>
  presentationForScreen(props.presentation, props.screen),
);
provide(formFlowPresentationKey, policy);
const systemDark = ref(false);
let media: MediaQueryList | undefined;
const updateSystem = () => {
  systemDark.value = media?.matches ?? false;
};
onMounted(() => {
  if (typeof window.matchMedia !== "function") return;
  media = window.matchMedia("(prefers-color-scheme: dark)");
  updateSystem();
  media.addEventListener("change", updateSystem);
});
onBeforeUnmount(() => media?.removeEventListener("change", updateSystem));
const dark = computed(
  () =>
    policy.value?.appearance?.mode === "dark" ||
    (policy.value?.appearance?.mode === "system" && systemDark.value),
);
const styles = computed(() => {
  const tokens = policy.value?.tokens;
  const palette = policy.value?.appearance?.[dark.value ? "dark" : "light"];
  const result: Record<string, string> = {
    colorScheme: dark.value ? "dark" : "light",
  };
  for (const [key, value] of Object.entries(palette ?? {})) {
    const variables: Record<string, string[]> = {
      canvas: ["--background"],
      surface: ["--card", "--popover"],
      foreground: ["--foreground", "--card-foreground", "--popover-foreground"],
      muted: ["--muted-foreground"],
      border: ["--border", "--input"],
      accent: ["--primary", "--ring"],
      accent_foreground: ["--primary-foreground"],
    };
    for (const variable of variables[key] ?? []) result[variable] = value;
    result[`--x-claim-${key.replaceAll("_", "-")}`] = value;
    if (key === "canvas") result["--x-experience-canvas"] = value;
  }
  if (tokens?.content_width)
    result["--flow-content-width"] = {
      narrow: "24rem",
      standard: "28rem",
      wide: "42rem",
    }[tokens.content_width];
  if (tokens?.spacing)
    result["--flow-spacing"] = {
      compact: "0.75rem",
      comfortable: "1rem",
      spacious: "1.5rem",
    }[tokens.spacing];
  if (tokens?.typography) {
    result["--flow-body-size"] = {
      small: "0.875rem",
      standard: "1rem",
      large: "1.125rem",
    }[tokens.typography];
    result["--flow-title-size"] = {
      small: "1.25rem",
      standard: "1.5rem",
      large: "1.75rem",
    }[tokens.typography];
  }
  if (tokens?.control_size)
    result["--flow-control-size"] =
      tokens.control_size === "large" ? "3.25rem" : "2.75rem";
  return result;
});
</script>

<template>
  <div
    v-if="policy"
    class="relative isolate min-h-svh bg-background text-foreground"
    :data-form-flow-profile="policy.profile ?? 'custom'"
    data-testid="form-flow-presentation"
    :style="styles"
    :data-flow-screen="policy.screen"
    :data-flow-density="policy.layout.density"
    :data-flow-capture="policy.layout.capture_surface"
    :data-flow-spacing="policy.tokens?.spacing"
    :data-flow-type="policy.tokens?.typography"
    :data-flow-controls="policy.tokens?.control_size"
    :class="policy.actions.placement === 'viewport_bottom' ? 'pb-28' : ''"
  >
    <FormFlowArtworkBackdrop
      v-if="artworkSource && policy.artwork?.background.enabled"
      :src="artworkSource"
      :settings="policy.artwork.background"
    />
    <header
      v-if="showHeader !== false"
      class="mx-auto flex w-full max-w-lg items-center gap-3 px-5 pb-1 pt-4"
      :class="
        policy.brand.placement === 'center' ? 'justify-center' : 'justify-start'
      "
      data-testid="form-flow-presentation-header"
    >
      <img
        v-if="policy.brand.logo"
        :src="policy.brand.logo"
        :alt="policy.brand.name ?? 'Logo'"
        class="w-auto shrink-0 object-contain"
        :style="{
          height: `${Math.min(policy.brand.logo_height, 96)}px`,
          maxWidth: `${policy.brand.logo_max_width}px`,
        }"
        data-testid="form-flow-presentation-logo"
      />
      <span
        v-if="policy.brand.name && !policy.brand.logo"
        class="text-sm font-semibold"
        >{{ policy.brand.name }}</span
      >
      <span
        v-if="
          policy.progress.show &&
          policy.progress.current &&
          policy.progress.total
        "
        class="ml-auto text-xs tabular-nums text-muted-foreground"
        aria-live="polite"
        data-testid="form-flow-presentation-progress"
        >Step {{ policy.progress.current }} of {{ policy.progress.total }}</span
      >
    </header>
    <slot />
  </div>
  <slot v-else />
</template>

<style scoped>
[data-testid="form-flow-presentation"] {
  overflow-wrap: anywhere;
}
header {
  max-width: var(--flow-content-width, 32rem);
}
:deep([data-flow-content]) {
  width: 100%;
  margin-inline: auto;
}
:deep([data-form-flow-screen] > div),
:deep([data-flow-content]),
:deep([data-flow-actions]) {
  max-width: var(--flow-content-width);
}
[data-flow-spacing] :deep([data-flow-panel]),
[data-flow-spacing] :deep([data-form-flow-screen] [data-slot="card-content"]),
[data-flow-spacing] :deep([data-testid="form-flow-field-group-content"]) {
  padding: min(var(--flow-spacing), 6vw, 24px);
}
[data-flow-spacing] :deep([data-flow-stack]),
[data-flow-spacing] :deep([data-flow-actions]) {
  gap: var(--flow-spacing);
}
[data-flow-type] :deep(p),
[data-flow-type] :deep(label),
[data-flow-type] :deep(input),
[data-flow-type] :deep(button),
[data-flow-type] :deep(select),
[data-flow-type] :deep(textarea) {
  font-size: var(--flow-body-size);
}
[data-flow-type] :deep(h1),
[data-flow-type] :deep(h2),
[data-flow-type] :deep(h3),
[data-flow-type] :deep([data-slot="card-title"]) {
  font-size: var(--flow-title-size);
  line-height: 1.35;
}
[data-flow-controls] :deep(button),
[data-flow-controls]
  :deep(input:not([type="hidden"]):not([type="checkbox"]):not([type="radio"])),
[data-flow-controls] :deep(select) {
  min-height: var(--flow-control-size);
  height: auto;
}
[data-flow-capture="contained"] :deep([data-flow-capture-surface]) {
  width: 100%;
  max-width: 24rem;
  max-height: 45svh;
  margin-inline: auto;
}
[data-flow-capture="fill_available"] :deep([data-flow-capture-surface]) {
  width: 100%;
  min-height: 12rem;
  max-height: 60svh;
  flex: 1 1 auto;
}
:deep([data-flow-capture-surface] video),
:deep([data-flow-capture-surface] img) {
  max-width: 100%;
  max-height: inherit;
  object-fit: contain;
}

[data-flow-spacing] :deep([data-flow-stack]) {
  display: flex;
  flex-direction: column;
  gap: var(--flow-spacing);
}
[data-flow-spacing] :deep([data-flow-stack] > :not([hidden]) ~ :not([hidden])) {
  margin-block: 0;
}
[data-testid="form-flow-presentation"]
  :deep(input:not([type="checkbox"]):not([type="radio"])),
[data-testid="form-flow-presentation"] :deep(select),
[data-testid="form-flow-presentation"] :deep(textarea) {
  background-color: var(--background);
  color: var(--foreground);
}

[data-flow-spacing] :deep([data-slot="card-header"]),
[data-flow-spacing] :deep([data-slot="card-content"]) {
  padding: min(var(--flow-spacing), 6vw, 24px);
}
:deep([data-flow-actions] button),
:deep([data-flow-actions] a) {
  min-width: 0;
  white-space: normal;
  overflow-wrap: anywhere;
  text-align: center;
  height: auto;
  min-height: var(--flow-control-size, 2.75rem);
  padding-block: 0.625rem;
}
</style>
