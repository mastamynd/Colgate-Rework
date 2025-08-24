<script setup>
import { ref, watch, computed } from 'vue'

const props = defineProps({
  options: {
    type: Array,
    required: true,
    default: () => []
  },
  modelValue: {
    type: [String, Number, Object, null],
    default: null
  },
  placeholder: {
    type: String,
    default: 'Select an option'
  },
  searchable: {
    type: Boolean,
    default: true
  },
  disabled: {
    type: Boolean,
    default: false
  },
  alignRight: {
    type: Boolean,
    default: false
  },
  clearable: {
    type: Boolean,
    default: true
  }
})

const emit = defineEmits(['update:modelValue'])

const isOpen = ref(false);
const selected = ref(props.modelValue)
const searchQuery = ref('')

watch(() => props.modelValue, (val) => {
  selected.value = val
})

const selectOption = (option) => {
  if (props.disabled) return
  selected.value = option
  emit('update:modelValue', option)
  isOpen.value = false;
  searchQuery.value = ''
}

const clearSelection = (event) => {
  event.stopPropagation()
  if (props.disabled) return
  selected.value = null
  emit('update:modelValue', null)
}

const displayValue = computed(() => {
  if (!selected.value) return props.placeholder
  if (typeof selected.value === 'object' && selected.value.name) return selected.value.name
  return selected.value
})

const filteredOptions = computed(() => {
  if (!searchQuery.value) return props.options
  return props.options.filter(option => 
    option.name.toLowerCase().includes(searchQuery.value.toLowerCase())
  )
})

const handleClickOutside = (event) => {
  if (!event.target.closest('.custom-dropdown')) {
    isOpen.value = false
    searchQuery.value = ''
  }
}

const toggleDropdown = () => {
  if (props.disabled) return
  isOpen.value = !isOpen.value;
	console.log(isOpen.value ? "YES" : "NO")
  if (isOpen.value && props.searchable) {
    // Focus search input after dropdown opens
    setTimeout(() => {
      const searchInput = document.querySelector('.dropdown-search')
      if (searchInput) searchInput.focus()
    }, 50)
  }
}

watch(isOpen, (open) => {
  if (open) {
    document.addEventListener('click', handleClickOutside)
  } else {
    document.removeEventListener('click', handleClickOutside)
    searchQuery.value = ''
  }
})
</script>

<template>
  <div class="relative w-full custom-dropdown" tabindex="0">
    <button
      class="w-full flex items-center justify-between px-4 py-2 rounded-sm shadow focus:outline-none transition"
      :class="{
        'bg-slate-800 dark:bg-slate-700 text-white focus:ring-2 focus:ring-blue-400 dark:focus:ring-blue-500': !disabled,
        'bg-slate-300 dark:bg-slate-600 text-slate-500 dark:text-slate-400 cursor-not-allowed': disabled
      }"
      @click="toggleDropdown"
      :aria-expanded="isOpen"
      :disabled="disabled"
      type="button"
    >
      <span class="truncate">{{ displayValue }}</span>
      <div class="flex items-center ml-2">
        <button
          v-if="clearable && selected && !disabled"
          @click="clearSelection"
          class="mr-1 p-1 hover:bg-slate-600 dark:hover:bg-slate-600 rounded-full transition"
          type="button"
          aria-label="Clear selection"
        >
          <svg 
            class="w-3 h-3" 
            fill="none" 
            stroke="currentColor" 
            stroke-width="2" 
            viewBox="0 0 24 24"
          >
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
        <svg 
          class="w-4 h-4 transition-transform" 
          :class="{ 
            'rotate-180': isOpen && !disabled,
            'opacity-50': disabled
          }" 
          fill="none" 
          stroke="currentColor" 
          stroke-width="2" 
          viewBox="0 0 24 24"
        >
          <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
        </svg>
      </div>
    </button>
    <transition name="fade">
      <div
        v-if="isOpen && !disabled"
        class="absolute z-10 mt-2 w-full min-w-[270px] bg-white dark:bg-slate-800 rounded-b-lg shadow-lg border border-slate-200 dark:border-slate-800 max-h-60 overflow-hidden"
        :class="{
          'left-0': !alignRight,
          'right-0': alignRight
        }"
      >
        <div v-if="searchable" class="p-2 border-b border-slate-200 dark:border-slate-600">
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Search options..."
            class="dropdown-search w-full px-3 py-2 text-sm bg-slate-50 dark:bg-slate-700 text-slate-800 dark:text-slate-200 border border-slate-200 dark:border-slate-600 rounded-sm focus:outline-none focus:ring-2 focus:ring-blue-400 dark:focus:ring-blue-500"
            @click.stop
          />
        </div>
        <ul class="max-h-48 overflow-auto">
          <li
            v-for="option in filteredOptions"
            :key="option.id"
            @click="selectOption(option)"
            class="px-4 py-2 cursor-pointer hover:bg-blue-100 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 transition"
            :class="{ 'bg-blue-50 dark:bg-slate-600 font-semibold': selected && selected.id === option.id }"
          >
            {{ option.name }}
          </li>
          <li v-if="!filteredOptions.length" class="px-4 py-2 text-slate-400 dark:text-slate-500">
            {{ searchQuery ? 'No matching options' : 'No options' }}
          </li>
        </ul>
      </div>
    </transition>
  </div>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active {
  transition: opacity 0.15s;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
}
</style>
