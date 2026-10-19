<script setup lang="ts">
import { computed, provide } from 'vue';
import { formFlowPresentationKey, type FormFlowPresentation } from './formFlowPresentation';

const props = defineProps<{ presentation?: FormFlowPresentation | null }>();
const policy = computed(() => props.presentation ?? null);
provide(formFlowPresentationKey, policy);
</script>

<template>
  <div v-if="policy" class="min-h-svh bg-background text-foreground" :data-form-flow-profile="policy.profile ?? 'custom'" data-testid="form-flow-presentation" :class="policy.actions.placement === 'viewport_bottom' ? 'pb-28' : ''">
    <header class="mx-auto flex w-full max-w-lg items-center gap-3 px-5 pb-1 pt-4" :class="policy.brand.placement === 'center' ? 'justify-center' : 'justify-start'" data-testid="form-flow-presentation-header">
      <img v-if="policy.brand.logo" :src="policy.brand.logo" :alt="policy.brand.name ?? 'Logo'" class="w-auto shrink-0 object-contain" :style="{ height: `${Math.min(policy.brand.logo_height, 48)}px`, maxWidth: `${policy.brand.logo_max_width}px` }" data-testid="form-flow-presentation-logo" />
      <span v-if="policy.brand.name && !policy.brand.logo" class="text-sm font-semibold">{{ policy.brand.name }}</span>
      <span v-if="policy.progress.show && policy.progress.current && policy.progress.total" class="ml-auto text-xs tabular-nums text-muted-foreground" aria-live="polite" data-testid="form-flow-presentation-progress">Step {{ policy.progress.current }} of {{ policy.progress.total }}</span>
    </header>
    <slot />
  </div>
  <slot v-else />
</template>
