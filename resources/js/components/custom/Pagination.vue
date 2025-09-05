<script setup lang="ts">
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import { ChevronLeftIcon, ChevronRightIcon } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'

interface PaginationLink {
  url: string | null
  label: string
  active: boolean
}

interface PaginationData {
  data: any[]
  current_page: number
  first_page_url: string
  from: number | null
  last_page: number
  last_page_url: string
  links: PaginationLink[]
  next_page_url: string | null
  path: string
  per_page: number
  prev_page_url: string | null
  to: number | null
  total: number
}

const props = defineProps<{
  paginationData: PaginationData
}>()

const visiblePages = computed(() => {
  const current = props.paginationData.current_page
  const last = props.paginationData.last_page
  const delta = 2
  const range = []
  const rangeWithDots = []

  for (let i = Math.max(2, current - delta); i <= Math.min(last - 1, current + delta); i++) {
    range.push(i)
  }

  if (current - delta > 2) {
    rangeWithDots.push(1, '...')
  } else {
    rangeWithDots.push(1)
  }

  rangeWithDots.push(...range)

  if (current + delta < last - 1) {
    rangeWithDots.push('...', last)
  } else if (last > 1) {
    rangeWithDots.push(last)
  }

  return rangeWithDots
})

const hasPages = computed(() => props.paginationData.last_page > 1)
const hasPrevious = computed(() => props.paginationData.prev_page_url !== null)
const hasNext = computed(() => props.paginationData.next_page_url !== null)
</script>

<template>
  <div v-if="hasPages" class="flex items-center justify-between px-4 py-3 bg-white dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700 sm:px-6">
    <!-- Mobile pagination info -->
    <div class="flex justify-between flex-1 sm:hidden">
                <Link
            v-if="hasPrevious"
            :href="paginationData.prev_page_url || '#'"
            class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700"
          >
            Previous
          </Link>
      <Link
        v-if="hasNext"
        :href="paginationData.next_page_url || '#'"
        class="relative ml-3 inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700"
      >
        Next
      </Link>
    </div>

    <!-- Desktop pagination -->
    <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
      <div>
        <p class="text-sm text-gray-700 dark:text-gray-300">
          Showing
          <span class="font-medium">{{ paginationData.from || 0 }}</span>
          to
          <span class="font-medium">{{ paginationData.to || 0 }}</span>
          of
          <span class="font-medium">{{ paginationData.total }}</span>
          results
        </p>
      </div>
      <div>
        <nav class="isolate inline-flex -space-x-px rounded-md shadow-sm" aria-label="Pagination">
          <!-- Previous button -->
          <Link
            v-if="hasPrevious"
            :href="paginationData.prev_page_url || '#'"
            class="relative inline-flex items-center rounded-l-md px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 dark:ring-gray-600 hover:bg-gray-50 dark:hover:bg-gray-800 focus:z-20 focus:outline-offset-0"
          >
            <span class="sr-only">Previous</span>
            <ChevronLeftIcon class="h-5 w-5" aria-hidden="true" />
          </Link>
          <span
            v-else
            class="relative inline-flex items-center rounded-l-md px-2 py-2 text-gray-300 dark:text-gray-600 ring-1 ring-inset ring-gray-300 dark:ring-gray-600 bg-gray-50 dark:bg-gray-800 cursor-not-allowed"
          >
            <span class="sr-only">Previous</span>
            <ChevronLeftIcon class="h-5 w-5" aria-hidden="true" />
          </span>

          <!-- Page numbers -->
          <template v-for="(page, index) in visiblePages" :key="index">
            <Link
              v-if="page !== '...'"
              :href="paginationData.links.find(link => link.label === page.toString())?.url || '#'"
              class="relative inline-flex items-center px-4 py-2 text-sm font-semibold ring-1 ring-inset ring-gray-300 dark:ring-gray-600 hover:bg-gray-50 dark:hover:bg-gray-800 focus:z-20 focus:outline-offset-0"
              :class="{
                'z-10 bg-red-600 text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600': paginationData.current_page === page,
                'text-gray-900 dark:text-gray-100': paginationData.current_page !== page
              }"
            >
              {{ page }}
            </Link>
            <span
              v-else
              class="relative inline-flex items-center px-4 py-2 text-sm font-semibold text-gray-700 dark:text-gray-300 ring-1 ring-inset ring-gray-300 dark:ring-gray-600"
            >
              ...
            </span>
          </template>

          <!-- Next button -->
          <Link
            v-if="hasNext"
            :href="paginationData.next_page_url || '#'"
            class="relative inline-flex items-center rounded-r-md px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 dark:ring-gray-600 hover:bg-gray-50 dark:hover:bg-gray-800 focus:z-20 focus:outline-offset-0"
          >
            <span class="sr-only">Next</span>
            <ChevronRightIcon class="h-5 w-5" aria-hidden="true" />
          </Link>
          <span
            v-else
            class="relative inline-flex items-center rounded-r-md px-2 py-2 text-gray-300 dark:text-gray-600 ring-1 ring-inset ring-gray-300 dark:ring-gray-600 bg-gray-50 dark:bg-gray-800 cursor-not-allowed"
          >
            <span class="sr-only">Next</span>
            <ChevronRightIcon class="h-5 w-5" aria-hidden="true" />
          </span>
        </nav>
      </div>
    </div>
  </div>
</template>
