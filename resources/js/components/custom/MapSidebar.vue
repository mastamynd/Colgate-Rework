<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useAppearance } from '@/composables/useAppearance'

const { appearance } = useAppearance()

// Props for the sidebar
const props = defineProps({
  isOpen: {
    type: Boolean,
    default: false
  },
  width: {
    type: String,
    default: '30%'
  },
  minWidth: {
    type: String,
    default: '250px'
  }
})

// Emits for parent component communication
const emit = defineEmits(['toggle', 'close'])

// Computed classes for theme-aware styling (moved below for responsive behavior)

// Overlay removed to prevent interference with map interactions

// Computed properties for responsive behavior
const isFullScreen = computed(() => {
  // Check if 30% of screen width is less than 250px
  return windowWidth.value * 0.3 < 250
})

const sidebarWidth = computed(() => {
  return isFullScreen.value ? '100%' : props.width
})

const sidebarClasses = computed(() => {
  const isDark = appearance.value === 'dark' || (appearance.value === 'system' && window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches)
  
  const baseClasses = [
    'absolute top-0 right-0 h-full z-40 transform transition-transform duration-300 ease-in-out overflow-hidden',
    'bg-white dark:bg-gray-900 border-l border-gray-200 dark:border-gray-700 shadow-xl',
    props.isOpen ? 'translate-x-0' : 'translate-x-full'
  ]
  
  // Add full screen classes when in full screen mode
  if (isFullScreen.value) {
    baseClasses.push('border-l-0')
  }
  
  return baseClasses
})

const toggleButtonClasses = computed(() => {
  const isDark = appearance.value === 'dark' || (appearance.value === 'system' && window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches)
  
  return [
    'absolute top-4 right-4 z-50 p-3 rounded-full shadow-lg transition-all duration-200',
    'bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700',
    'hover:bg-gray-50 dark:hover:bg-gray-700 hover:shadow-xl',
    'text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-gray-100'
  ]
})

// Methods
const handleToggle = () => {
  emit('toggle')
}

const handleClose = () => {
  emit('close')
}

// Overlay click handler removed since overlay is no longer used

// Reactive window width for responsive behavior
const windowWidth = ref(window.innerWidth)

// Window resize handler
const handleResize = () => {
  windowWidth.value = window.innerWidth
}

// Lifecycle hooks
onMounted(() => {
  window.addEventListener('resize', handleResize)
})

onUnmounted(() => {
  window.removeEventListener('resize', handleResize)
})
</script>

