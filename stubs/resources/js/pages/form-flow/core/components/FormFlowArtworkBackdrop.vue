<script setup lang="ts">
import type { FormFlowPresentation } from './formFlowPresentation';
defineProps<{ src: string; settings: NonNullable<FormFlowPresentation['artwork']>['background'] }>();
</script>
<template>
  <div class="artwork-background" aria-hidden="true" data-testid="artwork-background">
    <img :src="src" alt="" class="artwork-backdrop" :style="{opacity:settings.opacity,filter:`blur(${settings.blur}px)`}" />
    <div class="artwork-overlay" :style="{opacity:settings.overlay}" />
  </div>
</template>
<style scoped>
.artwork-background { position:absolute; inset:0; z-index:-1; overflow:hidden; pointer-events:none; }
.artwork-backdrop { position:absolute; inset:-5%; width:110%; height:110%; max-width:none; object-fit:cover; }
.artwork-overlay { position:absolute; inset:0; background:linear-gradient(to bottom, transparent, var(--background, #fff) 85%); }
</style>
