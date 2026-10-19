<script setup lang="ts">
import { computed } from "vue";
import {
  useFormFlowPresentation,
  type NoticeRole,
  type NoticeStyle,
} from "./formFlowPresentation";
import FormFlowStatusIcon from "./FormFlowStatusIcon.vue";
const props = withDefaults(defineProps<{ role?: NoticeRole }>(), {
  role: "info",
});
const policy = useFormFlowPresentation();
const defaults: Record<NoticeRole, NoticeStyle> = {
  info: {
    background: "#f1f5f9",
    foreground: "#0f172a",
    border: "#cbd5e1",
    icon: "receipt",
  },
  pending: {
    background: "#fffbeb",
    foreground: "#92400e",
    border: "#fde68a",
    icon: "clock",
  },
  success: {
    background: "#f0fdf4",
    foreground: "#166534",
    border: "#bbf7d0",
    icon: "circle-check",
  },
  attention: {
    background: "#fffbeb",
    foreground: "#92400e",
    border: "#fde68a",
    icon: "triangle-alert",
  },
  error: {
    background: "#fef2f2",
    foreground: "#991b1b",
    border: "#fecaca",
    icon: "circle-alert",
  },
};
const style = computed(() => ({
  ...defaults[props.role],
  ...policy.value?.notices?.[props.role],
}));
const iconPolicy = computed(() => ({
  ...policy.value,
  icons: {
    size: 20,
    review: { name: style.value.icon, color: style.value.foreground },
  },
}));
</script>
<template>
  <div
    :role="role === 'error' ? 'alert' : 'status'"
    :data-flow-notice="role"
    class="flex min-w-0 items-start gap-3 rounded-lg border p-4"
    :style="{
      backgroundColor: style.background,
      color: style.foreground,
      borderColor: style.border,
    }"
  >
    <FormFlowStatusIcon role="review" :presentation="iconPolicy as any" />
    <div class="min-w-0 flex-1"><slot /></div>
  </div>
</template>

<style scoped>
[data-flow-notice] :deep(p),
[data-flow-notice] :deep(span) {
  color: inherit;
}
</style>
