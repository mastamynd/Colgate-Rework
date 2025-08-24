<script setup lang="ts">
import { useAppearance } from '@/composables/useAppearance';

const { appearance, updateAppearance } = useAppearance();

const toggleAppearance = () => {
  updateAppearance(appearance.value === 'dark' ? 'light' : 'dark');
};
</script>

<template>
  <button
    @click="toggleAppearance"
    class="relative h-9 w-9 rounded-full bg-gradient-to-b from-sky-200 to-amber-50 transition-all duration-500 dark:from-indigo-900 dark:to-slate-900"
    aria-label="Toggle theme"
  >
    <!-- Horizon line -->
    <div class="absolute inset-x-0 top-1/2 h-px bg-amber-100/50 transition-colors duration-300 dark:bg-slate-700/50" />

    <!-- Animated Sun -->
    <div
      class="absolute inset-0 flex items-center justify-center transition-all duration-700"
      :class="appearance === 'dark' ? 'opacity-0 translate-y-8 rotate-45' : 'opacity-100'"
    >
      <svg
        viewBox="0 0 24 24"
        class="h-8 w-8 fill-amber-400 stroke-amber-500 transition-all duration-300 hover:scale-110"
      >
        <circle cx="12" cy="12" r="4" stroke-width="1.5" />
        <path
          d="M12 3v1.5M12 19.5V21m-8.5-9h1.5m14-7.5h1.5m-15.5 0L5.6 5.6m12.8 12.8 1.4 1.4"
          stroke-width="1"
          stroke-linecap="round"
          class="origin-center animate-pulse"
        />
      </svg>
    </div>

    <!-- Animated Moon -->
    <div
      class="absolute inset-0 flex items-center justify-center transition-all duration-700"
      :class="appearance === 'light' ? 'opacity-0 -translate-y-8 -rotate-45' : 'opacity-100'"
    >
      <svg
        viewBox="0 0 24 24"
        class="h-8 w-8 fill-slate-200 stroke-slate-400 transition-all duration-300 hover:scale-110"
      >
        <path
          d="M20 13.13a8.5 8.5 0 1 1-8.5-8.5c0 1.66.44 3.5 1.5 4.5"
          stroke-width="1.5"
          stroke-linecap="round"
        />
        <circle cx="15" cy="8" r="1" class="animate-pulse fill-current" />
        <circle cx="12" cy="12" r="1" class="animate-pulse fill-current delay-75" />
      </svg>
    </div>

    <!-- Subtle stars for dark mode -->
    <div
      v-if="appearance === 'dark'"
      class="absolute inset-0 animate-fade-in"
    >
      <div
        v-for="(star, index) in 5"
        :key="index"
        class="absolute h-0.5 w-0.5 bg-white rounded-full"
        :class="[
          `top-${Math.random() * 40 + 10}%`,
          `left-${Math.random() * 80 + 10}%`,
          `opacity-${Math.random() * 40 + 60}`
        ]"
      />
    </div>
  </button>
</template>

<style>
@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.3; }
}

.animate-pulse {
  animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

@keyframes fade-in {
  from { opacity: 0; }
  to { opacity: 1; }
}

.animate-fade-in {
  animation: fade-in 0.5s ease-out;
}
</style>