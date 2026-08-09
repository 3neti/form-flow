<script setup lang="ts">
import { ref, computed, watch, onMounted, nextTick } from "vue";
import { router, Head } from "@inertiajs/vue3";
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { Checkbox } from "@/components/ui/checkbox";
import { Alert, AlertDescription } from "@/components/ui/alert";
import { Badge } from "@/components/ui/badge";
import { Separator } from "@/components/ui/separator";
import { AlertCircle } from "lucide-vue-next";
import {
  CountrySelect,
  SettlementRailSelect,
  BankEMISelect,
} from "@/components/financial";
import PhoneInput from "@/components/ui/phone-input/PhoneInput.vue";
import NumberInputWithKeypad from "@/components/NumberInputWithKeypad.vue";
import { initializeTheme } from "@/composables/useTheme";
import {
  claimWorkflowConfirmationLabel,
  claimWorkflowReviewItems,
  claimWorkflowSummaryText,
  normalizeClaimWorkflow,
} from "@/components/x-change/formFlowClaimWorkflow";
import FormFlowActions from "./components/FormFlowActions.vue";
import FormFlowVersionStrip from "./components/FormFlowVersionStrip.vue";
import { normalizeFormFlowUiVariant } from "./components/formFlowUiVariant";
import type { FormFlowUiVariant } from "./components/formFlowUiVariant";

initializeTheme();

interface FieldDefinition {
  name: string;
  type:
    | "text"
    | "email"
    | "date"
    | "number"
    | "textarea"
    | "select"
    | "checkbox"
    | "file"
    | "recipient_country"
    | "settlement_rail"
    | "bank_account"
    | "tel"
    | "hidden";
  label?: string;
  placeholder?: string;
  required?: boolean;
  options?: string[];
  validation?: string[];
  default?: any;
  min?: number;
  max?: number;
  step?: number;
  readonly?: boolean;
  disabled?: boolean;
  // UI Enhancement: Optional field metadata for visual hierarchy
  emphasis?: "hero" | "normal";
  group?: string;
  help_text?: string;
  variant?: "readonly-badge" | "normal";
  persist?: boolean;
  institution_options?: Array<{
    key: string;
    value: string;
    name: string;
    short_name: string;
    category: string;
    account_label: string;
    identifier_scheme: string;
    aliases: string[];
    commonly_used: boolean;
  }>;
  // Slice / divisible voucher metadata (from YAML driver context)
  slice_mode?: string | null;
  min_withdrawal?: number | string | null;
  available_balance?: number | string | null;
  max_slices?: number | string | null;
}

interface AutoSyncConfig {
  enabled: boolean;
  source_field: string;
  target_field: string;
  condition_field: string;
  condition_values: string[];
  debounce_ms?: number;
}

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

interface Props {
  flow_id: string;
  step_index: number;
  step_name?: string;
  title?: string;
  description?: string;
  fields: FieldDefinition[];
  auto_sync?: AutoSyncConfig;
  claim_experience?: ClaimExperience | null;
  claim_workflow?: Record<string, unknown> | null;
  ui_variant?: FormFlowUiVariant | string | null;
  action_placement?:
    | "inline"
    | "bottom"
    | "bottom_sticky"
    | "viewport_bottom"
    | string
    | null;
  ui_layout?: Record<string, unknown> | null;
  app_name?: string | null;
  app_logo?: string | null;
  package_versions?:
    | { name: string; version: string }[]
    | Record<string, string>
    | null;
  show_package_versions?: boolean;
  package_version_context?: string | null;
  preview_mode?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  step_name: undefined,
  title: "Form",
  description: undefined,
  auto_sync: undefined,
  claim_experience: undefined,
  claim_workflow: undefined,
  ui_variant: "default",
  action_placement: undefined,
  ui_layout: undefined,
  app_name: undefined,
  app_logo: undefined,
  package_versions: undefined,
  show_package_versions: false,
  package_version_context: "form",
  preview_mode: false,
});

