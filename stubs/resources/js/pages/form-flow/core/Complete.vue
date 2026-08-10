<script setup lang="ts">
import { Head, router } from "@inertiajs/vue3";
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Alert, AlertDescription } from "@/components/ui/alert";
import { Spinner } from "@/components/ui/spinner";
import { Separator } from "@/components/ui/separator";
import { AlertCircle, CheckCircle2, Clock, Landmark, Route, WalletCards } from "lucide-vue-next";
import type { Component } from "vue";
import { computed, ref, onUnmounted } from "vue";
import { useFormFlowSummary } from "@/composables/useFormFlowSummary";
import { initializeTheme } from "@/composables/useTheme";
import {
  destinationInstitution,
  iconAssetForRail,
} from "@/components/x-change/support/payoutDestinations";
import PayoutDestinationIcon from "@/components/x-change/PayoutDestinationIcon.vue";
import FormFlowVersionStrip from "./components/FormFlowVersionStrip.vue";
import FormFlowActions from "./components/FormFlowActions.vue";

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
  state: {
    reference_id: string;
    collected_data: any[];
    completed_at: string;
  };
  callback_triggered: boolean;
  claim_experience?: ClaimExperience | null;
  claim_workflow?: {
    confirmation_label?: string;
  } | null;
  package_versions?:
    | PackageVersion[]
    | Record<string, string>
    | null;
  show_package_versions?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  package_versions: undefined,
  show_package_versions: false,
});

const confirmationLabel = computed(
  () => props.claim_workflow?.confirmation_label || "Confirm Redemption",
);

// Processing state
const isProcessing = ref(false);
const showError = ref(false);
const errorMessage = ref("");
const elapsedTime = ref(0);
const expectedDuration = 15;
let timerInterval: number | null = null;
let redirectTimeout: number | null = null;

// Detect if this is a disburse flow
const isClaimFlow = computed(() =>
  props.state.reference_id.startsWith("claim-"),
);

const isDisburseFlow = computed(() =>
  props.state.reference_id.startsWith("disburse-"),
);

const isRedemptionFlow = computed(
  () => isClaimFlow.value || isDisburseFlow.value,
);

// Extract voucher code from reference_id (format: disburse-{CODE}-{timestamp})
const voucherCode = computed(() => {
  if (!isRedemptionFlow.value) return null;

  const parts = props.state.reference_id.split("-");

  return parts.slice(1, -1).join("-");
});

// Progress percentage (0-100)
const progress = computed(() => {
  return Math.min((elapsedTime.value / expectedDuration) * 100, 100);
});

// Format elapsed time as MM:SS
const formattedTime = computed(() => {
  const minutes = Math.floor(elapsedTime.value / 60);
  const seconds = elapsedTime.value % 60;
  return `${minutes}:${seconds.toString().padStart(2, "0")}`;
});

// Status message based on elapsed time
const statusMessage = computed(() => {
  if (elapsedTime.value < 5) {
    return "Connecting to payment gateway...";
  } else if (elapsedTime.value < 10) {
    return "Processing disbursement...";
  } else if (elapsedTime.value < expectedDuration) {
    return "Waiting for bank confirmation...";
  } else {
    return "This is taking longer than expected...";
  }
});

function startTimer() {
  elapsedTime.value = 0;
  timerInterval = window.setInterval(() => {
    elapsedTime.value++;
  }, 1000);
}

function stopTimer() {
  if (timerInterval) {
    clearInterval(timerInterval);
    timerInterval = null;
  }
}

function handleClose() {
  if (isRedemptionFlow.value && voucherCode.value) {
    isProcessing.value = true;
    showError.value = false;
    errorMessage.value = "";
    startTimer();

    const submitUrl = isClaimFlow.value
      ? `/x/claim/${voucherCode.value}/submit`
      : `/disburse/${voucherCode.value}/redeem`;

    router.post(
      submitUrl,
      {
        flow_id: props.flow_id,
        reference_id: props.state.reference_id,
      },
      {
        onError: (errors) => {
          stopTimer();
          showError.value = true;
          errorMessage.value =
            errors.code ||
            errors.error ||
            "An error occurred during processing";

          redirectTimeout = window.setTimeout(() => {
            isProcessing.value = false;
          }, 3000);
        },
        onSuccess: () => {
          stopTimer();
          isProcessing.value = false;
        },
      },
    );

    return;
  }

  window.location.href = "/form-flow-demo.html";
}

// Cleanup on unmount
onUnmounted(() => {
  stopTimer();
  if (redirectTimeout) {
    clearTimeout(redirectTimeout);
  }
});

