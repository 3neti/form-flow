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

interface PackageVersion {
  name: string;
  version: string;
}

const props = withDefaults(
  defineProps<{
    title: string;
    description?: string;
    variant?: FormFlowUiVariant | string | null;
    verticalAlign?: "center" | "top" | string | null;
    appName?: string | null;
    appLogo?: string | null;
    packageVersions?: PackageVersion[] | Record<string, string> | null;
    showPackageVersions?: boolean;
    screenClass?: HTMLAttributes["class"];
    innerClass?: HTMLAttributes["class"];
    cardClass?: HTMLAttributes["class"];
    contentClass?: HTMLAttributes["class"];
  }>(),
  {
    description: "",
    variant: "default",
    verticalAlign: null,
    appName: null,
    appLogo: null,
    packageVersions: null,
    showPackageVersions: false,
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

const innerAlignmentClass = computed(() =>
  props.verticalAlign === "center" ? "justify-center" : "",
);

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

const normalizedPackageVersions = computed<PackageVersion[]>(() => {
  const versions = props.packageVersions;

  if (!versions) {
    return [];
  }

  if (Array.isArray(versions)) {
    return versions.filter((version) => version.name && version.version);
  }

  return Object.entries(versions)
    .filter(([, version]) => typeof version === "string" && version.length > 0)
    .map(([name, version]) => ({ name, version }));
});
</script>

<template>
  <div data-form-flow-screen :class="[screenVariantClass, props.screenClass]">
    <div :class="[innerVariantClass, innerAlignmentClass, props.innerClass]">
      <slot name="alert" />

      <Card :class="[cardVariantClass, props.cardClass]">
        <CardHeader :class="headerVariantClass">
          <div
            v-if="props.appLogo || props.appName"
            class="mb-3 flex items-center gap-3"
          >
            <img
              v-if="props.appLogo"
              :src="props.appLogo"
              :alt="props.appName ?? 'Logo'"
              class="h-9 max-h-9 w-auto max-w-28 object-contain"
            />
            <p
              v-if="props.appName"
              class="text-xs font-semibold uppercase tracking-[0.12em] text-muted-foreground"
            >
              {{ props.appName }}
            </p>
          </div>
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

      <p
        v-if="props.showPackageVersions && normalizedPackageVersions.length > 0"
        class="mt-3 text-center text-[10px] font-medium text-muted-foreground/70"
        data-testid="form-flow-package-version-strip"
      >
        <span
          v-for="(packageVersion, index) in normalizedPackageVersions"
          :key="packageVersion.name"
        >
          <span v-if="index > 0" aria-hidden="true"> · </span>
          <span>{{ packageVersion.name }} {{ packageVersion.version }}</span>
        </span>
      </p>
    </div>
  </div>
</template>