// Form state
const formData = ref<Record<string, any>>({});
const errors = ref<Record<string, string>>({});
const submitting = ref(false);
const apiError = ref<string | null>(null);
const manualOverrides = ref<Record<string, boolean>>({});
const claimWorkflow = computed(() =>
  normalizeClaimWorkflow(props.claim_workflow),
);
const claimWorkflowSummary = computed(() =>
  claimWorkflowSummaryText(claimWorkflow.value),
);
const claimWorkflowReview = computed(() =>
  claimWorkflowReviewItems(claimWorkflow.value),
);
const submitLabel = computed(() =>
  claimWorkflowConfirmationLabel(claimWorkflow.value),
);
const uiVariant = computed(() => normalizeFormFlowUiVariant(props.ui_variant));
const isCompactUi = computed(() => uiVariant.value === "compact");
const isImmersiveUi = computed(() => uiVariant.value === "immersive");
const screenClass = computed(() => {
  if (!isDisburseFlow.value) {
    return "container mx-auto max-w-2xl px-4 py-8";
  }

  if (isImmersiveUi.value) {
    return "min-h-screen bg-gradient-to-b from-primary/5 via-background to-background px-3 py-4 sm:px-5 sm:py-5";
  }

  if (isCompactUi.value) {
    return "min-h-screen bg-gradient-to-b from-primary/5 via-background to-background px-4 py-5";
  }

  return "min-h-screen bg-gradient-to-b from-primary/5 via-background to-background px-5 py-8";
});
const alertClass = computed(() => {
  if (!isDisburseFlow.value) {
    return "";
  }

  return isImmersiveUi.value ? "mx-auto max-w-xl" : "mx-auto max-w-md";
});
const cardClass = computed(() => {
  if (!isDisburseFlow.value) {
    return "";
  }

  if (isImmersiveUi.value) {
    return "mx-auto flex min-h-[calc(100vh-2rem)] w-full max-w-xl flex-col border-0 bg-card/90 shadow-sm sm:min-h-[calc(100vh-2.5rem)]";
  }

  if (isCompactUi.value) {
    return "mx-auto max-w-md border-0 bg-card/90 shadow-sm";
  }

  return "mx-auto max-w-md border-0 shadow-sm bg-card/80";
});
const cardHeaderClass = computed(() => {
  if (isImmersiveUi.value) {
    return "px-4 pb-2 pt-4 sm:px-5";
  }

  return isCompactUi.value ? "px-5 py-4" : "";
});
const cardContentClass = computed(() => {
  if (isImmersiveUi.value) {
    return "flex flex-1 flex-col px-4 pb-4 sm:px-5";
  }

  return isCompactUi.value ? "px-5 pb-5" : "";
});
const formClass = computed(() => {
  if (isImmersiveUi.value) {
    return "flex flex-1 flex-col gap-3";
  }

  return isCompactUi.value ? "space-y-4" : "space-y-6";
});

// Initialize form data - must happen synchronously for Vue reactivity
const initializeFormData = () => {
  // Clear existing data to avoid stale values from previous step
  formData.value = {};

  props.fields.forEach((field) => {
    // ALWAYS set a value to avoid undefined issues
    if (field.default !== undefined && field.default !== null) {
      // Use explicitly provided default value from backend
      formData.value[field.name] = field.default;
    } else if (field.type === "checkbox") {
      formData.value[field.name] = false;
    } else if (field.type === "recipient_country") {
      formData.value[field.name] = "PH"; // Fallback if backend didn't resolve
    } else if (field.type === "settlement_rail") {
      formData.value[field.name] = null;
    } else if (field.type === "bank_account") {
      formData.value[field.name] = "GXCHPHM2XXX"; // Fallback if backend didn't resolve
    } else {
      formData.value[field.name] = "";
    }
  });
};

// localStorage persistence for fields with persist: true
const PERSIST_KEY = props.step_name
  ? `form_flow_persist_${props.step_name}`
  : null;

function loadPersistedValues() {
  if (!PERSIST_KEY) return;
  try {
    const raw = localStorage.getItem(PERSIST_KEY);
    if (!raw) return;
    const saved = JSON.parse(raw);
    props.fields.forEach((field) => {
      if (field.persist && saved[field.name] !== undefined) {
        formData.value[field.name] = saved[field.name];
      }
    });
  } catch {
    /* ignore corrupt data */
  }
}

function savePersistedValues() {
  if (!PERSIST_KEY) return;
  try {
    const toSave: Record<string, any> = {};
    props.fields.forEach((field) => {
      if (field.persist && formData.value[field.name]) {
        toSave[field.name] = formData.value[field.name];
      }
    });
    localStorage.setItem(PERSIST_KEY, JSON.stringify(toSave));
  } catch {
    /* ignore storage errors */
  }
}

// Initialize immediately (props are available in setup)
initializeFormData();
loadPersistedValues();

// Re-initialize when fields change (Inertia navigation to next step)
watch(
  () => props.fields,
  () => {
    initializeFormData();
    loadPersistedValues();
  },
  { deep: true },
);

// Debounce helper
function debounce<T extends (...args: any[]) => void>(fn: T, delay: number): T {
  let timer: ReturnType<typeof setTimeout>;
  return function (this: any, ...args: any[]) {
    clearTimeout(timer);
    timer = setTimeout(() => fn.apply(this, args), delay);
  } as T;
}

