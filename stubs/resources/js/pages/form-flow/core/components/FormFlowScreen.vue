<script setup lang="ts">
import type { HTMLAttributes } from "vue";
import { computed } from "vue";
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import {
  normalizeFormFlowUiVariant,
  type FormFlowUiVariant,
} from "./formFlowUiVariant";

const props = withDefaults(
  defineProps<{
    title: string;
    description?: string;
    variant?: FormFlowUiVariant | string | null;
    screenClass?: HTMLAttributes["class"];
    innerClass?: HTMLAttributes["class"];
    cardClass?: HTMLAttributes["class"];
    contentClass?: HTMLAttributes["class"];
  }>(),
  {
    description: "",
    variant: "default",
    screenClass: "",
    innerClass: "",
    cardClass: "",
    contentClass: "",
  },
);

const normalizedVariant = computed(() =>
  normalizeFormFlowUiVariant(props.variant),
);

const screenVariantClass = computed(() => {
  if (normalizedVariant.value === "immersive") {
    return "min-h-screen bg-gradient-to-b from-primary/5 via-background to-background px-3 py-3 sm:px-5 sm:py-5";
  }

  if (normalizedVariant.value === "compact") {
    return "min-h-screen bg-gradient-to-b from-primary/5 via-background to-background px-4 py-5";
  }

  return "min-h-screen bg-gradient-to-b from-primary/5 via-background to-background px-5 py-8";
});

const innerVariantClass = computed(() => {
  if (normalizedVariant.value === "immersive") {
    return "mx-auto flex min-h-[calc(100vh-1.5rem)] w-full max-w-5xl flex-col sm:min-h-[calc(100vh-2.5rem)]";
  }

  if (normalizedVariant.value === "compact") {
    return "mx-auto w-full max-w-xl";
  }

  return "mx-auto w-full max-w-2xl";
});

const cardVariantClass = computed(() => {
  if (normalizedVariant.value === "immersive") {
    return "flex flex-1 flex-col border-0 bg-card/90 shadow-sm";
  }

  return "border-0 bg-card/90 shadow-sm";
});

const headerVariantClass = computed(() => {
  if (normalizedVariant.value === "immersive") {
    return "px-4 py-4 sm:px-5";
  }

  if (normalizedVariant.value === "compact") {
    return "px-5 py-4";
  }

  return "";
});

const titleVariantClass = computed(() => {
  if (normalizedVariant.value === "immersive") {
    return "text-lg";
  }

  if (normalizedVariant.value === "compact") {
    return "text-xl";
  }

  return "";
});

const descriptionVariantClass = computed(() =>
  normalizedVariant.value === "immersive" ? "text-sm" : "",
);

const contentVariantClass = computed(() => {
  if (normalizedVariant.value === "immersive") {
    return "flex flex-1 flex-col px-4 pb-4 sm:px-5";
  }

  if (normalizedVariant.value === "compact") {
    return "px-5 pb-5";
  }

  return "";
});
</script>

<template>
  <div data-form-flow-screen :class="[screenVariantClass, props.screenClass]">
    <div :class="[innerVariantClass, props.innerClass]">
      <slot name="alert" />

      <Card :class="[cardVariantClass, props.cardClass]">
        <CardHeader :class="headerVariantClass">
          <CardTitle :class="['flex items-center gap-2', titleVariantClass]">
            <slot name="icon" />
            {{ props.title }}
          </CardTitle>
          <CardDescription
            v-if="props.description"
            :class="descriptionVariantClass"
          >
            {{ props.description }}
          </CardDescription>
        </CardHeader>
        <CardContent :class="[contentVariantClass, props.contentClass]">
          <slot />
        </CardContent>
      </Card>
    </div>
  </div>
</template>
