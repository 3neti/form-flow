<script setup lang="ts">
import FormFlowPresentation from "./components/FormFlowPresentation.vue";
import type { FormFlowPresentation as PresentationPolicy } from "./components/formFlowPresentation";
import { ref, computed, onMounted, onUnmounted } from "vue";
import { router, Head } from "@inertiajs/vue3";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { marked } from "marked";
import DOMPurify from "dompurify";
import { initializeTheme } from "@/composables/useTheme";
import FormFlowArtworkSurface from "./components/FormFlowArtworkSurface.vue";
import FormFlowHtmlContent from "./components/FormFlowHtmlContent.vue";
import FormFlowActions from "./components/FormFlowActions.vue";
import FormFlowVersionStrip from "./components/FormFlowVersionStrip.vue";

initializeTheme();

interface ClaimExperienceDiagnostics {
  splash_owner?: unknown;
  form_flow_splash_policy?: unknown;
}

interface ClaimExperienceOptions {
  skip_consumed_splash?: unknown;
}

interface ClaimExperienceEntry {
  mode?: unknown;
}

interface ClaimExperience {
  entry?: ClaimExperienceEntry;
  options?: ClaimExperienceOptions;
  diagnostics?: ClaimExperienceDiagnostics;
}

interface ClaimPresentation {
  title?: string;
  primary_action_label?: string;
  eyebrow?: string;
  subject_label?: string;
  intent?: string;
  source?: string;
}

interface PackageVersion {
  name: string;
  version: string;
}

interface Props {
  presentation?: PresentationPolicy | null;
  flow_id: string;
  step_index: number;
  title?: string;
  content: string;
  timeout?: number;
  button_label?: string;
  is_default_splash?: boolean;
  voucher_code?: string;
  app_name?: string;
  app_logo?: string;
  app_author?: string;
  copyright_text?: string;
  package_versions?: PackageVersion[] | Record<string, string> | null;
  show_package_versions?: boolean;
  action_placement?: "inline" | "bottom" | "bottom_sticky" | "viewport_bottom" | string | null;
  claim_experience?: ClaimExperience | null;
  claim_presentation?: ClaimPresentation | null;
  preview_mode?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  title: undefined,
  timeout: 5,
  button_label: "Continue Now",
  is_default_splash: false,
  voucher_code: undefined,
  app_name: undefined,
  app_logo: undefined,
  app_author: undefined,
  copyright_text: undefined,
  package_versions: undefined,
  show_package_versions: false,
  action_placement: undefined,
  claim_experience: undefined,
  claim_presentation: undefined,
  preview_mode: false,
});

// Coerce timeout to number (env values may arrive as strings)
const timeoutSeconds = computed(() => {
  const val = Number(props.timeout);
  return isNaN(val) ? 5 : val;
});

const remainingSeconds = ref(timeoutSeconds.value);
const submitting = ref(false);
let intervalId: ReturnType<typeof setInterval> | null = null;

// Detect disburse flow from voucher_code presence
const isDisburseFlow = computed(() => !!props.voucher_code);

const claimPresentationTitle = computed(() => {
  const title = props.claim_presentation?.title?.trim();

  return title && title.length > 0 ? title : undefined;
});

const pageTitle = computed(() => claimPresentationTitle.value ?? props.title ?? "Welcome");

const primaryActionLabel = computed(() => {
  const label = props.claim_presentation?.primary_action_label?.trim();

  return label && label.length > 0 ? label : props.button_label;
});

const defaultSplashHeading = computed(() => {
  return claimPresentationTitle.value ?? props.app_name;
});

const voucherCodeEyebrow = computed(() => {
  const eyebrow =
    props.claim_presentation?.eyebrow?.trim() ??
    props.claim_presentation?.subject_label?.trim();

  return eyebrow && eyebrow.length > 0
    ? eyebrow
    : isDisburseFlow.value
      ? "Pay Code"
      : "Code";
});

const splashActionPlacement = computed(() => props.action_placement || "bottom");