// Auto-sync logic - setup watchers if config exists
if (props.auto_sync?.enabled) {
  const {
    source_field,
    target_field,
    condition_field,
    condition_values,
    debounce_ms = 1500,
  } = props.auto_sync;

  // Track if user is currently editing target field
  let isAutoSyncing = false;

  // Sync target field from source field
  const syncFields = debounce(() => {
    // Check if condition is met
    const conditionValue = formData.value[condition_field];
    const shouldSync = condition_values.includes(conditionValue);

    // Only sync if not manually overridden and condition is met
    if (
      !manualOverrides.value[target_field] &&
      shouldSync &&
      formData.value[source_field]
    ) {
      isAutoSyncing = true;

      // Transform E.164 (+639173011987) to national format (09173011987) for account_number
      if (source_field === "mobile" && target_field === "account_number") {
        const e164 = formData.value[source_field];
        if (e164 && e164.startsWith("+63")) {
          // Convert +639173011987 → 09173011987
          formData.value[target_field] = "0" + e164.substring(3);
        } else {
          formData.value[target_field] = e164;
        }
      } else {
        formData.value[target_field] = formData.value[source_field];
      }

      // Reset flag after Vue updates
      setTimeout(() => {
        isAutoSyncing = false;
      }, 0);
    }
  }, debounce_ms);

  // Watch source field changes
  watch(
    () => formData.value[source_field],
    () => {
      syncFields();
    },
  );

  // Track manual edits to target field
  watch(
    () => formData.value[target_field],
    (newVal, oldVal) => {
      // Don't set override if this change was from auto-sync
      if (isAutoSyncing) {
        return;
      }

      // Set override if value was manually changed and differs from source
      if (
        oldVal !== undefined &&
        newVal !== undefined &&
        newVal !== formData.value[source_field]
      ) {
        manualOverrides.value[target_field] = true;
      }
    },
  );

  // Reset target field and override when condition changes
  watch(
    () => formData.value[condition_field],
    (newVal, oldVal) => {
      if (newVal !== oldVal && oldVal !== undefined) {
        formData.value[target_field] = "";
        manualOverrides.value[target_field] = false;
      }
    },
  );
}

// Computed properties
const pageTitle = computed(() => props.title || "Form");

// Extract issuer name and voucher code from description
const issuerName = computed(() => {
  if (!props.description) return null;
  // Extract from "Redeeming voucher CODE from ISSUER_NAME"
  const match = props.description.match(/from (.+)$/);
  return match ? match[1] : null;
});

const voucherCode = computed(() => {
  if (!props.description) return null;
  // Extract from "Redeeming voucher CODE from ..."
  const match = props.description.match(/voucher (\S+)/);
  return match ? match[1] : null;
});

// Detect disburse flow from description ("Redeeming voucher CODE from ...")
const isDisburseFlow = computed(() => !!voucherCode.value);

// Slice / divisible voucher detection
const amountField = computed(() =>
  props.fields.find((f) => f.name === "amount"),
);
const isOpenSlice = computed(
  () => String(amountField.value?.slice_mode) === "open",
);
const isFixedSlice = computed(
  () => String(amountField.value?.slice_mode) === "fixed",
);
const isDivisible = computed(() => isOpenSlice.value || isFixedSlice.value);

const sliceMinWithdrawal = computed(() => {
  const v = amountField.value?.min_withdrawal;
  return v ? parseFloat(String(v)) : 0;
});
const sliceAvailableBalance = computed(() => {
  const v = amountField.value?.available_balance;
  return v ? parseFloat(String(v)) : 0;
});
const sliceMaxSlices = computed(() => {
  const v = amountField.value?.max_slices;
  return v ? parseInt(String(v), 10) : 0;
});

function formatCurrency(value: number): string {
  return new Intl.NumberFormat("en-PH", {
    style: "currency",
    currency: "PHP",
  }).format(value);
}

// Field organization by UI metadata
// Hide amount badge since we show it in progress flow (or as editable input for open-mode)
const summaryFields = computed(() =>
  props.fields.filter(
    (f) => f.variant === "readonly-badge" && f.name !== "amount",
  ),
);

const heroFields = computed(() =>
  props.fields.filter(
    (f) => f.emphasis === "hero" && f.variant !== "readonly-badge",
  ),
);

const groupedFields = computed(() => {
  const groups: Record<string, FieldDefinition[]> = {};
  props.fields
    .filter(
      (f) => f.group && f.variant !== "readonly-badge" && f.emphasis !== "hero",
    )
    .forEach((field) => {
      const groupName = field.group!;
      if (!groups[groupName]) {
        groups[groupName] = [];
      }
      groups[groupName].push(field);
    });
  return groups;
});

const normalFields = computed(() =>
  props.fields.filter(
    (f) => !f.group && f.variant !== "readonly-badge" && f.emphasis !== "hero",
  ),
);

// Smart auto-focus: if a tel field already has a value (from persist/default),
// focus the next editable input so the phone number displays formatted
onMounted(() => {
  if (props.preview_mode) return;
  nextTick(() => {
    const allFields = [
      ...heroFields.value,
      ...Object.values(groupedFields.value).flat(),
      ...normalFields.value,
    ];

    // Check if a tel field has a persisted/default value
    const telWithValue = allFields.find(
      (f) => f.type === "tel" && formData.value[f.name],
    );

    if (telWithValue) {
      // Focus the first editable non-tel input after the tel field
      const telIndex = allFields.indexOf(telWithValue);
      const nextInput = allFields
        .slice(telIndex + 1)
        .find(
          (f) => ["text", "email", "number"].includes(f.type) && !f.readonly,
        );
      if (nextInput) {
        const el = document.getElementById(nextInput.name) as HTMLInputElement;
        if (el) {
          el.focus();
          return;
        }
      }
    }

    // Default: focus first hero field, or first editable input
    const heroField = heroFields.value[0];
    if (heroField) {
      const inputElement = document.getElementById(
        heroField.name,
      ) as HTMLInputElement;
      inputElement?.focus();
    } else {
      const firstEditable = allFields.find(
        (f) =>
          ["text", "email", "number", "tel"].includes(f.type) && !f.readonly,
      );
      if (firstEditable) {
        const el = document.getElementById(
          firstEditable.name,
        ) as HTMLInputElement;
        el?.focus();
      }
    }
  });
});

