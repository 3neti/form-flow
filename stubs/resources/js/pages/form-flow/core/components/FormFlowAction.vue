<script setup lang="ts">
import { computed } from "vue";
import { Button } from "@/components/ui/button";
import {
  useFormFlowPresentation,
  actionLabel,
  type ActionRole,
} from "./formFlowPresentation";
const props = withDefaults(
  defineProps<{
    role: ActionRole;
    label: string;
    processing?: boolean;
    processingLabel?: string;
    secondary?: boolean;
  }>(),
  { processing: false, processingLabel: "Please wait...", secondary: false },
);
const policy = useFormFlowPresentation();
const label = computed(() =>
  actionLabel(
    policy.value,
    props.role,
    props.processing ? props.processingLabel : props.label,
    props.processing,
    props.secondary,
  ),
);
</script>
<template>
  <Button :data-flow-action="role"><slot name="icon" />{{ label }}</Button>
</template>
