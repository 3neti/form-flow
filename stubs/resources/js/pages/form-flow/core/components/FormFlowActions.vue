<script setup lang="ts">
import type { ButtonHTMLAttributes } from "vue";
import { computed } from "vue";
import { Loader2 } from "lucide-vue-next";
import { Button } from "@/components/ui/button";
import {
  normalizeFormFlowUiVariant,
  type FormFlowUiVariant,
} from "./formFlowUiVariant";

type FormFlowActionPlacement = "inline" | "bottom" | "bottom_sticky";

const props = withDefaults(
  defineProps<{
    primaryLabel?: string;
    secondaryLabel?: string;
    primaryType?: ButtonHTMLAttributes["type"];
    variant?: FormFlowUiVariant | string | null;
    actionPlacement?: FormFlowActionPlacement | string | null;
    primaryDisabled?: boolean;
    secondaryDisabled?: boolean;
    processing?: boolean;
    showSecondary?: boolean;
  }>(),
  {
    primaryLabel: "Continue",
    secondaryLabel: "Back",
    primaryType: "submit",
    variant: "default",
    actionPlacement: null,
    primaryDisabled: false,
    secondaryDisabled: false,
    processing: false,
    showSecondary: true,
  },
);

const emit = defineEmits<{
  primary: [];
  secondary: [];
}>();

const normalizedVariant = computed(() =>
  normalizeFormFlowUiVariant(props.variant),
);

const normalizedActionPlacement = computed<FormFlowActionPlacement>(() =>
  props.actionPlacement === "bottom_sticky" ||
  props.actionPlacement === "bottom" ||
  props.actionPlacement === "inline"
    ? props.actionPlacement
    : normalizedVariant.value === "immersive"
      ? "bottom"
      : "inline",
);

const containerClass = computed(() => {
  const columns = props.showSecondary ? "sm:grid-cols-2" : "grid-cols-1";

  if (normalizedActionPlacement.value === "bottom_sticky") {
    return [
      "sticky bottom-0 z-10 -mx-4 mt-auto grid gap-3 border-t bg-card/95 px-4 py-3 backdrop-blur supports-[backdrop-filter]:bg-card/85 sm:-mx-5 sm:px-5",
      columns,
    ];
  }

  if (normalizedActionPlacement.value === "bottom") {
    return ["mt-auto grid gap-3 border-t pt-4", columns];
  }

  if (normalizedVariant.value === "compact") {
    return ["grid gap-2 pt-3", columns];
  }

  return ["grid gap-3 pt-4", columns];
});

const buttonClass = computed(() =>
  normalizedVariant.value === "immersive"
    ? "h-11 w-full rounded-full"
    : "w-full rounded-full",
);
</script>

<template>
  <div :class="containerClass">
    <Button
      v-if="props.showSecondary"
      type="button"
      variant="outline"
      :class="buttonClass"
      :disabled="props.secondaryDisabled || props.processing"
      @click="emit('secondary')"
    >
      {{ props.secondaryLabel }}
    </Button>

    <Button
      :type="props.primaryType"
      :class="buttonClass"
      :disabled="props.primaryDisabled || props.processing"
      @click="emit('primary')"
    >
      <Loader2 v-if="props.processing" class="mr-2 h-4 w-4 animate-spin" />
      {{ props.processing ? "Please wait..." : props.primaryLabel }}
    </Button>
  </div>
</template>