// Helper to format currency/number for badges
function formatBadgeValue(field?: FieldDefinition): string {
  if (!field) {
    return "-";
  }

  const value = formData.value[field.name];

  if (value === null || value === undefined || value === "") return "-";

  // Format amount as currency (50 = ₱50.00)
  if (field.name === "amount") {
    const numericValue = typeof value === "number" ? value : parseFloat(value);
    if (!isNaN(numericValue)) {
      return new Intl.NumberFormat("en-PH", {
        style: "currency",
        currency: "PHP",
      }).format(numericValue);
    }
  }

  return String(value);
}

// Form submission
async function handleSubmit() {
  if (props.preview_mode) return;
  submitting.value = true;
  apiError.value = null;
  errors.value = {};

  console.log("[GenericForm] Form data before submit:", formData.value);

  try {
    router.post(
      `/form-flow/${props.flow_id}/step/${props.step_index}`,
      {
        data: formData.value,
      },
      {
        preserveState: true,
        preserveScroll: true,
        onSuccess: (page) => {
          savePersistedValues();
          console.log("[GenericForm] Form submitted successfully");
        },
        onError: (pageErrors) => {
          console.error("[GenericForm] Validation errors:", pageErrors);

          // Extract field-level errors from Laravel validation response
          // Errors come in format: { 'data.field_name': 'Error message' }
          Object.keys(pageErrors).forEach((key) => {
            // Remove 'data.' prefix if present
            const fieldName = key.replace(/^data\./, "");
            errors.value[fieldName] = pageErrors[key];
          });

          apiError.value = "Please correct the errors below and try again.";
        },
        onFinish: () => {
          submitting.value = false;
        },
      },
    );
  } catch (error) {
    console.error("[GenericForm] Submission error:", error);
    apiError.value = "An unexpected error occurred. Please try again.";
    submitting.value = false;
  }
}

function handleCancel() {
  if (props.preview_mode) return;
  router.post(`/form-flow/${props.flow_id}/cancel`);
}

// Get field label
function getFieldLabel(field: FieldDefinition): string {
  return (
    field.label || field.name.charAt(0).toUpperCase() + field.name.slice(1)
  );
}

