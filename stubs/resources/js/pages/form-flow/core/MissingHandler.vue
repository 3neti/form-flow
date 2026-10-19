<script setup lang="ts">
import FormFlowScreen from "./components/FormFlowScreen.vue";
import FormFlowActions from "./components/FormFlowActions.vue";
import FormFlowAction from "./components/FormFlowAction.vue";
import FormFlowStatusIcon from "./components/FormFlowStatusIcon.vue";
import FormFlowNotice from "./components/FormFlowNotice.vue";
import FormFlowPresentation from "./components/FormFlowPresentation.vue";
import type { FormFlowPresentation as PresentationPolicy } from "./components/formFlowPresentation";
import { router } from "@inertiajs/vue3";

const props = defineProps<{
  presentation?: PresentationPolicy | null;
  flow_id: string;
  step_index: number;
  handler_name: string;
  handler_title: string;
  install_hint: string;
  is_production: boolean;
  can_skip: boolean;
  preview_mode?: boolean;
}>();

const handleSkip = () => {
  if (props.preview_mode) return;
  console.log("[MissingHandler] Skipping step", {
    flow_id: props.flow_id,
    step_index: props.step_index,
    handler_name: props.handler_name,
  });

  // Submit minimal data to skip this step (must have at least one field)
  router.post(
    `/form-flow/${props.flow_id}/step/${props.step_index}`,
    {
      data: {
        _skipped: true,
      },
    },
    {
      onSuccess: () => {
        console.log("[MissingHandler] Skip successful");
      },
      onError: (errors) => {
        console.error("[MissingHandler] Skip failed", errors);
        alert("Error: " + JSON.stringify(errors));
      },
    },
  );
};

const handleGoBack = () => {
  if (props.preview_mode) return;
  router.visit("/disburse");
};
</script>

<template>
  <FormFlowPresentation
    :presentation="presentation"
    :screen="presentation?.screen ?? 'missing_handler'"
  >
    <FormFlowScreen
      :title="
        is_production ? 'Step Unavailable' : `Handler Missing: ${handler_name}`
      "
    >
      <FormFlowStatusIcon :role="is_production ? 'error' : 'attention'" />
      <p class="my-4 text-sm text-muted-foreground">
        This flow requires <strong>{{ handler_title }}</strong
        >, but this step is unavailable.
      </p>
      <FormFlowNotice v-if="is_production" role="error"
        >Reference: HANDLER-{{
          handler_name.toUpperCase()
        }}-MISSING</FormFlowNotice
      >
      <FormFlowNotice v-else role="attention">
        <p>The handler is not installed.</p>
        <pre class="mt-2 whitespace-pre-wrap break-words text-xs">{{
          install_hint
        }}</pre>
      </FormFlowNotice>
      <FormFlowActions :show-secondary="!is_production && can_skip">
        <FormFlowAction
          role="back"
          label="Go Back"
          type="button"
          variant="outline"
          @click="handleGoBack"
        />
        <FormFlowAction
          v-if="!is_production && can_skip"
          role="continue"
          label="Continue Anyway"
          type="button"
          @click="handleSkip"
        />
      </FormFlowActions>
      <p class="mt-4 text-sm text-muted-foreground">
        Please contact support if this issue persists.
      </p>
    </FormFlowScreen>
  </FormFlowPresentation>
</template>
