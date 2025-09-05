<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { 
  DropdownMenu, 
  DropdownMenuContent, 
  DropdownMenuTrigger 
} from '@/components/ui/dropdown-menu';
import { PaletteIcon, CheckIcon } from 'lucide-vue-next';

const props = defineProps({
  modelValue: {
    type: String,
    default: '#3B82F6'
  },
  disabled: {
    type: Boolean,
    default: false
  }
});

const emit = defineEmits(['update:modelValue']);

const isOpen = ref(false);

// Predefined color palette
const colorPalette = [
  '#3B82F6', // Blue
  '#10B981', // Emerald
  '#F59E0B', // Amber
  '#EF4444', // Red
  '#8B5CF6', // Violet
  '#06B6D4', // Cyan
  '#84CC16', // Lime
  '#F97316', // Orange
  '#EC4899', // Pink
  '#6B7280', // Gray
  '#14B8A6', // Teal
  '#A855F7', // Purple
  '#22C55E', // Green
  '#F43F5E', // Rose
  '#0EA5E9', // Sky
  '#6366F1', // Indigo
];

const selectedColor = ref(props.modelValue);

watch(() => props.modelValue, (newValue) => {
  selectedColor.value = newValue;
});

const selectColor = (color: string) => {
  selectedColor.value = color;
  emit('update:modelValue', color);
  isOpen.value = false;
};

const isSelected = (color: string) => {
  return selectedColor.value === color;
};

const colorStyle = computed(() => ({
  backgroundColor: selectedColor.value,
}));
</script>

<template>
  <DropdownMenu v-model:open="isOpen">
    <DropdownMenuTrigger as-child>
      <Button
        variant="outline"
        :disabled="disabled"
        class="w-full justify-start gap-2 h-10"
      >
        <div 
          class="w-4 h-4 rounded border border-gray-300"
          :style="colorStyle"
        />
        <span class="text-sm">{{ selectedColor }}</span>
        <PaletteIcon class="h-4 w-4 ml-auto" />
      </Button>
    </DropdownMenuTrigger>
    <DropdownMenuContent class="w-80 p-4">
      <div class="space-y-4">
        <div>
          <h4 class="font-medium text-sm mb-2">Choose a color</h4>
          <div class="grid grid-cols-8 gap-2">
            <button
              v-for="color in colorPalette"
              :key="color"
              @click="selectColor(color)"
              class="w-8 h-8 rounded border-2 transition-all hover:scale-110 focus:outline-none focus:ring-2 focus:ring-offset-2"
              :class="[
                isSelected(color) 
                  ? 'border-gray-900 ring-2 ring-offset-1' 
                  : 'border-gray-300 hover:border-gray-400'
              ]"
              :style="{ backgroundColor: color }"
              :aria-label="`Select color ${color}`"
            >
              <CheckIcon 
                v-if="isSelected(color)" 
                class="w-4 h-4 text-white drop-shadow-sm"
              />
            </button>
          </div>
        </div>
        
        <div class="space-y-2">
          <label class="text-sm font-medium">Custom color</label>
          <div class="flex gap-2">
            <input
              type="color"
              :value="selectedColor"
              @input="selectColor(($event.target as HTMLInputElement).value)"
              class="w-12 h-10 rounded border border-gray-300 cursor-pointer"
              :disabled="disabled"
            />
            <input
              type="text"
              :value="selectedColor"
              @input="selectColor(($event.target as HTMLInputElement).value)"
              class="flex-1 px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              placeholder="#000000"
              :disabled="disabled"
            />
          </div>
        </div>
      </div>
    </DropdownMenuContent>
  </DropdownMenu>
</template>