// Get field placeholder
function getFieldPlaceholder(field: FieldDefinition): string {
  return field.placeholder || `Enter ${getFieldLabel(field).toLowerCase()}`;
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
  <Head :title="pageTitle" />

  <div :class="screenClass" :data-form-flow-ui-variant="uiVariant">
    <!-- Error Alert -->
    <Alert
      v-if="apiError"
      variant="destructive"
      class="mb-6"
      :class="alertClass"
    >
      <AlertCircle class="h-4 w-4" />
      <AlertDescription>
        {{ apiError }}
      </AlertDescription>
    </Alert>

    <!-- Form Card -->
    <Card :class="cardClass">
      <CardHeader :class="cardHeaderClass">
        <div
          v-if="props.app_logo || props.app_name"
          class="mb-3 flex items-center gap-3"
        >
          <img
            v-if="props.app_logo"
            :src="props.app_logo"
            :alt="props.app_name ?? 'Logo'"
            class="h-9 max-h-9 w-auto max-w-28 object-contain"
          />
          <p
            v-if="props.app_name"
            class="text-xs font-semibold uppercase tracking-[0.12em] text-muted-foreground"
          >
            {{ props.app_name }}
          </p>
        </div>
        <CardTitle class="text-lg sm:text-xl">{{ title }}</CardTitle>
      </CardHeader>
      <CardContent :class="cardContentClass">
        <form @submit.prevent="handleSubmit" :class="formClass">
          <!-- Hidden fields (not visible but needed for auto-sync conditions) -->
          <input
            v-for="field in props.fields.filter((f) => f.type === 'hidden')"
            :key="field.name"
            type="hidden"
            :name="field.name"
            v-model="formData[field.name]"
          />

          <div
            v-if="claimWorkflow"
            class="rounded-lg border border-primary/15 bg-primary/5 p-3"
            data-testid="form-flow-claim-workflow-panel"
          >
            <div class="space-y-1">
              <p class="text-sm font-semibold text-foreground">
                {{ claimWorkflow.title || title }}
              </p>
              <p
                v-if="claimWorkflow.description"
                class="text-sm text-muted-foreground"
              >
                {{ claimWorkflow.description }}
              </p>
              <p
                v-if="claimWorkflowSummary"
                class="text-sm text-muted-foreground"
              >
                {{ claimWorkflowSummary }}
              </p>
            </div>

            <dl
              v-if="claimWorkflowReview.length > 0"
              class="mt-3 grid grid-cols-1 gap-2 sm:grid-cols-2"
            >
              <div
                v-for="item in claimWorkflowReview"
                :key="item.label"
                class="rounded-md border border-border/70 bg-background/80 px-3 py-2"
              >
                <dt
                  class="text-xs uppercase tracking-wide text-muted-foreground"
                >
                  {{ item.label }}
                </dt>
                <dd
                  class="mt-1 break-words text-sm font-semibold text-foreground"
                >
                  {{ item.value }}
                </dd>
              </div>
            </dl>
          </div>

          <!-- Summary Badges Section -->
          <div
            v-if="summaryFields.length > 0"
            class="mb-3 grid grid-cols-1 gap-2 md:grid-cols-2 lg:grid-cols-3"
          >
            <div
              v-for="field in summaryFields"
              :key="field.name"
              class="flex flex-col"
            >
              <span class="text-xs text-muted-foreground mb-1">{{
                getFieldLabel(field)
              }}</span>
              <Badge
                variant="secondary"
                class="text-base py-2 px-4 justify-start w-full"
              >
                <span class="font-bold">{{ formatBadgeValue(field) }}</span>
              </Badge>
            </div>
          </div>

          <!-- Open-mode: editable amount input with numpad -->
          <fieldset
            v-if="isOpenSlice"
            class="border rounded-lg px-3 pt-1 pb-3 bg-muted/5"
          >
            <legend class="text-sm font-medium text-muted-foreground px-2">
              Withdrawal Amount
            </legend>
            <NumberInputWithKeypad
              v-model="formData['amount']"
              prefix="₱"
              :min="sliceMinWithdrawal"
              :max="sliceAvailableBalance"
              :allow-decimal="true"
              keypad-mode="amount"
              keypad-title="Withdrawal Amount"
              hero
            />
            <div
              class="flex flex-wrap items-center justify-center gap-1.5 mt-1"
            >
              <Badge variant="outline" class="px-2 py-0.5 text-xs font-medium">
                {{ formatCurrency(sliceMinWithdrawal) }} –
                {{ formatCurrency(sliceAvailableBalance) }}
              </Badge>
              <span class="text-muted-foreground text-xs">•</span>
              <Badge variant="outline" class="px-2 py-0.5 text-xs font-medium">
                Up to {{ sliceMaxSlices }} withdrawals
              </Badge>
            </div>
            <p
              v-if="errors['amount']"
              class="text-sm text-destructive text-center mt-1"
            >
              {{ errors["amount"] }}
            </p>
          </fieldset>

          <!-- Fixed-mode: show slice info badge -->
          <div
            v-else-if="isFixedSlice && sliceMaxSlices > 0"
            class="text-center"
          >
            <Badge variant="secondary" class="text-base py-2 px-4">
              <span class="font-bold">{{ formatBadgeValue(amountField) }}</span>
            </Badge>
            <p class="text-xs text-muted-foreground mt-1">
              Slice 1 of {{ sliceMaxSlices }}
            </p>
          </div>

          <!-- Hero Fields Section -->
          <div v-if="heroFields.length > 0" class="mb-4 space-y-4">
            <div
              v-for="field in heroFields"
              :key="field.name"
              class="space-y-3"
            >
              <Label
                :for="field.name"
                :class="[
                  'text-2xl font-bold',
                  { 'text-destructive': errors[field.name] },
                ]"
              >
                {{ getFieldLabel(field) }}
                <span v-if="field.required" class="text-destructive">*</span>
              </Label>

              <!-- Hero field input with larger styling -->
              <Input
                v-if="field.type === 'text' || field.type === 'email'"
                :id="field.name"
                v-model="formData[field.name]"
                :type="field.type"
                :placeholder="getFieldPlaceholder(field)"
                :required="field.required"
                :readonly="field.readonly"
                :disabled="field.disabled"
                :class="[
                  'py-4 text-lg ring-2 ring-primary/20 focus-visible:ring-4 focus-visible:ring-primary/30 transition-all',
                  {
                    'border-destructive ring-destructive/20':
                      errors[field.name],
                  },
                ]"
                autofocus
              />

              <!-- Hero phone input -->
              <PhoneInput
                v-else-if="field.type === 'tel'"
                v-model="formData[field.name]"
                :error="errors[field.name]"
                :placeholder="field.placeholder || ''"
                :required="field.required"
                :readonly="field.readonly"
                :disabled="field.disabled"
                autofocus
              />

              <!-- Transaction badges below mobile field (hide amount badge for open-mode since it has its own input) -->
              <div
                v-if="field.name === 'mobile' && issuerName"
                class="flex flex-wrap items-center gap-1.5 mt-2"
              >
                <Badge
                  variant="outline"
                  class="px-2 py-0.5 text-xs font-medium"
                >
                  {{ issuerName }}
                </Badge>
                <template v-if="!isOpenSlice">
                  <span class="text-muted-foreground text-xs">•</span>
                  <Badge
                    variant="outline"
                    class="px-2 py-0.5 text-xs font-medium"
                  >
                    {{
                      formatBadgeValue(
                        props.fields.find((f) => f.name === "amount"),
                      )
                    }}
                  </Badge>
                </template>
              </div>

              <!-- Help text (hide for mobile field since badges replace it) -->
              <p
                v-if="field.help_text && field.name !== 'mobile'"
                class="text-sm text-muted-foreground"
              >
                {{ field.help_text }}
              </p>
            </div>
          </div>

          <!-- Separator before grouped/normal fields (when hero fields present) -->
          <div
            v-if="
              heroFields.length > 0 &&
              (Object.keys(groupedFields).length > 0 || normalFields.length > 0)
            "
            class="relative my-4"
          >
            <Separator />
            <div
              v-if="voucherCode"
              class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 bg-background px-2"
            >
              <span
                class="inline-flex items-center gap-1.5 px-3 py-0.5 text-sm font-mono font-semibold tracking-widest text-primary bg-primary/5 border border-primary/20 rounded-full"
              >
                <span class="text-primary/40" aria-hidden="true">||</span>
                {{ voucherCode }}
                <span class="text-primary/40" aria-hidden="true">||</span>
              </span>
            </div>
          </div>

          <!-- Grouped Fields Sections (with voucher code separator between first and remaining groups) -->
          <template
            v-for="(groupFields, groupName, groupIndex) in groupedFields"
            :key="groupName"
          >
            <div class="space-y-3">
              <fieldset class="rounded-lg border bg-muted/5 px-4 pb-4 pt-2">
                <legend class="px-2 text-sm font-medium text-muted-foreground">
                  {{
                    groupName
                      .replace("_", " ")
                      .replace(/\b\w/g, (l) => l.toUpperCase())
                  }}
                </legend>

                <div class="mt-2 space-y-3">
                  <div
                    v-for="field in groupFields"
                    :key="field.name"
                    class="space-y-2"
                  >
                    <!-- Render field with standard template below -->
                    <template
                      v-if="
                        field.type === 'text' ||
                        field.type === 'email' ||
                        field.type === 'date' ||
                        field.type === 'number'
                      "
                    >
                      <Label
                        :for="field.name"
                        :class="{ 'text-destructive': errors[field.name] }"
                      >
                        {{ getFieldLabel(field) }}
                        <span v-if="field.required" class="text-destructive"
                          >*</span
                        >
                      </Label>
                      <Input
                        :id="field.name"
                        v-model="formData[field.name]"
                        :type="field.type"
                        :placeholder="getFieldPlaceholder(field)"
                        :required="field.required"
                        :min="field.min"
                        :max="field.max"
                        :step="field.step"
                        :readonly="field.readonly"
                        :disabled="field.disabled"
                        :class="[
                          { 'border-destructive': errors[field.name] },
                          field.name === 'account_number'
                            ? 'font-bold text-lg tracking-widest text-center placeholder:text-center'
                            : '',
                        ]"
                      />
                      <p
                        v-if="field.help_text"
                        class="text-xs text-muted-foreground"
                      >
                        {{ field.help_text }}
                      </p>
                      <p
                        v-if="errors[field.name]"
                        class="text-sm text-destructive"
                      >
                        {{ errors[field.name] }}
                      </p>
                    </template>

                    <!-- Phone Input -->
                    <template v-else-if="field.type === 'tel'">
                      <Label
                        :for="field.name"
                        :class="{ 'text-destructive': errors[field.name] }"
                      >
                        {{ getFieldLabel(field) }}
                        <span v-if="field.required" class="text-destructive"
                          >*</span
                        >
                      </Label>
                      <PhoneInput
                        v-model="formData[field.name]"
                        :error="errors[field.name]"
                        :placeholder="field.placeholder || ''"
                        :required="field.required"
                        :readonly="field.readonly"
                        :disabled="field.disabled"
                      />
                      <!-- Transaction badges below mobile field (hide amount badge for open-mode) -->
                      <div
                        v-if="field.name === 'mobile' && issuerName"
                        class="flex flex-wrap items-center gap-1.5 mt-1"
                      >
                        <Badge
                          variant="outline"
                          class="px-2 py-0.5 text-xs font-medium"
                        >
                          {{ issuerName }}
                        </Badge>
                        <template v-if="!isOpenSlice">
                          <span class="text-muted-foreground text-xs">•</span>
                          <Badge
                            variant="outline"
                            class="px-2 py-0.5 text-xs font-medium"
                          >
                            {{
                              formatBadgeValue(
                                props.fields.find((f) => f.name === "amount"),
                              )
                            }}
                          </Badge>
                        </template>
                      </div>
                      <p
                        v-if="field.help_text && field.name !== 'mobile'"
                        class="text-xs text-muted-foreground"
                      >
                        {{ field.help_text }}
                      </p>
                    </template>

                    <!-- Bank Account (BankEMISelect) -->
                    <template v-else-if="field.type === 'bank_account'">
                      <Label
                        :for="field.name"
                        :class="{ 'text-destructive': errors[field.name] }"
                      >
                        {{ getFieldLabel(field) }}
                        <span v-if="field.required" class="text-destructive"
                          >*</span
                        >
                      </Label>
                      <BankEMISelect
                        v-model="formData[field.name]"
                        :settlement-rail="formData.settlement_rail || null"
                        :institutions="field.institution_options ?? []"
                        :disabled="field.disabled || field.readonly"
                      />
                      <p
                        v-if="field.help_text"
                        class="text-xs text-muted-foreground"
                      >
                        {{ field.help_text }}
                      </p>
                      <p
                        v-if="errors[field.name]"
                        class="text-sm text-destructive"
                      >
                        {{ errors[field.name] }}
                      </p>
                    </template>

                    <!-- Settlement Rail -->
                    <template v-else-if="field.type === 'settlement_rail'">
                      <Label
                        :for="field.name"
                        :class="{ 'text-destructive': errors[field.name] }"
                      >
                        {{ getFieldLabel(field) }}
                        <span v-if="field.required" class="text-destructive"
                          >*</span
                        >
                      </Label>
                      <SettlementRailSelect
                        v-model="formData[field.name]"
                        :amount="formData.amount || 0"
                        :bank-code="formData.bank_account || null"
                        :disabled="field.disabled || field.readonly"
                      />
                      <p
                        v-if="field.help_text"
                        class="text-xs text-muted-foreground"
                      >
                        {{ field.help_text }}
                      </p>
                      <p
                        v-if="errors[field.name]"
                        class="text-sm text-destructive"
                      >
                        {{ errors[field.name] }}
                      </p>
                    </template>

                    <!-- Recipient Country -->
                    <template v-else-if="field.type === 'recipient_country'">
                      <Label
                        :for="field.name"
                        :class="{ 'text-destructive': errors[field.name] }"
                      >
                        {{ getFieldLabel(field) }}
                        <span v-if="field.required" class="text-destructive"
                          >*</span
                        >
                      </Label>
                      <CountrySelect
                        v-model="formData[field.name]"
                        :disabled="field.disabled || field.readonly"
                      />
                      <p
                        v-if="field.help_text"
                        class="text-xs text-muted-foreground"
                      >
                        {{ field.help_text }}
                      </p>
                      <p
                        v-if="errors[field.name]"
                        class="text-sm text-destructive"
                      >
                        {{ errors[field.name] }}
                      </p>
                    </template>
                  </div>
                </div>
              </fieldset>
            </div>

            <!-- Voucher code separator between first and remaining groups -->
            <div
              v-if="
                groupIndex === 0 &&
                Object.keys(groupedFields).length > 1 &&
                voucherCode &&
                heroFields.length === 0
              "
              class="relative my-4"
            >
              <Separator />

              <div
                class="absolute inset-x-0 top-1/2 flex -translate-y-1/2 justify-center bg-background/0 px-2"
              >
                <span
                  class="inline-flex items-center justify-center gap-1.5 rounded-full border border-primary/20 bg-background px-3 py-0.5 text-sm font-mono font-semibold tracking-widest text-primary"
                >
                  <span class="text-primary/40" aria-hidden="true">||</span>
                  {{ voucherCode }}
                  <span class="text-primary/40" aria-hidden="true">||</span>
                </span>
              </div>
            </div>
          </template>

          <!-- Normal Fields (non-grouped, non-hero, non-badge) -->
          <div v-if="normalFields.length > 0" class="space-y-3">
            <div
              v-for="field in normalFields"
              :key="field.name"
              class="space-y-2"
            >
              <!-- Text Input -->
              <div
                v-if="
                  field.type === 'text' ||
                  field.type === 'email' ||
                  field.type === 'date' ||
                  field.type === 'number'
                "
              >
                <Label
                  :for="field.name"
                  :class="{ 'text-destructive': errors[field.name] }"
                >
                  {{ getFieldLabel(field) }}
                  <span v-if="field.required" class="text-destructive">*</span>
                </Label>
                <Input
                  :id="field.name"
                  v-model="formData[field.name]"
                  :type="field.type"
                  :placeholder="getFieldPlaceholder(field)"
                  :required="field.required"
                  :min="field.min"
                  :max="field.max"
                  :step="field.step"
                  :readonly="field.readonly"
                  :disabled="field.disabled"
                  :class="{ 'border-destructive': errors[field.name] }"
                />
                <p v-if="errors[field.name]" class="text-sm text-destructive">
                  {{ errors[field.name] }}
                </p>
              </div>

              <!-- Phone Input -->
              <div v-else-if="field.type === 'tel'">
                <Label
                  :for="field.name"
                  :class="{ 'text-destructive': errors[field.name] }"
                >
                  {{ getFieldLabel(field) }}
                  <span v-if="field.required" class="text-destructive">*</span>
                </Label>
                <PhoneInput
                  v-model="formData[field.name]"
                  :error="errors[field.name]"
                  :placeholder="field.placeholder || ''"
                  :required="field.required"
                  :readonly="field.readonly"
                  :disabled="field.disabled"
                />
              </div>

              <!-- Textarea -->
              <div v-else-if="field.type === 'textarea'">
                <Label
                  :for="field.name"
                  :class="{ 'text-destructive': errors[field.name] }"
                >
                  {{ getFieldLabel(field) }}
                  <span v-if="field.required" class="text-destructive">*</span>
                </Label>
                <Textarea
                  :id="field.name"
                  v-model="formData[field.name]"
                  :placeholder="getFieldPlaceholder(field)"
                  :required="field.required"
                  :readonly="field.readonly"
                  :disabled="field.disabled"
                  :class="{ 'border-destructive': errors[field.name] }"
                  rows="4"
                />
                <p v-if="errors[field.name]" class="text-sm text-destructive">
                  {{ errors[field.name] }}
                </p>
              </div>

              <!-- Select -->
              <div v-else-if="field.type === 'select'">
                <Label
                  :for="field.name"
                  :class="{ 'text-destructive': errors[field.name] }"
                >
                  {{ getFieldLabel(field) }}
                  <span v-if="field.required" class="text-destructive">*</span>
                </Label>
                <Select
                  v-model="formData[field.name]"
                  :required="field.required"
                  :disabled="field.disabled"
                >
                  <SelectTrigger
                    :class="{ 'border-destructive': errors[field.name] }"
                  >
                    <SelectValue
                      :placeholder="`Select ${getFieldLabel(field).toLowerCase()}`"
                    />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem
                      v-for="option in field.options || []"
                      :key="option"
                      :value="option"
                    >
                      {{ option }}
                    </SelectItem>
                  </SelectContent>
                </Select>
                <p v-if="errors[field.name]" class="text-sm text-destructive">
                  {{ errors[field.name] }}
                </p>
              </div>

              <!-- Checkbox -->
              <div
                v-else-if="field.type === 'checkbox'"
                class="flex items-center space-x-2"
              >
                <Checkbox
                  :id="field.name"
                  :checked="formData[field.name]"
                  @update:modelValue="
                    (value) => {
                      console.log(
                        `[GenericForm] Checkbox '${field.name}' changed:`,
                        value,
                        'type:',
                        typeof value,
                      );
                      formData[field.name] = value;
                      console.log(
                        `[GenericForm] formData['${field.name}'] is now:`,
                        formData[field.name],
                      );
                    }
                  "
                  :required="field.required"
                  :disabled="field.disabled"
                  :class="{ 'border-destructive': errors[field.name] }"
                />
                <Label
                  :for="field.name"
                  :class="{ 'text-destructive': errors[field.name] }"
                  class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70"
                >
                  {{ getFieldLabel(field) }}
                  <span v-if="field.required" class="text-destructive">*</span>
                </Label>
                <p
                  v-if="errors[field.name]"
                  class="text-sm text-destructive ml-6"
                >
                  {{ errors[field.name] }}
                </p>
              </div>

              <!-- File Input -->
              <div v-else-if="field.type === 'file'">
                <Label
                  :for="field.name"
                  :class="{ 'text-destructive': errors[field.name] }"
                >
                  {{ getFieldLabel(field) }}
                  <span v-if="field.required" class="text-destructive">*</span>
                </Label>
                <Input
                  :id="field.name"
                  type="file"
                  :required="field.required"
                  :class="{ 'border-destructive': errors[field.name] }"
                  @change="
                    (e: Event) => {
                      const target = e.target as HTMLInputElement;
                      formData[field.name] = target.files?.[0] || null;
                    }
                  "
                />
                <p v-if="errors[field.name]" class="text-sm text-destructive">
                  {{ errors[field.name] }}
                </p>
              </div>

              <!-- Recipient Country -->
              <div v-else-if="field.type === 'recipient_country'">
                <Label
                  :for="field.name"
                  :class="{ 'text-destructive': errors[field.name] }"
                >
                  {{ getFieldLabel(field) }}
                  <span v-if="field.required" class="text-destructive">*</span>
                </Label>
                <CountrySelect
                  v-model="formData[field.name]"
                  :disabled="field.disabled || field.readonly"
                />
                <p v-if="errors[field.name]" class="text-sm text-destructive">
                  {{ errors[field.name] }}
                </p>
              </div>

              <!-- Settlement Rail -->
              <div v-else-if="field.type === 'settlement_rail'">
                <Label
                  :for="field.name"
                  :class="{ 'text-destructive': errors[field.name] }"
                >
                  {{ getFieldLabel(field) }}
                  <span v-if="field.required" class="text-destructive">*</span>
                </Label>
                <SettlementRailSelect
                  v-model="formData[field.name]"
                  :amount="formData.amount || 0"
                  :bank-code="formData.bank_account || null"
                  :disabled="field.disabled || field.readonly"
                />
                <p v-if="errors[field.name]" class="text-sm text-destructive">
                  {{ errors[field.name] }}
                </p>
              </div>

              <!-- Bank/EMI Account -->
              <div v-else-if="field.type === 'bank_account'">
                <Label
                  :for="field.name"
                  :class="{ 'text-destructive': errors[field.name] }"
                >
                  {{ getFieldLabel(field) }}
                  <span v-if="field.required" class="text-destructive">*</span>
                </Label>
                <BankEMISelect
                  v-model="formData[field.name]"
                  :settlement-rail="formData.settlement_rail || null"
                  :institutions="field.institution_options ?? []"
                  :disabled="field.disabled || field.readonly"
                />
                <p v-if="errors[field.name]" class="text-sm text-destructive">
                  {{ errors[field.name] }}
                </p>
              </div>
            </div>
          </div>

          <FormFlowActions
            primary-type="submit"
            secondary-label="Cancel"
            :primary-label="submitLabel"
            :variant="uiVariant"
            :action-placement="props.action_placement"
            :primary-disabled="submitting"
            :secondary-disabled="submitting"
            :processing="submitting"
            @secondary="handleCancel"
          />
        </form>
      </CardContent>
    </Card>

    <FormFlowVersionStrip
      :show="props.show_package_versions"
      :package-versions="props.package_versions"
      :context="props.package_version_context"
    />
  </div>
</template>
