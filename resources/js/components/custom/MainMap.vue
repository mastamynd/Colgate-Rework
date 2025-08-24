<script setup>
import { useGeolocation } from '@vueuse/core'
import { GoogleMap, Marker } from 'vue3-google-map'
import DrillDown from './DrillDown.vue'
import { ref, watchEffect, computed } from 'vue'
import { useAppearance } from '@/composables/useAppearance'
// import { fetchBoundaries } from '@/composables/useFetchBoundaries';

const { api_key, counties, constituencies, wards, boundaries } = defineProps({
	api_key: {
		type: String,
		default: ''
	},
	mapHeight: {
		type: String,
		default: '900px'
	},
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
	boundaries: {
		type: Promise,
		default: () => Promise.resolve([])
	}
});

const { coords, locatedAt, error, resume, pause } = useGeolocation();
const { appearance } = useAppearance();

const userLocation = ref({
	lat: -1.194438642926344,
	lng: 36.928756667223446
});

const zoom = ref(15);
const mapTypeId = ref('roadmap');
const showHeatMap = ref(false);
const showMarkers = ref(true);
const showBoundaries = ref(true);
const boundariesList = ref([]);

// Dark theme map styles with dark background and red hue
const darkMapStyles = [
	{ "elementType": "geometry", "stylers": [{ "color": "#1a1a1a" }] },
	{ "elementType": "labels.icon", "stylers": [{ "visibility": "off" }] },
	{ "elementType": "labels.text.fill", "stylers": [{ "color": "#8a8a8a" }] },
	{ "elementType": "labels.text.stroke", "stylers": [{ "color": "#1a1a1a" }] },
	{ "featureType": "administrative", "elementType": "geometry", "stylers": [{ "color": "#666666" }] },
	{ "featureType": "administrative.country", "elementType": "labels.text.fill", "stylers": [{ "color": "#d63031" }] },
	{ "featureType": "administrative.locality", "elementType": "labels.text.fill", "stylers": [{ "color": "#c9b2a6" }] },
	{ "featureType": "poi", "elementType": "labels.text.fill", "stylers": [{ "color": "#d59563" }] },
	{ "featureType": "poi.park", "elementType": "geometry", "stylers": [{ "color": "#2e2e2e" }] },
	{ "featureType": "poi.park", "elementType": "labels.text.fill", "stylers": [{ "color": "#6b9a76" }] },
	{ "featureType": "road", "elementType": "geometry.fill", "stylers": [{ "color": "#2c2c2c" }] },
	{ "featureType": "road", "elementType": "labels.text.fill", "stylers": [{ "color": "#8a8a8a" }] },
	{ "featureType": "road.arterial", "elementType": "geometry", "stylers": [{ "color": "#373737" }] },
	{ "featureType": "road.highway", "elementType": "geometry", "stylers": [{ "color": "#3c3c3c" }] },
	{ "featureType": "road.highway.controlled_access", "elementType": "geometry", "stylers": [{ "color": "#4e4e4e" }] },
	{ "featureType": "road.local", "elementType": "labels.text.fill", "stylers": [{ "color": "#616161" }] },
	{ "featureType": "transit", "elementType": "labels.text.fill", "stylers": [{ "color": "#757575" }] },
	{ "featureType": "water", "elementType": "geometry", "stylers": [{ "color": "#0f0f0f" }] },
	{ "featureType": "water", "elementType": "labels.text.fill", "stylers": [{ "color": "#3d3d3d" }] },
	{ "stylers": [{ "hue": "#dc2626" }] }
];