<template>
  <!-- Toggle Button -->
  <button
    @click="handleToggle"
    :class="toggleButtonClasses"
    :aria-label="isOpen ? 'Close sidebar' : 'Open sidebar'"
  >
    <svg
      v-if="!isOpen"
      xmlns="http://www.w3.org/2000/svg"
      width="24"
      height="24"
      viewBox="0 0 24 24"
      fill="none"
      stroke="currentColor"
      stroke-width="2"
      stroke-linecap="round"
      stroke-linejoin="round"
    >
      <line x1="3" y1="6" x2="21" y2="6"></line>
      <line x1="3" y1="12" x2="21" y2="12"></line>
      <line x1="3" y1="18" x2="21" y2="18"></line>
    </svg>
    <svg
      v-else
      xmlns="http://www.w3.org/2000/svg"
      width="24"
      height="24"
      viewBox="0 0 24 24"
      fill="none"
      stroke="currentColor"
      stroke-width="2"
      stroke-linecap="round"
      stroke-linejoin="round"
    >
      <line x1="18" y1="6" x2="6" y2="18"></line>
      <line x1="6" y1="6" x2="18" y2="18"></line>
    </svg>
  </button>

  <!-- Overlay - Removed to prevent interference with map interactions -->

  <!-- Sidebar -->
  <div
    :class="sidebarClasses"
    :style="{ width: sidebarWidth }"
    class="flex flex-col"
  >
    <!-- Sidebar Header -->
    <div class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-700">
      <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
        Map Controls
      </h2>
      <button
        @click="handleClose"
        class="p-1 rounded-md text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
        aria-label="Close sidebar"
      >
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <line x1="18" y1="6" x2="6" y2="18"></line>
          <line x1="6" y1="6" x2="18" y2="18"></line>
        </svg>
      </button>
    </div>

    <!-- Sidebar Content -->
    <div class="flex-1 overflow-y-auto overflow-x-hidden p-4" style="max-height: calc(100% - 140px);">
      <div class="space-y-6">
        <!-- Map Controls Section -->
        <div>
          <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-3 flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2">
              <circle cx="12" cy="12" r="3"></circle>
              <path d="M12 1v6m0 6v6m11-7h-6m-6 0H1"></path>
            </svg>
            Map Controls
          </h3>
          <div class="space-y-2">
            <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
              <span class="text-sm text-gray-700 dark:text-gray-300">Map Type</span>
              <div class="flex bg-gray-200 dark:bg-gray-700 rounded-lg p-0.5">
                <button class="px-3 py-1 text-xs font-medium rounded transition-colors bg-white dark:bg-gray-600 text-gray-900 dark:text-gray-100 shadow-sm">
                  Roadmap
                </button>
                <button class="px-3 py-1 text-xs font-medium rounded transition-colors text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100">
                  Satellite
                </button>
              </div>
            </div>
            
            <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
              <span class="text-sm text-gray-700 dark:text-gray-300">Heat Map</span>
              <div class="relative inline-flex h-5 w-9 items-center rounded-full bg-gray-200 dark:bg-gray-600 transition-colors">
                <span class="inline-block h-3 w-3 transform rounded-full bg-white transition-transform translate-x-1"></span>
              </div>
            </div>
            
            <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
              <span class="text-sm text-gray-700 dark:text-gray-300">Boundaries</span>
              <div class="relative inline-flex h-5 w-9 items-center rounded-full bg-blue-600 transition-colors">
                <span class="inline-block h-3 w-3 transform rounded-full bg-white transition-transform translate-x-5"></span>
              </div>
            </div>
          </div>
        </div>

        <!-- Marker Layers Section -->
        <div>
          <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-3 flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2">
              <circle cx="12" cy="12" r="10"></circle>
              <circle cx="12" cy="12" r="4"></circle>
            </svg>
            Marker Layers
          </h3>
          <div class="space-y-2">
            <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
              <span class="text-sm text-gray-700 dark:text-gray-300">Customers</span>
              <div class="relative inline-flex h-5 w-9 items-center rounded-full bg-blue-600 transition-colors">
                <span class="inline-block h-3 w-3 transform rounded-full bg-white transition-transform translate-x-5"></span>
              </div>
            </div>
            
            <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
              <span class="text-sm text-gray-700 dark:text-gray-300">Sales Personnel</span>
              <div class="relative inline-flex h-5 w-9 items-center rounded-full bg-blue-600 transition-colors">
                <span class="inline-block h-3 w-3 transform rounded-full bg-white transition-transform translate-x-5"></span>
              </div>
            </div>
          </div>
        </div>

        <!-- Sales Personnel Section -->
        <div>
          <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-3 flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 32 32" class="mr-2">
              <path fill="currentColor" d="M30 6V4h-3V2h-2v2h-1c-1.103 0-2 .898-2 2v2c0 1.103.897 2 2 2h4v2h-6v2h3v2h2v-2h1c1.103 0 2-.897 2-2v-2c0-1.102-.897-2-2-2h-4V6zm-6 14v2h2.586L23 25.586l-2.292-2.293a1 1 0 0 0-.706-.293H20a1 1 0 0 0-.706.293L14 28.586L15.414 30l4.587-4.586l2.292 2.293a1 1 0 0 0 1.414 0L28 23.414V26h2v-6zM4 30H2v-5c0-3.86 3.14-7 7-7h6c1.989 0 3.89.85 5.217 2.333l-1.49 1.334A5 5 0 0 0 15 20H9c-2.757 0-5 2.243-5 5zm8-14a7 7 0 1 0 0-14a7 7 0 0 0 0 14m0-12a5 5 0 1 1 0 10a5 5 0 0 1 0-10"/>
            </svg>
            Sales Personnel
          </h3>
          <div class="space-y-2">
            <div class="flex items-center space-x-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
              <div class="w-3 h-3 rounded-full bg-red-500"></div>
              <div class="flex-1">
                <p class="text-sm font-medium text-gray-900 dark:text-gray-100">Alice Mwangi</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">SP001</p>
              </div>
              <div class="relative inline-flex h-5 w-9 items-center rounded-full bg-blue-600 transition-colors">
                <span class="inline-block h-3 w-3 transform rounded-full bg-white transition-transform translate-x-5"></span>
              </div>
            </div>
            
            <div class="flex items-center space-x-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
              <div class="w-3 h-3 rounded-full bg-teal-500"></div>
              <div class="flex-1">
                <p class="text-sm font-medium text-gray-900 dark:text-gray-100">Brian Otieno</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">SP002</p>
              </div>
              <div class="relative inline-flex h-5 w-9 items-center rounded-full bg-blue-600 transition-colors">
                <span class="inline-block h-3 w-3 transform rounded-full bg-white transition-transform translate-x-5"></span>
              </div>
            </div>
            
            <div class="flex items-center space-x-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
              <div class="w-3 h-3 rounded-full bg-blue-500"></div>
              <div class="flex-1">
                <p class="text-sm font-medium text-gray-900 dark:text-gray-100">Catherine Njeri</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">SP003</p>
              </div>
              <div class="relative inline-flex h-5 w-9 items-center rounded-full bg-blue-600 transition-colors">
                <span class="inline-block h-3 w-3 transform rounded-full bg-white transition-transform translate-x-5"></span>
              </div>
            </div>
            
            <div class="flex items-center space-x-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
              <div class="w-3 h-3 rounded-full bg-green-500"></div>
              <div class="flex-1">
                <p class="text-sm font-medium text-gray-900 dark:text-gray-100">David Mutua</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">SP004</p>
              </div>
              <div class="relative inline-flex h-5 w-9 items-center rounded-full bg-blue-600 transition-colors">
                <span class="inline-block h-3 w-3 transform rounded-full bg-white transition-transform translate-x-5"></span>
              </div>
            </div>
          </div>
        </div>

        <!-- Customer Classification Section -->
        <div>
          <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-3 flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2">
              <path d="M9 12l2 2 4-4"></path>
              <path d="M21 12c-1 0-3-1-3-3s2-3 3-3 3 1 3 3-2 3-3 3"></path>
              <path d="M3 12c1 0 3-1 3-3s-2-3-3-3-3 1-3 3 2 3 3 3"></path>
            </svg>
            Customer Classification
          </h3>
          <div class="flex bg-gray-200 dark:bg-gray-700 rounded-lg p-0.5">
            <button class="flex-1 px-3 py-2 text-xs font-medium rounded transition-colors bg-white dark:bg-gray-600 text-gray-900 dark:text-gray-100 shadow-sm">
              KD
            </button>
            <button class="flex-1 px-3 py-2 text-xs font-medium rounded transition-colors text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100">
              RE
            </button>
          </div>
        </div>

        <!-- Map Information Section -->
        <div>
          <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-3 flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2">
              <circle cx="12" cy="12" r="10"></circle>
              <path d="M12 16v-4"></path>
              <path d="M12 8h.01"></path>
            </svg>
            Map Information
          </h3>
          <div class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
            <div class="flex justify-between">
              <span>Zoom Level:</span>
              <span class="font-medium">15</span>
            </div>
            <div class="flex justify-between">
              <span>Boundaries:</span>
              <span class="font-medium">12</span>
            </div>
            <div class="flex justify-between">
              <span>Customers:</span>
              <span class="font-medium">12</span>
            </div>
            <div class="flex justify-between">
              <span>Sales Personnel:</span>
              <span class="font-medium">4</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Sidebar Footer -->
    <div class="p-4 border-t border-gray-200 dark:border-gray-700">
      <button
        @click="handleClose"
        class="w-full px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors font-medium"
      >
        Close Sidebar
      </button>
    </div>
  </div>
</template>

<style scoped>
/* Custom scrollbar for sidebar content */
.overflow-y-auto::-webkit-scrollbar {
  width: 6px;
}

.overflow-y-auto::-webkit-scrollbar-track {
  background: transparent;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
  background: #d1d5db;
  border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
  background: #9ca3af;
}

/* Dark mode scrollbar */
@media (prefers-color-scheme: dark) {
  .overflow-y-auto::-webkit-scrollbar-thumb {
    background: #4b5563;
  }
  
  .overflow-y-auto::-webkit-scrollbar-thumb:hover {
    background: #6b7280;
  }
}
</style>
