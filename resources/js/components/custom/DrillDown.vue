<script setup>
import Dropdown from './Dropdown.vue'
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';

const { counties, constituencies, wards } = defineProps({
	counties: {
		type: Array,
		default: () => []
	},
	constituencies: {
		type: Array,
		default: () => []
	},
	wards: {
		type: Array,
		default: () => []
	},
	modelValue: {
		type: Array,
		default: () => []
	}
});

const selectedCounty = ref(null);
const selectedConstituency = ref(null);
const selectedWard = ref(null);
const boundariesVisible = ref(false);

const toggleBoundaries = () => {
	boundariesVisible.value = !boundariesVisible.value;
};

const emit = defineEmits(['update:modelValue']);

const updateUrl = () => {
	const params = new URLSearchParams();
	
	if (selectedCounty.value) {
		params.set('boundary_type', 'county');
		params.set('boundary_id', selectedCounty.value.id);
	}
	
	if (selectedConstituency.value) {
		params.set('boundary_type', 'constituency');
		params.set('boundary_id', selectedConstituency.value.id);
	}
	
	if (selectedWard.value) {
		params.set('boundary_type', 'ward');
		params.set('boundary_id', selectedWard.value.id);
	}
	
	// If no selections, clear the params
	if (!selectedCounty.value && !selectedConstituency.value && !selectedWard.value) {
		// Navigate to base URL without query params
		router.get(window.location.pathname);
		return;
	}
	
	// Navigate with the new query parameters
	router.get(`${window.location.pathname}?${params.toString()}`, {}, {
		preserveState: true,
		preserveScroll: true,
		replace: true
	});
};

watch(selectedCounty, (newCounty) => {
	selectedConstituency.value = null;
	selectedWard.value = null;
	updateUrl();
});

watch(selectedConstituency, (newConstituency) => {
	selectedWard.value = null;
	updateUrl();
});

watch(selectedWard, () => {
	updateUrl();
});
</script>

<template>
	<div class="flex gap-1.5 rounded-lg bg-white dark:bg-slate-800 text-slate-800 dark:text-white py-1.5 px-3 shadow-lg">
		<div class="flex gap-2 items-center">
			<div 
				class="flex flex-col p-2 cursor-pointer transition-all duration-300"
				:class="{
					'text-primary drop-shadow-[0_0_8px_rgba(59,130,246,0.8)]': boundariesVisible,
					'hover:text-primary/90': !boundariesVisible,
					'opacity-50': !boundariesVisible
				}"
				@click="toggleBoundaries"
			>
				<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><!-- Icon from Solar by 480 Design - https://creativecommons.org/licenses/by/4.0/ --><g fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" d="M4.979 6.315C2.993 7.109 2 7.506 2 8s.993.89 2.979 1.685l2.808 1.124C9.773 11.603 10.767 12 12 12s2.227-.397 4.213-1.191l2.808-1.124C21.007 8.891 22 8.494 22 8s-.993-.89-2.979-1.685l-2.808-1.123C14.227 4.397 13.233 4 12 4c-.954 0-1.764.237-3 .712"/><path d="m5.766 10l-.787.315C2.993 11.109 2 11.507 2 12s.993.89 2.979 1.685l2.808 1.124C9.773 15.603 10.767 16 12 16s2.227-.397 4.213-1.191l2.808-1.124C21.007 12.891 22 12.493 22 12s-.993-.89-2.979-1.685L18.234 10"/><path stroke-linecap="round" d="M19.021 17.685C21.007 16.891 22 16.494 22 16c0-.493-.993-.89-2.979-1.685L18.234 14M5.766 14l-.787.315C2.993 15.109 2 15.507 2 16s.993.89 2.979 1.685l2.808 1.124C9.773 19.603 10.767 20 12 20c.954 0 1.764-.237 3-.712"/></g></svg>
			</div>
			<div class="text-sm">
				<Dropdown :options="counties" v-model="selectedCounty" placeholder="All Counties" />
			</div>
			<div class="text-sm">
				<Dropdown :options="constituencies" v-model="selectedConstituency" :disabled="!selectedCounty" placeholder="All Constituencies" />
			</div>
			<div class="text-sm">
				<Dropdown :options="wards" v-model="selectedWard" :disabled="!selectedConstituency || !selectedCounty" placeholder="All Wards" :alignRight="true" />
			</div>
		</div>
	</div>
</template>