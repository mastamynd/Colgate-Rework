<script setup>
import { useGeolocation } from '@vueuse/core'
import { GoogleMap, Marker, Polygon, InfoWindow } from 'vue3-google-map'
import DrillDown from './DrillDown.vue'
import { ref, watchEffect, computed } from 'vue'
import { Deferred } from '@inertiajs/vue3'

import { useAppearance } from '@/composables/useAppearance'
// import { fetchBoundaries } from '@/composables/useFetchBoundaries';

const { api_key, counties, constituencies, wards, boundaries, customInfoWindowHtml, salesPersonnel } = defineProps({
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
	},
	customInfoWindowHtml: {
		type: String,
		default: '',
		description: 'Custom HTML content to display in the polygon info window. If not provided, default content will be shown.'
	},
	salesPersonnel: {
		type: Array,
		default: () => [],
		description: 'Array of sales personnel objects with properties like name, color, etc.'
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
const drillDownData = ref({
	boundariesVisible: true,
	boundariesCount: 0,
	county: null,
	constituency: null,
	ward: null
	});

// State for managing which infowindow is open
const openInfoWindow = ref(null);

// Computed property for info window HTML content
const infoWindowHtml = computed(() => {
	return customInfoWindowHtml || `
		<div class="space-y-2">
			<div class="flex items-center justify-between">
				<span class="text-gray-500">Population:</span>
				<span class="font-medium">~2.4M</span>
			</div>
			<div class="flex items-center justify-between">
				<span class="text-gray-500">Area:</span>
				<span class="font-medium">696.1 km²</span>
			</div>
			<div class="flex items-center justify-between">
				<span class="text-gray-500">Density:</span>
				<span class="font-medium">3,447/km²</span>
			</div>
		</div>
	`;
});

// Function to handle polygon click
const handlePolygonClick = (boundaryId) => {
	console.log('Polygon clicked:', boundaryId);
	openInfoWindow.value = openInfoWindow.value === boundaryId ? null : boundaryId;
	console.log('Info window state:', openInfoWindow.value);
};

// Function to close infowindow
const closeInfoWindow = () => {
	openInfoWindow.value = null;
};

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

const processedBoundaries = (boundaries) => {
	if (!boundaries || !Array.isArray(boundaries)) return [];

	drillDownData.value.boundariesCount = boundaries.length;

	const mapBounds = new mapRef.value.api.LatLngBounds();
	let hasValidBounds = false;
	
	const processedBoundaries = boundaries.map(boundary => {
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
				if (exteriorRing && exteriorRing.length > 0 && !isNaN(exteriorRing[0][1]) && !isNaN(exteriorRing[0][0])) {
					mapBounds.extend(new mapRef.value.api.LatLng(exteriorRing[0][1], exteriorRing[0][0]));
					hasValidBounds = true;
				}
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
					if (exteriorRing && exteriorRing.length > 0 && !isNaN(exteriorRing[0][1]) && !isNaN(exteriorRing[0][0])) {
						mapBounds.extend(new mapRef.value.api.LatLng(exteriorRing[0][1], exteriorRing[0][0]));
						hasValidBounds = true;
					}
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
			if (exteriorRing && exteriorRing.length > 0 && !isNaN(exteriorRing[0][1]) && !isNaN(exteriorRing[0][0])) {
				mapBounds.extend(new mapRef.value.api.LatLng(exteriorRing[0][1], exteriorRing[0][0]));
				hasValidBounds = true;
			}
			path = exteriorRing.map(coord => ({
				lat: coord[1], // latitude is second element
				lng: coord[0]  // longitude is first element
			}));
		} else if (geometry.type === 'MultiPolygon') {
			if (geometry.coordinates.length > 0) {
				const firstPolygon = geometry.coordinates[0];
				const exteriorRing = firstPolygon[0];
				if (exteriorRing && exteriorRing.length > 0 && !isNaN(exteriorRing[0][1]) && !isNaN(exteriorRing[0][0])) {
					mapBounds.extend(new mapRef.value.api.LatLng(exteriorRing[0][1], exteriorRing[0][0]));
					hasValidBounds = true;
				}
				path = exteriorRing.map(coord => ({
					lat: coord[1], // latitude is second element
					lng: coord[0]  // longitude is first element
				}));
			}
		}

		const processed = {
			id: boundary.id,
			name: boundary.name,
			type: boundary.type, // Add the boundary type
			path: path
		};
		
		return processed;
	});

	// Only fit bounds if we have valid coordinates
	if (hasValidBounds && !mapBounds.isEmpty()) {
		mapRef.value.map.fitBounds(mapBounds);
	}

	return processedBoundaries;
}

const shapesLoaded = computed(() => {
	return boundaries.value && boundaries.value.length > 0;
});

const toggleHeatMap = () => {
	showHeatMap.value = !showHeatMap.value;
};
const toggleMarkers = () => {
	showMarkers.value = !showMarkers.value;
};
const toggleBoundaries = () => {
	drillDownData.value.boundariesVisible = !drillDownData.value.boundariesVisible;
};

const showPersonnelCard = ref(false);

const togglePersonnelCard = () => {
	showPersonnelCard.value = !showPersonnelCard.value;
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

const getOps = (boundary, openInfoWindow, hover=false) => {
	return hover ? {
		paths: boundary.path,
		strokeColor: 'hsl(0, 85%, 60%)', // Bright red stroke on hover
		strokeOpacity: 0.9,
		strokeWeight: 3,
		fillColor: 'hsl(0, 75%, 55%)', // Vibrant red fill on hover
		fillOpacity: 0.5,
		clickable: true,
		zIndex: openInfoWindow === boundary.id ? 1000 : 100,
	} : {
		paths: boundary.path,
		strokeColor: 'hsl(0, 70%, 45%)', // Darker red stroke for normal state
		strokeOpacity: 0.7,
		strokeWeight: 2,
		fillColor: 'hsl(0, 60%, 50%)', // Muted red fill for normal state
		fillOpacity: 0.25,
		clickable: true,
		zIndex: openInfoWindow === boundary.id ? 1000 : 1,
	}
}

const mapRef = ref(null);
</script>

<template>
	<div class="relative" :style="{ width: '100%', height: mapHeight }">
		<GoogleMap ref="mapRef" :apiKey="api_key" :zoom="zoom" :mapTypeId="mapTypeId" :disableDefaultUi="true" :validated-boundaries="processedBoundaries(boundaries)"
			:styles="mapTypeId === 'roadmap' ? currentMapStyles : null" :style="{ width: '100%', height: mapHeight }" @click="closeInfoWindow">
			<template v-if="drillDownData.boundariesVisible">
				<Deferred :data="['boundaries']">
					<template #fallback>
							<div>Loading...</div>
					</template>
					<Polygon v-for="boundary in processedBoundaries(boundaries)" :key="boundary.id" :options="getOps(boundary, openInfoWindow, false)" @click="handlePolygonClick(boundary.id)" @mouseover="getOps(boundary, openInfoWindow, true)" @mouseleave="getOps(boundary, openInfoWindow, false)">
						<InfoWindow v-if="openInfoWindow === boundary.id" :options="{
							position: { 
								lat: boundary.path.reduce((sum, point) => sum + point.lat, 0) / boundary.path.length,
								lng: boundary.path.reduce((sum, point) => sum + point.lng, 0) / boundary.path.length
							}
						}">
							<div class="bg-white rounded-lg shadow-xl border border-gray-200 p-4 min-w-[280px] max-w-[320px]">
								<!-- Header Section -->
								<div class="border-b border-gray-200 pb-3 mb-3">
									<h3 class="text-lg font-semibold text-gray-800 mb-1">{{ boundary.name }}</h3>
									<div class="flex items-center">
										<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
											{{ boundary.type?.charAt(0).toUpperCase() + boundary.type?.slice(1) }}
										</span>
									</div>
								</div>
								
								<!-- Custom HTML Content Section -->
								<div v-if="infoWindowHtml" class="text-sm text-gray-600" v-html="infoWindowHtml"></div>
								
								<!-- Footer with close button -->
								<div class="mt-3 pt-3 border-t border-gray-200">
									<button @click="closeInfoWindow" class="text-xs text-gray-500 hover:text-gray-700 transition-colors">
										Click to close
									</button>
								</div>
							</div>
						</InfoWindow>
					</Polygon>
				</Deferred>
			</template>
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
				:class="[getControlBaseClasses, drillDownData.boundariesVisible ? getControlActiveClasses : '']">
				<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
					viewBox="0 0 512 512"><!-- Icon from Famicons by Family - https://github.com/familyjs/famicons/blob/main/LICENSE -->
					<path fill="currentColor"
						d="M480 150L256 48L32 150l224 104zM255.71 392.95l-144.81-66.2L32 362l224 102l224-102l-78.69-35.3z" />
					<path fill="currentColor" d="m480 256l-75.53-33.53L256.1 290.6l-148.77-68.17L32 256l224 102z" />
				</svg>
			</button>
			<button @click="togglePersonnelCard"
				:class="[getControlBaseClasses, showPersonnelCard ? getControlActiveClasses : '']">
				<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 32 32"><!-- Icon from Carbon by IBM - undefined --><path fill="currentColor" d="M30 6V4h-3V2h-2v2h-1c-1.103 0-2 .898-2 2v2c0 1.103.897 2 2 2h4v2h-6v2h3v2h2v-2h1c1.103 0 2-.897 2-2v-2c0-1.102-.897-2-2-2h-4V6zm-6 14v2h2.586L23 25.586l-2.292-2.293a1 1 0 0 0-.706-.293H20a1 1 0 0 0-.706.293L14 28.586L15.414 30l4.587-4.586l2.292 2.293a1 1 0 0 0 1.414 0L28 23.414V26h2v-6zM4 30H2v-5c0-3.86 3.14-7 7-7h6c1.989 0 3.89.85 5.217 2.333l-1.49 1.334A5 5 0 0 0 15 20H9c-2.757 0-5 2.243-5 5zm8-14a7 7 0 1 0 0-14a7 7 0 0 0 0 14m0-12a5 5 0 1 1 0 10a5 5 0 0 1 0-10"/></svg>
			</button>
		</div>
		<DrillDown class="absolute top-10 left-1/2 -translate-x-1/2 z-10" v-model="drillDownData" :counties="counties" :constituencies="constituencies" :wards="wards" />
		
		<!-- Personnel Card -->
		<div v-if="showPersonnelCard" class="absolute left-16 top-24 z-20 bg-white dark:bg-gray-800 rounded-lg shadow-xl border border-gray-200 dark:border-gray-700 p-4 min-w-[300px] max-w-[400px] max-h-[500px] overflow-y-auto">
			<!-- Header -->
			<div class="flex items-center justify-between mb-4 pb-3 border-b border-gray-200 dark:border-gray-600">
				<h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 flex">
					<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 32 32"><!-- Icon from Carbon by IBM - undefined --><path fill="currentColor" d="M30 6V4h-3V2h-2v2h-1c-1.103 0-2 .898-2 2v2c0 1.103.897 2 2 2h4v2h-6v2h3v2h2v-2h1c1.103 0 2-.897 2-2v-2c0-1.102-.897-2-2-2h-4V6zm-6 14v2h2.586L23 25.586l-2.292-2.293a1 1 0 0 0-.706-.293H20a1 1 0 0 0-.706.293L14 28.586L15.414 30l4.587-4.586l2.292 2.293a1 1 0 0 0 1.414 0L28 23.414V26h2v-6zM4 30H2v-5c0-3.86 3.14-7 7-7h6c1.989 0 3.89.85 5.217 2.333l-1.49 1.334A5 5 0 0 0 15 20H9c-2.757 0-5 2.243-5 5zm8-14a7 7 0 1 0 0-14a7 7 0 0 0 0 14m0-12a5 5 0 1 1 0 10a5 5 0 0 1 0-10"/></svg>
					<span class="ml-2">Sales Personnel</span>
				</h3>
				<button @click="togglePersonnelCard" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
					<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
						<line x1="18" y1="6" x2="6" y2="18"></line>
						<line x1="6" y1="6" x2="18" y2="18"></line>
					</svg>
				</button>
			</div>
			
			<!-- Personnel List -->
			<div v-if="salesPersonnel && salesPersonnel.length > 0" class="space-y-3">
				<div v-for="person in salesPersonnel" :key="person.id || person.name" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
					<!-- Profile Photo -->
					<div class="flex-shrink-0">
						<img :src="`https://ui-avatars.com/api/?name=${encodeURIComponent(person.name)}&color=${person.color || '7F9CF5'}&background=EBF4FF`" 
							 :alt="person.name"
							 class="w-10 h-10 rounded-full border-2 border-gray-200 dark:border-gray-600"
							 @error="$event.target.src = `https://ui-avatars.com/api/?name=${encodeURIComponent(person.name)}&color=7F9CF5&background=EBF4FF`">
					</div>
					
					<!-- Personnel Info -->
					<div class="flex-1 min-w-0">
						<p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">{{ person.name }}</p>
						<p v-if="person.role" class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ person.role }}</p>
						<p v-if="person.code" class="text-xs text-gray-500 dark:text-gray-400 truncate">Code: {{ person.code }}</p>
					</div>
					
					<!-- Status Indicator -->
					<div v-if="person.is_active !== undefined" class="flex-shrink-0">
						<div :class="[
							'w-2 h-2 rounded-full',
							person.is_active ? 'bg-green-500' : 'bg-red-500'
						]"></div>
					</div>
				</div>
			</div>
			
			<!-- Empty State -->
			<div v-else class="text-center py-8">
				<div class="flex justify-center text-gray-400 dark:text-gray-500 mb-2">
					<svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round">
						<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
						<circle cx="9" cy="7" r="4"></circle>
						<path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
						<path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
					</svg>
				</div>
				<p class="text-gray-500 dark:text-gray-400 text-sm text-center">No sales personnel available</p>
			</div>
		</div>
	</div>
</template>

<style>
.gm-style .gm-control-active {
	background-color: hsl(var(--primary)) !important;
	border-color: hsl(var(--primary)) !important;
	color: #fff !important;
}
</style>