// Light theme map styles with light background and light red hue
const lightMapStyles = [
	{ "elementType": "geometry", "stylers": [{ "color": "#f5f5f5" }] },
	{ "elementType": "labels.icon", "stylers": [{ "visibility": "off" }] },
	{ "elementType": "labels.text.fill", "stylers": [{ "color": "#616161" }] },
	{ "elementType": "labels.text.stroke", "stylers": [{ "color": "#f5f5f5" }] },
	{ "featureType": "administrative", "elementType": "geometry", "stylers": [{ "lightness": 35 }] },
	{ "featureType": "administrative.country", "elementType": "labels.text.fill", "stylers": [{ "color": "#dc2626" }] },
	{ "featureType": "administrative.locality", "elementType": "labels.text.fill", "stylers": [{ "color": "#757575" }] },
	{ "featureType": "poi", "elementType": "labels.text.fill", "stylers": [{ "color": "#757575" }] },
	{ "featureType": "poi.park", "elementType": "geometry", "stylers": [{ "color": "#e8f5e8" }] },
	{ "featureType": "poi.park", "elementType": "labels.text.fill", "stylers": [{ "color": "#4caf50" }] },
	{ "featureType": "road", "elementType": "geometry.fill", "stylers": [{ "color": "#ffffff" }] },
	{ "featureType": "road", "elementType": "labels.text.fill", "stylers": [{ "color": "#757575" }] },
	{ "featureType": "road.arterial", "elementType": "geometry", "stylers": [{ "color": "#fafafa" }] },
	{ "featureType": "road.highway", "elementType": "geometry", "stylers": [{ "color": "#dadada" }] },
	{ "featureType": "road.highway.controlled_access", "elementType": "geometry", "stylers": [{ "color": "#e0e0e0" }] },
	{ "featureType": "road.local", "elementType": "labels.text.fill", "stylers": [{ "color": "#9e9e9e" }] },
	{ "featureType": "transit", "elementType": "labels.text.fill", "stylers": [{ "color": "#757575" }] },
	{ "featureType": "water", "elementType": "geometry", "stylers": [{ "color": "#c9c9c9" }] },
	{ "featureType": "water", "elementType": "labels.text.fill", "stylers": [{ "color": "#9e9e9e" }] },
	{ "stylers": [{ "hue": "#fecaca" }, { "saturation": 20 }] }
];

// Computed property to switch between dark/light map styles based on current theme
const currentMapStyles = computed(() => {
	// Handle system theme preference
	if (appearance.value === 'system') {
		const isDarkMode = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
		return isDarkMode ? darkMapStyles : lightMapStyles;
	}
	
	// Handle explicitly set theme
	return appearance.value === 'dark' ? darkMapStyles : lightMapStyles;
});

watchEffect(() => {
	if (coords.value?.latitude && coords.value?.longitude) {
		userLocation.value = {
			lat: coords.value.latitude,
			lng: coords.value.longitude
		}
	}
});


const zoomIn = () => zoom.value = Math.min(22, zoom.value + 1);
const zoomOut = () => zoom.value = Math.max(0, zoom.value - 1);
const toggleMapType = () => {
	mapTypeId.value = mapTypeId.value === 'roadmap' ? 'satellite' : 'roadmap';
};

const processedBoundaries = computed(() => {
	if (!boundaries || !Array.isArray(boundaries)) return [];
	
	return boundaries.map(boundary => {
		let geometry = JSON.parse(boundary.geometry);
		let path = [];
		
		// Handle FeatureCollection format
		if (geometry.type === 'FeatureCollection' && geometry.features && geometry.features.length > 0) {
			const feature = geometry.features[0];
			const featureGeometry = feature.geometry;
			
			if (featureGeometry.type === 'Polygon') {
				// For Polygon, coordinates is an array of linear rings
				// First ring is exterior, subsequent rings are holes
				const exteriorRing = featureGeometry.coordinates[0];
				path = exteriorRing.map(coord => ({
					lat: coord[1], // latitude is second element
					lng: coord[0]  // longitude is first element
				}));
			} else if (featureGeometry.type === 'MultiPolygon') {
				// For MultiPolygon, coordinates is an array of Polygon coordinate arrays
				// We'll use the first polygon for simplicity
				if (featureGeometry.coordinates.length > 0) {
					const firstPolygon = featureGeometry.coordinates[0];
					const exteriorRing = firstPolygon[0];
					path = exteriorRing.map(coord => ({
						lat: coord[1], // latitude is second element
						lng: coord[0]  // longitude is first element
					}));
				}
			}
		}
		// Handle direct Polygon/MultiPolygon format (fallback)
		else if (geometry.type === 'Polygon') {
			const exteriorRing = geometry.coordinates[0];
			path = exteriorRing.map(coord => ({
				lat: coord[1], // latitude is second element
				lng: coord[0]  // longitude is first element
			}));
		} else if (geometry.type === 'MultiPolygon') {
			if (geometry.coordinates.length > 0) {
				const firstPolygon = geometry.coordinates[0];
				const exteriorRing = firstPolygon[0];
				path = exteriorRing.map(coord => ({
					lat: coord[1], // latitude is second element
					lng: coord[0]  // longitude is first element
				}));
			}
		}

		return {
			id: boundary.id,
			name: boundary.name,
			path: path
		};
	});
});

