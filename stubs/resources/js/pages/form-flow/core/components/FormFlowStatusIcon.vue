<script setup lang="ts">
import { computed } from 'vue';
import { ReceiptText, ClipboardCheck, Clock3, CheckCircle2, AlertTriangle, AlertCircle, ShieldCheck } from 'lucide-vue-next';
import { useFormFlowPresentation, type FormFlowPresentation, type ClaimIconRole, type ClaimIconName } from './formFlowPresentation';
const props = defineProps<{ role: ClaimIconRole; presentation?: FormFlowPresentation | null }>();
const inherited = useFormFlowPresentation();
const fallback = { review: 'receipt', pending: 'clock', success: 'circle-check', attention: 'triangle-alert', error: 'circle-alert' } as const;
const colors = { review: 'foreground', pending: '#d97706', success: '#16a34a', attention: '#d97706', error: '#dc2626' };
const registry = { receipt: ReceiptText, 'clipboard-check': ClipboardCheck, clock: Clock3, 'circle-check': CheckCircle2, 'triangle-alert': AlertTriangle, 'circle-alert': AlertCircle, 'shield-check': ShieldCheck };
const policy = computed(() => props.presentation ?? inherited.value);
const icon = computed(() => policy.value?.icons?.[props.role] ?? { name: fallback[props.role], color: colors[props.role] });
const component = computed(() => registry[icon.value.name as ClaimIconName] ?? registry[fallback[props.role]]);
const style = computed(() => ({ width: `${policy.value?.icons?.size ?? 48}px`, height: `${policy.value?.icons?.size ?? 48}px`, color: icon.value.color === 'foreground' ? 'var(--foreground)' : icon.value.color }));
</script>
<template>
  <component :is="component" :style="style" class="mx-auto shrink-0" aria-hidden="true" :data-claim-icon-role="role" :data-claim-icon-name="icon.name" />
</template>
