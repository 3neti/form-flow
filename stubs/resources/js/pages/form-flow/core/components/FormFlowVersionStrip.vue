<script setup lang="ts">
import { computed } from "vue";

interface PackageVersion {
  name: string;
  version: string;
}

const props = withDefaults(
  defineProps<{
    packageVersions?: PackageVersion[] | Record<string, string> | null;
    show?: boolean;
    label?: string;
    context?: string | null;
  }>(),
  {
    packageVersions: null,
    show: false,
    label: "QA build",
    context: null,
  },
);

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

const shortPackageName = (name: string): string =>
  name
    .replace(/^3neti\/x-change$/, "x-change")
    .replace(/^3neti\/form-flow$/, "form-flow")
    .replace(/^3neti\/form-handler-/, "")
    .replace(/^3neti\//, "");

const packageNamesForContext = (context?: string | null): string[] => {
  const base = ["3neti/x-change", "3neti/form-flow"];

  switch (context) {
    case "otp":
      return [...base, "3neti/form-handler-otp"];
    case "selfie":
      return [...base, "3neti/form-handler-selfie"];
    case "signature":
      return [...base, "3neti/form-handler-signature"];
    case "location":
      return [...base, "3neti/form-handler-location"];
    case "kyc":
      return [...base, "3neti/form-handler-kyc"];
    default:
      return base;
  }
};

const visiblePackageVersions = computed<PackageVersion[]>(() => {
  const allowedNames = packageNamesForContext(props.context);
  const filtered = normalizedPackageVersions.value.filter((packageVersion) =>
    allowedNames.includes(packageVersion.name),
  );

  return filtered.length > 0 ? filtered : normalizedPackageVersions.value;
});
</script>

<template>
  <div
    v-if="props.show && visiblePackageVersions.length > 0"
    class="mx-auto mt-2 flex w-full max-w-md items-center justify-center gap-1.5 overflow-hidden text-center text-[9px] leading-none text-muted-foreground/70"
    data-testid="form-flow-package-version-strip"
    aria-label="Form flow package versions"
  >
    <p
      class="shrink-0 font-semibold uppercase tracking-[0.12em] text-muted-foreground/60"
    >
      {{ props.label }}
    </p>

    <div class="flex min-w-0 max-w-full flex-nowrap justify-center gap-1.5 overflow-hidden">
      <span
        v-for="packageVersion in visiblePackageVersions"
        :key="packageVersion.name"
        class="inline-flex min-w-0 shrink items-center gap-0.5 truncate"
        :title="`${packageVersion.name} ${packageVersion.version}`"
      >
        <span class="truncate font-medium text-foreground/65">
          {{ shortPackageName(packageVersion.name) }}
        </span>
        <span class="shrink-0 font-mono text-muted-foreground/70">
          {{ packageVersion.version }}
        </span>
      </span>
    </div>
  </div>
</template>