const voucherCodeDisplayStyle = computed(() => {
  const length = String(props.voucher_code ?? "").length;
  let fontSize = "5.25rem";

  if (length > 4 && length <= 8) {
    fontSize = "4.5rem";
  }

  if (length > 8 && length <= 14) {
    fontSize = "3.5rem";
  }

  if (length > 14 && length <= 22) {
    fontSize = "2.5rem";
  }

  if (length > 22) {
    fontSize = "1.875rem";
  }

  return {
    fontSize: `min(${fontSize}, ${130 / (length + 6)}cqi)`,
    letterSpacing: "0.08em",
  };
});

// Detect content type and render appropriately
const contentType = computed(() => {
  const trimmed = props.content.trim();

  // SVG detection
  if (trimmed.startsWith("<svg")) {
    return "svg";
  }

  // HTML detection
  if (/<[a-z][\s\S]*>/i.test(trimmed)) {
    return "html";
  }

  // URL detection
  if (trimmed.match(/^https?:\/\//i)) {
    return "url";
  }

  // Markdown detection (has # headers or ** bold or * list)
  if (trimmed.match(/^#+\s|^\*\*|\*\s/m)) {
    return "markdown";
  }

  // Fallback to plain text
  return "text";
});

const splashArtworkSource = computed(() => {
  if (typeof document === "undefined") return null;
  const template = document.createElement("template");
  template.innerHTML = DOMPurify.sanitize(props.content);
  return template.content.querySelector("img")?.getAttribute("src") ?? null;
});

const isArtworkSplash = computed(() =>
  (contentType.value === "html" || contentType.value === "svg")
  && /<(?:img|svg)\b/i.test(props.content)
  && (!!props.presentation?.artwork || (!!props.presentation?.layout.artwork_aspect_ratio && props.presentation.layout.artwork_aspect_ratio !== "natural")),
);

const renderedContent = computed(() => {
  switch (contentType.value) {
    case "markdown":
      // Parse markdown to HTML and sanitize
      return DOMPurify.sanitize(marked.parse(props.content) as string);

    case "html":
    case "svg":
      // Sanitize HTML/SVG
      return DOMPurify.sanitize(props.content);

    case "url":
      // Embed as iframe
      return `<iframe src="${props.content}" class="w-full h-96 border-0" />`;

    case "text":
    default:
      // Plain text with preserved line breaks
      return props.content.replace(/\n/g, "<br>");
  }
});

// Progress percentage (0-100)
const progressPercentage = computed(() => {
  if (timeoutSeconds.value === 0) return 0;
  return (
    ((timeoutSeconds.value - remainingSeconds.value) / timeoutSeconds.value) *
    100
  );
});

// Start countdown
onMounted(() => {
  if (props.preview_mode) return;
  if (timeoutSeconds.value > 0) {
    intervalId = setInterval(() => {
      remainingSeconds.value -= 1;

      if (remainingSeconds.value <= 0) {
        // Auto-submit when countdown reaches 0
        handleContinue();
      }
    }, 1000);
  }
});

// Cleanup interval on unmount
onUnmounted(() => {
  if (intervalId) {
    clearInterval(intervalId);
  }
});

// Submit to next step
async function handleContinue() {
  if (props.preview_mode) return;
  if (submitting.value) return;

  submitting.value = true;

  // Clear countdown
  if (intervalId) {
    clearInterval(intervalId);
    intervalId = null;
  }

  router.post(
    `/form-flow/${props.flow_id}/step/${props.step_index}`,
    {
      data: { confirmed: true },
    },
    {
      preserveState: false,
      preserveScroll: false,
      onFinish: () => {
        submitting.value = false;
      },
    },
  );
}

if (import.meta.env.DEV && props.claim_experience) {
  console.debug("[form-flow] claim experience", {
    mode: props.claim_experience?.entry?.mode,
    skip_consumed_splash: props.claim_experience?.options?.skip_consumed_splash,
    splash_owner: props.claim_experience?.diagnostics?.splash_owner,
    form_flow_splash_policy:
      props.claim_experience?.diagnostics?.form_flow_splash_policy,
  });
}
</script>

<template>
  <FormFlowPresentation :presentation="presentation">
  <Head :title="pageTitle" />

  <!-- ============================================================ -->
  <!-- Default Splash: modal-style launch screen                    -->
  <!-- ============================================================ -->
  <div
    v-if="is_default_splash"
    class="x-claim-canvas min-h-screen bg-gradient-to-b from-primary/5 via-background to-background px-5 py-8 select-none"
  >
    <Card class="mx-auto w-full max-w-4xl border-0 bg-card/90 shadow-sm">
      <CardContent
        class="flex min-h-[620px] flex-col items-center justify-center px-4 py-8 text-center sm:min-h-[680px] sm:px-12"
      >
        <!-- Hero logo -->
        <img
          v-if="!presentation && app_logo"
          :src="app_logo"
          :alt="app_name ?? 'Logo'"
          class="form-flow-default-splash-logo mb-4 w-auto object-contain drop-shadow-lg animate-fade-in"
        />

        <p
          v-if="defaultSplashHeading"
          class="mb-7 text-base font-semibold tracking-wide text-foreground/80 animate-fade-in-delay sm:text-lg"
        >
          {{ defaultSplashHeading }}
        </p>

        <div
          v-if="voucher_code"
          class="mb-10 w-full text-center animate-fade-in-delay"
        >
          <p
            class="mb-4 text-[11px] font-semibold uppercase tracking-[0.22em] text-foreground/80"
          >
            {{ voucherCodeEyebrow }}
          </p>

          <div
            data-testid="form-flow-splash-pay-code"
            class="@container flex w-full items-center justify-center"
          >
            <span
              :style="voucherCodeDisplayStyle"
              class="max-w-full whitespace-nowrap font-mono font-black uppercase leading-none text-primary"
            >{{ `|| ${voucher_code} ||` }}</span>
          </div>
        </div>

        <div class="w-full max-w-xs space-y-3">
          <div v-if="timeoutSeconds > 0" class="space-y-1">
            <div class="h-1 w-full overflow-hidden rounded-full bg-muted">
              <div
                class="h-full rounded-full bg-primary/50 transition-all duration-1000 ease-linear"
                :style="{ width: `${progressPercentage}%` }"
              />
            </div>

            <p class="text-center text-[11px] font-medium text-foreground/75">
              {{ remainingSeconds }}s
            </p>
          </div>
        </div>

        <footer class="mt-6 space-y-0.5 text-center">
          <p
            v-if="app_author"
            class="text-[10px] font-medium text-foreground/65"
          >
            {{ app_author }}
          </p>

          <p
            v-if="copyright_text"
            class="text-[10px] font-medium text-foreground/65"
          >
            {{ copyright_text }}
          </p>

          <FormFlowVersionStrip
            :show="show_package_versions"
            :package-versions="package_versions"
            context="splash"
          />
        </footer>

        <FormFlowActions
          :action-placement="splashActionPlacement"
          :processing="submitting"
          :primary-disabled="submitting"
          :show-secondary="false"
          :primary-label="primaryActionLabel"
          primary-type="button"
          variant="immersive"
          @primary="handleContinue"
        />
      </CardContent>
    </Card>
  </div>

  <!-- ============================================================ -->
  <!-- Custom Splash: Card-based layout for rider->splash content   -->
  <!-- ============================================================ -->
  <div
    v-else
    class="min-w-0"
    :style="isArtworkSplash && presentation ? { minHeight: 'calc(100svh - 11rem)', padding: '0' } : undefined"
    :class="[isArtworkSplash ? 'flex flex-col items-center' : 'x-claim-canvas',

      isArtworkSplash ? '' : isDisburseFlow
        ? 'min-h-screen flex items-center justify-center bg-gradient-to-b from-primary/5 via-background to-background p-4'
        : 'min-h-screen flex items-center justify-center bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-900 dark:to-gray-800 p-4'
    ]"
  >
    <FormFlowArtworkSurface v-if="isArtworkSplash" :artwork-src="splashArtworkSource" class="self-stretch">
      <h2 v-if="title" class="text-center text-2xl font-semibold">{{ title }}</h2>
      <Card class="w-full max-w-2xl min-w-0 gap-0 border-0 py-0 shadow-sm" data-testid="form-flow-splash-artwork-card">
        <CardContent class="min-w-0 p-0">
          <FormFlowHtmlContent :html="renderedContent" />
        </CardContent>
      </Card>
      <template #footer>
      <p v-if="timeoutSeconds > 0" class="text-center text-xs text-muted-foreground" role="status">{{ remainingSeconds }}s</p>
      <FormFlowVersionStrip :show="show_package_versions" :package-versions="package_versions" context="splash" />
      <FormFlowActions
        :action-placement="splashActionPlacement"
        :processing="submitting"
        :primary-disabled="submitting"
        :show-secondary="false"
        :primary-label="primaryActionLabel"
        primary-type="button"
        :variant="isDisburseFlow ? 'immersive' : 'default'"
        @primary="handleContinue"
      />
      </template>
    </FormFlowArtworkSurface>
    <Card
      v-else
      :class="
        isDisburseFlow
          ? 'min-w-0 w-full max-w-2xl overflow-visible border-0 shadow-sm bg-card/80'
          : 'min-w-0 w-full max-w-2xl overflow-visible'
      "
    >
      <CardHeader v-if="title">
        <CardTitle class="text-center text-2xl">{{ title }}</CardTitle>
      </CardHeader>

      <CardContent class="min-w-0 space-y-6 overflow-visible">
        <!-- Rendered content -->
        <FormFlowHtmlContent
          v-if="contentType === 'html' || contentType === 'svg'"
          :html="renderedContent"
        />
        <div
          v-else-if="contentType !== 'text'"
          v-html="renderedContent"
          class="prose prose-base max-w-none dark:prose-invert text-center overflow-visible"
        />
        <div
          v-else
          class="text-center text-lg text-gray-700 dark:text-gray-300 whitespace-pre-wrap"
        >
          {{ content }}
        </div>

        <!-- Countdown progress -->
        <div v-if="timeoutSeconds > 0" class="space-y-2">
          <div
            :class="
              isDisburseFlow
                ? 'w-full bg-muted rounded-full h-1 overflow-hidden'
                : 'w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 overflow-hidden'
            "
          >
            <div
              :class="
                isDisburseFlow
                  ? 'h-full rounded-full bg-primary/50 transition-all duration-1000 ease-linear'
                  : 'bg-primary h-full transition-all duration-1000 ease-linear'
              "
              :style="{ width: `${progressPercentage}%` }"
            />
          </div>
          <p
            :class="
              isDisburseFlow
                ? 'text-center text-[11px] text-muted-foreground'
                : 'text-center text-sm text-gray-500 dark:text-gray-400'
            "
          >
            <template v-if="isDisburseFlow">{{ remainingSeconds }}s</template>
            <template v-else
              >Continuing in {{ remainingSeconds }} second{{
                remainingSeconds !== 1 ? "s" : ""
              }}…</template
            >
          </p>
        </div>

        <!-- Continue button -->
        <FormFlowVersionStrip
          :show="show_package_versions"
          :package-versions="package_versions"
          context="splash"
        />

        <FormFlowActions
          :action-placement="splashActionPlacement"
          :processing="submitting"
          :primary-disabled="submitting"
          :show-secondary="false"
          :primary-label="primaryActionLabel"
          primary-type="button"
          :variant="isDisburseFlow ? 'immersive' : 'default'"
          @primary="handleContinue"
        />
      </CardContent>
    </Card>
  </div>

  </FormFlowPresentation>
</template>

<style scoped>
/* Default splash entrance animations */
@keyframes fade-in {
  from {
    opacity: 0;
    transform: translateY(12px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.animate-fade-in {
  animation: fade-in 0.6s ease-out both;
}

.animate-fade-in-delay {
  animation: fade-in 0.6s ease-out 0.15s both;
}

.form-flow-default-splash-logo {
  display: block;
  width: auto;
  height: auto;
  max-width: min(14rem, 70vw);
  max-height: 4.5rem;
  object-fit: contain;
}

@media (min-width: 640px) {
  .form-flow-default-splash-logo {
    max-width: 14rem;
    max-height: 4.5rem;
  }
}


/* Prose overrides for custom splash content */
.prose :deep(img) {
  max-width: 100% !important;
  height: auto !important;
  display: block;
}

.prose {
  overflow: visible !important;
}

.prose :deep(*) {
  max-width: 100%;
}

:deep(.card) {
  overflow: visible !important;
}

:deep([class*="card"]) {
  overflow: visible !important;
}
</style>
