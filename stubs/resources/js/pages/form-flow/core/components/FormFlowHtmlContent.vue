<script setup lang="ts">
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from "vue";

import { useFormFlowPresentation } from "./formFlowPresentation";

const presentation = useFormFlowPresentation();
const props = defineProps<{ html: string }>();
const viewport = ref<HTMLElement | null>(null);
const canvas = ref<HTMLElement | null>(null);
const availableWidth = ref(0);
const canvasHeight = ref(0);
let observer: ResizeObserver | null = null;

// A pixel-sized root declares an artwork canvas. Scale the whole composition,
// including positioned text, rather than shrinking only its image.
const authoredWidth = computed(() => {
  if (typeof document === "undefined") return null;
  const template = document.createElement("template");
  template.innerHTML = props.html;
  if (template.content.children.length !== 1 || !template.content.querySelector("img, svg")) return null;
  const root = template.content.firstElementChild;
  if (!(root instanceof HTMLElement)) return null;
  for (const value of [root.style.width, root.style.maxWidth]) {
    if (/^\d+(?:\.\d+)?px$/.test(value)) {
      const width = Number.parseFloat(value);
      if (width > 0 && width <= 4096) return width;
    }
  }
  return null;
});

const frameRatio = computed(() => {
  const ratio = presentation.value?.layout.artwork_aspect_ratio;
  if (!ratio || ratio === "natural" || !/<(?:img|svg)\b/i.test(props.html)) return null;
  const [width, height] = ratio.split("/").map(Number);
  return width > 0 && height > 0 ? width / height : null;
});
const frameHeight = computed(() => frameRatio.value ? availableWidth.value / frameRatio.value : 0);
const scale = computed(() => {
  const width = authoredWidth.value ?? availableWidth.value;
  if (!width || !availableWidth.value) return 1;
  return Math.min(1, availableWidth.value / width,
    frameRatio.value && canvasHeight.value > 0 ? frameHeight.value / canvasHeight.value : 1);
});
const viewportStyle = computed(() => frameRatio.value
  ? { aspectRatio: String(frameRatio.value) }
  : authoredWidth.value ? { height: `${canvasHeight.value * scale.value}px` } : undefined);
const canvasStyle = computed(() => authoredWidth.value || frameRatio.value ? {
  width: authoredWidth.value ? `${authoredWidth.value}px` : "100%",
  transform: `scale(${scale.value})`,
  transformOrigin: "top left",
  position: frameRatio.value ? "absolute" as const : undefined,
  left: frameRatio.value ? `${(availableWidth.value - (authoredWidth.value ?? availableWidth.value) * scale.value) / 2}px` : undefined,
  top: frameRatio.value ? `${(frameHeight.value - canvasHeight.value * scale.value) / 2}px` : undefined,
  visibility: availableWidth.value > 0 ? "visible" as const : "hidden" as const,
} : undefined);

function measure() {
  availableWidth.value = viewport.value?.clientWidth ?? 0;
  canvasHeight.value = canvas.value?.offsetHeight ?? 0;
}

onMounted(() => {
  observer = new ResizeObserver(measure);
  if (viewport.value) observer.observe(viewport.value);
  if (canvas.value) observer.observe(canvas.value);
  measure();
});
watch(() => props.html, async () => { await nextTick(); measure(); });
onUnmounted(() => observer?.disconnect());
</script>

<template>
  <div ref="viewport" class="form-flow-html-viewport" :style="viewportStyle" data-testid="form-flow-html-content">
    <div ref="canvas" class="form-flow-html-canvas" :style="canvasStyle" v-html="html" />
  </div>
</template>

<style scoped>
.form-flow-html-viewport {
  position: relative;
  width: 100%;
  min-width: 0;
}
.form-flow-html-canvas {
  display: flow-root;
  width: 100%;
  margin-inline: auto;
}
.form-flow-html-canvas :deep(img),
.form-flow-html-canvas :deep(svg) {
  max-width: 100%;
  height: auto;
}
</style>