const toggleHeatMap = () => {
	showHeatMap.value = !showHeatMap.value;
};
const toggleMarkers = () => {
	showMarkers.value = !showMarkers.value;
};
const toggleBoundaries = () => {
	showBoundaries.value = !showBoundaries.value;
};

// Computed properties for control button styling based on theme
const getControlBaseClasses = computed(() => {
	const isDark = appearance.value === 'dark' || (appearance.value === 'system' && window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches);
	
	return isDark 
		? 'bg-gray-800 hover:bg-gray-700 shadow-lg p-2 rounded-full w-10 h-10 flex items-center justify-center text-red-500 hover:text-red-400 transition-colors'
		: 'bg-red-600 hover:bg-red-700 shadow-lg p-2 rounded-full w-10 h-10 flex items-center justify-center text-white hover:text-gray-100 transition-colors';
});

const getControlActiveClasses = computed(() => {
	const isDark = appearance.value === 'dark' || (appearance.value === 'system' && window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches);
	
	return isDark
		? 'bg-gray-700 text-red-400'
		: 'bg-red-700 text-white';
});
</script>

<template>
	<div class="relative" :style="{ width: '100%', height: mapHeight }">
		<GoogleMap :apiKey="api_key" :center="userLocation" :zoom="zoom" :mapTypeId="mapTypeId" :disableDefaultUi="true"
			:styles="mapTypeId === 'roadmap' ? currentMapStyles : null" :style="{ width: '100%', height: mapHeight }">
			{{ JSON.stringify(processedBoundaries) }}
							<Polygon v-for="boundary in processedBoundaries" :key="boundary.id" :options="{
					paths: boundary.path,
					strokeColor: 'hsl(220, 83%, 53%)',
					strokeOpacity: 0.8,
					strokeWeight: 2,
					fillColor: 'hsl(220, 83%, 53%)',
					fillOpacity: 0.35,
				}" />
		</GoogleMap>
		<div class="absolute right-2 top-1/2 -translate-y-1/2 space-y-2">
			<button @click="toggleMapType"
				:class="[getControlBaseClasses, mapTypeId !== 'roadmap' ? getControlActiveClasses : '']">
				<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
					class="transition-transform duration-300"
					:class="{ 'rotate-90': mapTypeId == 'satellite', 'rotate-180': mapTypeId !== 'satellite' }">
					<path fill="currentColor"
						d="m11.62 1l5.66 5.67l-2.12 2.12l-2.12-2.12l-1.42 1.42l2.33 2.32l-1.16 1.17l.45.46a2.5 2.5 0 0 1 2.83.5l-3.53 3.53a2.5 2.5 0 0 1-.5-2.83l-.46-.45l-1.17 1.16l-2.32-2.33l-1.42 1.42l2.12 2.12l-2.12 2.12L1 11.62L3.14 9.5l2.12 2.12l1.41-1.41l-2.83-2.83c-.78-.78-.78-2.05 0-2.83l.71-.71c.78-.78 2.05-.78 2.83 0l2.83 2.83l1.41-1.41L9.5 3.14zM18 14a4 4 0 0 1-4 4v-2a2 2 0 0 0 2-2zm4 0a8 8 0 0 1-8 8v-2a6 6 0 0 0 6-6z" />
				</svg>
			</button>
			<button @click="zoomIn"
				:class="[getControlBaseClasses, 'text-xl']">
				+
			</button>
			<button @click="zoomOut"
				:class="[getControlBaseClasses, 'text-xl']">
				-
			</button>
		</div>
		<div class="absolute left-2 top-1/2 -translate-y-1/2 space-y-2">
			<button @click="toggleHeatMap"
				:class="[getControlBaseClasses, showHeatMap ? getControlActiveClasses : '']">
				<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
					viewBox="0 0 24 24"><!-- Icon from Material Symbols by Google - https://github.com/google/material-design-icons/blob/master/LICENSE -->
					<path fill="currentColor"
						d="M4 14q0-2.825 1.675-5.425t4.6-4.55q.55-.375 1.138-.038T12 5v1.3q0 .85.588 1.425t1.437.575q.425 0 .813-.188t.687-.537q.2-.25.513-.312t.587.137Q18.2 8.525 19.1 10.275T20 14q0 2.2-1.075 4.013T16.1 20.874q.425-.6.663-1.312T17 18.05q0-1-.375-1.888t-1.075-1.587L12 11.1l-3.525 3.475q-.725.725-1.1 1.6T7 18.05q0 .8.238 1.513t.662 1.312q-1.75-1.05-2.825-2.863T4 14m8-.1l2.125 2.075q.425.425.65.95T15 18.05q0 1.225-.875 2.088T12 21t-2.125-.862T9 18.05q0-.575.225-1.112t.65-.963z" />
				</svg>
			</button>
			<button @click="toggleMarkers"
				:class="[getControlBaseClasses, showMarkers ? getControlActiveClasses : '']">
				<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
					viewBox="0 0 24 24"><!-- Icon from Material Symbols by Google - https://github.com/google/material-design-icons/blob/master/LICENSE -->
					<path fill="currentColor"
						d="M12 21.325q-.35 0-.7-.125t-.625-.375Q9.05 19.325 7.8 17.9t-2.087-2.762t-1.275-2.575T4 10.2q0-3.75 2.413-5.975T12 2t5.588 2.225T20 10.2q0 1.125-.437 2.363t-1.275 2.575T16.2 17.9t-2.875 2.925q-.275.25-.625.375t-.7.125M12 12q.825 0 1.413-.587T14 10t-.587-1.412T12 8t-1.412.588T10 10t.588 1.413T12 12" />
				</svg>
			</button>
			<button @click="toggleBoundaries"
				:class="[getControlBaseClasses, showBoundaries ? getControlActiveClasses : '']">
				<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
					viewBox="0 0 512 512"><!-- Icon from Famicons by Family - https://github.com/familyjs/famicons/blob/main/LICENSE -->
					<path fill="currentColor"
						d="M480 150L256 48L32 150l224 104zM255.71 392.95l-144.81-66.2L32 362l224 102l224-102l-78.69-35.3z" />
					<path fill="currentColor" d="m480 256l-75.53-33.53L256.1 290.6l-148.77-68.17L32 256l224 102z" />
				</svg>
			</button>
		</div>
		{{ JSON.stringify(boundariesList) }}
		<DrillDown class="absolute top-10 left-1/2 -translate-x-1/2 z-10" v-model="boundariesList" :counties="counties" :constituencies="constituencies" :wards="wards" />
	</div>
</template>

<style>
.gm-style .gm-control-active {
	background-color: hsl(var(--primary)) !important;
	border-color: hsl(var(--primary)) !important;
	color: #fff !important;
}
</style>