// Data summary transformation
const { flattenCollectedData, formatFieldValue, groupDataBySection } =
  useFormFlowSummary();

const flatData = computed(() =>
  flattenCollectedData(props.state.collected_data),
);
const dataSections = computed(() => groupDataBySection(flatData.value));

// The redemption confirmation summary replaces the "Redemption Details"
// section from useFormFlowSummary with a fixed-order, icon-annotated view;
// the remaining supplemental sections (Personal Information, Location
// Verification, Identity Verification) still come straight from it.
const supplementalSections = computed(() =>
  dataSections.value.filter((section) => section.title !== "Redemption Details"),
);

interface RedemptionSummaryFieldIcon {
  iconAsset: string | null;
  fallbackIcon: Component;
}

interface RedemptionSummaryField {
  key: string;
  label: string;
  value: string;
  tabular?: boolean;
  icon?: RedemptionSummaryFieldIcon;
}

function hasCollectedValue(
  data: Record<string, any>,
  key: string,
): boolean {
  return (
    key in data &&
    data[key] !== null &&
    data[key] !== undefined &&
    String(data[key]).trim() !== ""
  );
}

// Fixed canonical order: Mobile Number, Bank/Wallet Provider, Account
// Number, Amount, Payment Method. Only fields actually present in the
// collected data are rendered -- specialized workflows (e.g. officer
// authorization) that never collect a payout destination simply produce
// an empty list here, never placeholder rows.
const redemptionSummaryFields = computed<RedemptionSummaryField[]>(() => {
  const data = flatData.value;
  const fields: RedemptionSummaryField[] = [];

  if (hasCollectedValue(data, "mobile")) {
    fields.push({
      key: "mobile",
      label: "Mobile Number",
      value: formatFieldValue("mobile", data.mobile),
      tabular: true,
    });
  }

  const bankCodeKey = hasCollectedValue(data, "bank_code")
    ? "bank_code"
    : hasCollectedValue(data, "bank_account")
      ? "bank_account"
      : null;

  if (bankCodeKey) {
    const institution = destinationInstitution(data[bankCodeKey]);

    fields.push({
      key: "bank_code",
      label: "Bank/Wallet Provider",
      value: institution.shortLabel,
      icon: {
        iconAsset: institution.iconAsset,
        fallbackIcon: institution.category === "wallet" ? WalletCards : Landmark,
      },
    });
  }

  if (hasCollectedValue(data, "account_number")) {
    fields.push({
      key: "account_number",
      label: "Account Number",
      // Shown in full -- never truncated -- so the claimant can verify it.
      value: String(data.account_number).trim(),
      tabular: true,
    });
  }

  if (hasCollectedValue(data, "amount")) {
    fields.push({
      key: "amount",
      label: "Amount",
      value: formatFieldValue("amount", data.amount),
      tabular: true,
    });
  }

  if (hasCollectedValue(data, "settlement_rail")) {
    fields.push({
      key: "settlement_rail",
      label: "Payment Method",
      value: formatFieldValue("settlement_rail", data.settlement_rail),
      icon: {
        iconAsset: iconAssetForRail(data.settlement_rail),
        fallbackIcon: Route,
      },
    });
  }

  return fields;
});

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
  <Head title="Flow Complete" />

  <!-- ============================================================ -->
  <!-- Disburse Flow: clean, branded layout                         -->
  <!-- ============================================================ -->
  <template v-if="isRedemptionFlow">
    <!-- Processing State -->
    <div
      v-if="isProcessing"
      class="min-h-screen flex items-center justify-center bg-gradient-to-b from-primary/5 via-background to-background px-6"
    >
      <!-- Error -->
      <div v-if="showError" class="w-full max-w-sm text-center space-y-6">
        <AlertCircle class="h-12 w-12 text-destructive mx-auto" />
        <p class="text-lg font-medium">Processing Failed</p>
        <Alert variant="destructive">
          <AlertCircle class="h-4 w-4" />
          <AlertDescription>{{ errorMessage }}</AlertDescription>
        </Alert>
        <p class="text-xs text-muted-foreground">Redirecting in 3 seconds…</p>
      </div>

      <!-- Active Processing -->
      <div v-else class="w-full max-w-sm text-center space-y-6">
        <div class="relative mx-auto w-fit">
          <Spinner class="h-14 w-14 text-primary" />
          <Clock
            class="h-5 w-5 text-muted-foreground absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2"
          />
        </div>
        <p class="text-lg font-medium">Processing Redemption</p>
        <p class="text-sm text-muted-foreground">{{ statusMessage }}</p>
        <div class="space-y-1">
          <div class="h-1 bg-muted rounded-full overflow-hidden">
            <div
              class="h-full rounded-full bg-primary/50 transition-all duration-1000 ease-linear"
              :style="{ width: `${progress}%` }"
            />
          </div>
          <p class="text-[11px] text-muted-foreground font-mono">
            {{ formattedTime }}
          </p>
        </div>
        <p
          v-if="elapsedTime >= expectedDuration"
          class="text-xs text-muted-foreground"
        >
          The bank is taking longer than usual. Please keep waiting.
        </p>
        <FormFlowVersionStrip
          :show="props.show_package_versions"
          :package-versions="props.package_versions"
          context="complete"
        />
      </div>
    </div>

    <!-- Completed / Review State -->
    <div
      v-else
      class="min-h-screen bg-gradient-to-b from-primary/5 via-background to-background px-5 py-8"
    >
      <Card class="mx-auto max-w-md border-0 bg-card/80 shadow-sm">
        <CardContent class="flex flex-col gap-6 px-6 py-8">
          <!-- Compact confirmation header: workflow-provided label + Pay Code -->
          <div class="flex items-center justify-between gap-3">
            <h2 class="text-base font-semibold text-foreground">
              {{ confirmationLabel }}
            </h2>
            <span
              v-if="voucherCode"
              class="shrink-0 rounded-full border border-primary/20 bg-primary/5 px-3 py-0.5 font-mono text-xs font-semibold tracking-wide text-primary tabular-nums"
            >
              {{ voucherCode }}
            </span>
          </div>

          <!-- Redemption summary: fixed-order payout facts for final review -->
          <dl
            v-if="redemptionSummaryFields.length > 0"
            data-testid="redemption-summary"
            class="divide-y divide-border/60 text-sm"
          >
            <div
              v-for="field in redemptionSummaryFields"
              :key="field.key"
              class="grid grid-cols-2 items-start gap-x-4 gap-y-1 py-2 first:pt-0 last:pb-0"
            >
              <dt class="min-w-0 text-muted-foreground">{{ field.label }}</dt>
              <dd
                class="flex min-w-0 flex-wrap items-center justify-end gap-1.5 font-medium text-foreground"
                :class="{ 'tabular-nums': field.tabular }"
              >
                <span class="min-w-0 text-right break-words">{{ field.value }}</span>
                <PayoutDestinationIcon
                  v-if="field.icon"
                  :icon-asset="field.icon.iconAsset"
                  :fallback-icon="field.icon.fallbackIcon"
                  alt=""
                  aria-hidden="true"
                  size-class="h-3.5 w-3.5"
                />
              </dd>
            </div>
          </dl>

          <!-- Supplemental sections (Personal Information, Location Verification, Identity Verification) -->
          <div v-if="supplementalSections.length > 0" class="flex flex-col gap-5">
            <div v-for="section in supplementalSections" :key="section.title">
              <p
                class="text-[11px] uppercase tracking-[0.15em] text-muted-foreground mb-2"
              >
                {{ section.title }}
              </p>
              <div class="space-y-1.5">
                <div
                  v-for="field in section.fields"
                  :key="field.key"
                  class="flex justify-between items-baseline gap-4"
                >
                  <span class="text-sm text-muted-foreground shrink-0">{{
                    field.label
                  }}</span>
                  <span
                    class="text-sm font-medium text-foreground text-right truncate"
                    >{{ field.value }}</span
                  >
                </div>
              </div>
            </div>
          </div>

          <!-- Reference ID: subtle footer, stays with the card's content -->
          <p
            class="text-center text-[10px] text-gray-300 dark:text-gray-700 font-mono"
          >
            {{ state.reference_id }}
          </p>
        </CardContent>
      </Card>

      <!--
        Rendered outside Card/CardContent on purpose: in "viewport_bottom"
        mode FormFlowActions renders its own invisible height spacer (to
        reserve room below the visually fixed button bar) as a sibling of
        the buttons. Keeping that spacer inside CardContent's flex column
        showed up as dead space inside the card body; the primary action
        itself is unaffected since `fixed` positioning is relative to the
        viewport, not to Card.
      -->
      <FormFlowActions
        action-placement="viewport_bottom"
        :primary-disabled="isProcessing"
        :processing="isProcessing"
        :show-secondary="false"
        :primary-label="confirmationLabel"
        primary-type="button"
        variant="immersive"
        @primary="handleClose"
      />

      <FormFlowVersionStrip
        :show="props.show_package_versions"
        :package-versions="props.package_versions"
        context="complete"
      />
    </div>
  </template>

  <!-- ============================================================ -->
  <!-- Non-disburse: existing Card layout                           -->
  <!-- ============================================================ -->
  <div v-else class="container mx-auto max-w-2xl px-4 py-8">
    <Card>
      <!-- Processing State -->
      <template v-if="isProcessing">
        <template v-if="showError">
          <CardHeader class="text-center">
            <div class="flex justify-center mb-4">
              <AlertCircle class="h-16 w-16 text-destructive" />
            </div>
            <CardTitle class="text-2xl">Processing Failed</CardTitle>
            <CardDescription>Redirecting back in 3 seconds…</CardDescription>
          </CardHeader>
          <CardContent class="space-y-6">
            <Alert variant="destructive">
              <AlertCircle class="h-4 w-4" />
              <AlertDescription>{{ errorMessage }}</AlertDescription>
            </Alert>
            <div class="text-center text-sm text-muted-foreground">
              Processing took {{ formattedTime }}
            </div>
          </CardContent>
        </template>

        <template v-else>
          <CardHeader class="text-center">
            <div class="flex justify-center mb-4">
              <div class="relative">
                <Spinner class="h-16 w-16 text-primary" />
                <Clock
                  class="h-6 w-6 text-muted-foreground absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2"
                />
              </div>
            </div>
            <CardTitle class="text-2xl">Processing Redemption</CardTitle>
            <CardDescription
              >Please wait while we process your voucher…</CardDescription
            >
          </CardHeader>
          <CardContent class="space-y-6">
            <Alert>
              <AlertDescription class="text-center">{{
                statusMessage
              }}</AlertDescription>
            </Alert>
            <div class="space-y-2">
              <div class="flex justify-between text-sm text-muted-foreground">
                <span>Elapsed time</span>
                <span class="font-mono">{{ formattedTime }}</span>
              </div>
              <div class="h-2 bg-muted rounded-full overflow-hidden">
                <div
                  class="h-full bg-primary transition-all duration-1000 ease-linear"
                  :style="{ width: `${progress}%` }"
                />
              </div>
            </div>
            <Alert v-if="elapsedTime >= expectedDuration" variant="default">
              <AlertCircle class="h-4 w-4" />
              <AlertDescription>
                The bank is taking longer than usual to respond. Please continue
                waiting or contact support if this persists.
              </AlertDescription>
            </Alert>
          </CardContent>
        </template>
      </template>

      <!-- Completed State -->
      <template v-else>
        <CardHeader class="text-center">
          <div class="flex justify-center mb-4">
            <CheckCircle2 class="h-16 w-16 text-green-500" />
          </div>
          <CardTitle class="text-2xl">Flow Completed</CardTitle>
          <CardDescription
            >Your submission has been received successfully.</CardDescription
          >
        </CardHeader>
        <CardContent class="space-y-6">
          <div class="space-y-4">
            <div
              v-for="section in dataSections"
              :key="section.title"
              class="border rounded-lg p-4"
            >
              <div class="flex items-center gap-2 mb-3">
                <component
                  :is="section.icon"
                  class="h-5 w-5 text-muted-foreground"
                />
                <h4 class="font-medium">{{ section.title }}</h4>
              </div>
              <Separator class="mb-3" />
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div
                  v-for="field in section.fields"
                  :key="field.key"
                  class="space-y-1"
                >
                  <p class="text-sm text-muted-foreground">{{ field.label }}</p>
                  <p class="text-base font-medium">{{ field.value }}</p>
                </div>
              </div>
            </div>

            <details class="mt-4">
              <summary
                class="text-xs text-muted-foreground cursor-pointer hover:text-foreground"
              >
                Raw Data
              </summary>
              <pre class="text-xs mt-2 p-2 bg-muted rounded overflow-auto">{{
                JSON.stringify(state.collected_data, null, 2)
              }}</pre>
            </details>
          </div>

          <div class="text-center">
            <p class="text-[10px] text-muted-foreground font-mono">
              {{ state.reference_id }}
            </p>
          </div>

          <div class="flex justify-center pt-4">
            <Button @click="handleClose" :disabled="isProcessing">
              Back to Demo
            </Button>
          </div>
          <FormFlowVersionStrip
            :show="props.show_package_versions"
            :package-versions="props.package_versions"
            context="complete"
          />
        </CardContent>
      </template>
    </Card>
  </div>
</template>
