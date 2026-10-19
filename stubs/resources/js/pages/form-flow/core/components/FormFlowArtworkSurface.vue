<script setup lang="ts">
import { computed, inject, watch, onUnmounted } from 'vue';
import { useFormFlowPresentation, formFlowArtworkKey, type FormFlowPresentation } from './formFlowPresentation';

import FormFlowArtworkBackdrop from './FormFlowArtworkBackdrop.vue';
const pageArtwork = inject(formFlowArtworkKey, null);
const props = defineProps<{ artworkSrc?: string | null; presentation?: FormFlowPresentation | null }>();
const inherited = useFormFlowPresentation();
const policy = computed(() => props.presentation ?? inherited.value);
const background = computed(() => policy.value?.artwork?.background ?? { enabled:false, opacity:0.45, blur:18, overlay:0.65 });
const foreground = computed(() => policy.value?.artwork?.foreground ?? {position:'top',top_spacing:24,gap:20,padding:20,max_width:672});
const source = computed(() => {
  const value = props.artworkSrc?.trim();
  if (!value) return null;
  try {
    const url = new URL(value, 'https://artwork.invalid');
    return ['http:', 'https:'].includes(url.protocol) || /^data:image\/(png|jpeg|webp|gif);base64,/i.test(value) ? value : null;
  } catch { return null; }
});
watch(source, value => pageArtwork?.setSource(value), {immediate:true});
onUnmounted(() => pageArtwork?.setSource(null));
</script>

<template>
  <section class="artwork-surface" data-testid="form-flow-artwork-surface">
    <FormFlowArtworkBackdrop v-if="source && background.enabled && !pageArtwork" :src="source" :settings="background" />
    <div class="artwork-content" :style="{padding:`${foreground.padding}px`,paddingTop:`${foreground.top_spacing}px`,justifyContent:foreground.position === 'center' ? 'center' : 'flex-start'}">
      <div class="artwork-foreground" :style="{maxWidth:`${foreground.max_width}px`,gap:`${foreground.gap}px`}" data-testid="artwork-foreground"><slot /></div>
    </div>
    <div v-if="$slots.footer" class="artwork-footer" :style="{padding:`0 ${foreground.padding}px ${foreground.padding}px`}"><slot name="footer" /></div>
  </section>
</template>

<style scoped>
.artwork-surface { position:relative; isolation:isolate; display:flex; flex-direction:column; width:100%; min-width:0; flex:1; }
.artwork-content { display:flex; flex:1; min-width:0; flex-direction:column; align-items:center; }
.artwork-foreground { display:flex; flex-direction:column; width:100%; min-width:0; }
.artwork-footer { position:relative; display:grid; gap:12px; margin-top:auto; }
</style>
