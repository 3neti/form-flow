<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from "vue";
import { router, Head } from "@inertiajs/vue3";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { marked } from "marked";
import DOMPurify from "dompurify";
import { initializeTheme } from "@/composables/useTheme";
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

interface PackageVersion {
  name: string;
  version: string;
}

interface Props {
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
  <Head :title="title || 'Welcome'" />

  <!-- ============================================================ -->
  <!-- Default Splash: modal-style launch screen                    -->
  <!-- ============================================================ -->
  <div
    v-if="is_default_splash"
    class="min-h-screen bg-gradient-to-b from-primary/5 via-background to-background px-5 py-8 select-none"
  >
    <Card class="mx-auto max-w-md border-0 bg-card/80 shadow-sm">
      <CardContent
        class="flex min-h-[620px] flex-col items-center justify-center px-6 py-8 text-center"
      >
        <!-- Hero logo -->
        <img
          v-if="app_logo"
          :src="app_logo"
          :alt="app_name ?? 'Logo'"
          class="form-flow-default-splash-logo mb-4 w-auto object-contain drop-shadow-lg animate-fade-in"
        />

        <p
          v-if="app_name"
          class="mb-6 text-base font-medium tracking-wide text-gray-400 animate-fade-in-delay dark:text-gray-500 sm:text-lg"
        >
          {{ app_name }}
        </p>

        <div v-if="voucher_code" class="mb-8 text-center animate-fade-in-delay">
          <p
            class="mb-2 text-[11px] uppercase tracking-[0.2em] text-gray-400 dark:text-gray-600"
          >
            Redeeming
          </p>

          <span
            class="inline-flex items-center gap-2 rounded-full border border-primary/20 bg-primary/5 px-5 py-1.5 font-mono text-lg font-semibold tracking-widest text-primary sm:text-xl"
          >
            <span class="text-primary/40" aria-hidden="true">||</span>
            {{ voucher_code }}
            <span class="text-primary/40" aria-hidden="true">||</span>
          </span>
        </div>

        <div class="w-full max-w-xs space-y-3">
          <div v-if="timeoutSeconds > 0" class="space-y-1">
            <div class="h-1 w-full overflow-hidden rounded-full bg-muted">
              <div
                class="h-full rounded-full bg-primary/50 transition-all duration-1000 ease-linear"
                :style="{ width: `${progressPercentage}%` }"
              />
            </div>

            <p class="text-center text-[11px] text-muted-foreground">
              {{ remainingSeconds }}s
            </p>
          </div>
        </div>

        <footer class="mt-6 space-y-0.5 text-center">
          <p
            v-if="app_author"
            class="text-[10px] text-gray-300 dark:text-gray-700"
          >
            {{ app_author }}
          </p>

          <p
            v-if="copyright_text"
            class="text-[10px] text-gray-300 dark:text-gray-700"
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
          :action-placement="action_placement || 'viewport_bottom'"
          :processing="submitting"
          :primary-disabled="submitting"
          :show-secondary="false"
          :primary-label="button_label"
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
    :class="
      isDisburseFlow
        ? 'min-h-screen flex items-center justify-center bg-gradient-to-b from-primary/5 via-background to-background p-4'
        : 'min-h-screen flex items-center justify-center bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-900 dark:to-gray-800 p-4'
    "
  >
    <Card
      :class="
        isDisburseFlow
          ? 'w-full max-w-2xl overflow-visible border-0 shadow-sm bg-card/80'
          : 'w-full max-w-2xl overflow-visible'
      "
    >
      <CardHeader v-if="title">
        <CardTitle class="text-center text-2xl">{{ title }}</CardTitle>
      </CardHeader>

      <CardContent class="space-y-6 overflow-visible">
        <!-- Rendered content -->
        <div
          v-if="contentType !== 'text'"
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
          :action-placement="action_placement || 'viewport_bottom'"
          :processing="submitting"
          :primary-disabled="submitting"
          :show-secondary="false"
          :primary-label="button_label"
          primary-type="button"
          :variant="isDisburseFlow ? 'immersive' : 'default'"
          @primary="handleContinue"
        />
      </CardContent>
    </Card>
  </div>
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
  max-height: 6rem;
  object-fit: contain;
}

@media (min-width: 640px) {
  .form-flow-default-splash-logo {
    max-width: 16rem;
    max-height: 7rem;
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
