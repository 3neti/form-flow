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
  }>(),
  {
    packageVersions: null,
    show: false,
    label: "QA build",
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
  name.replace(/^3neti\//, "").replace(/^form-handler-/, "");
</script>

<template>
  <div
    v-if="props.show && normalizedPackageVersions.length > 0"
    class="mx-auto mt-3 flex w-full max-w-md flex-col items-center gap-2 text-center"
    data-testid="form-flow-package-version-strip"
    aria-label="Form flow package versions"
  >
    <p
      class="text-[10px] font-semibold uppercase tracking-[0.18em] text-muted-foreground/60"
    >
      {{ props.label }}
    </p>

    <div class="flex max-w-full flex-wrap justify-center gap-1.5">
      <span
        v-for="packageVersion in normalizedPackageVersions"
        :key="packageVersion.name"
        class="inline-flex max-w-full items-center gap-1 rounded-full border border-border/70 bg-background/80 px-2 py-1 text-[10px] leading-none text-muted-foreground shadow-sm"
        :title="`${packageVersion.name} ${packageVersion.version}`"
      >
        <span class="max-w-28 truncate font-medium text-foreground/75">
          {{ shortPackageName(packageVersion.name) }}
        </span>
        <span class="font-mono text-muted-foreground/80">
          {{ packageVersion.version }}
        </span>
      </span>
    </div>
  </div>
</template>
