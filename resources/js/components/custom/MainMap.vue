<script setup>
import { useGeolocation } from '@vueuse/core'
import { GoogleMap, CustomMarker, Polygon, InfoWindow, HeatmapLayer, MarkerCluster } from 'vue3-google-map'
import DrillDown from './DrillDown.vue'
import MapSidebar from './MapSidebar.vue'
import { ref, watchEffect, computed, shallowRef, watch, onMounted, onUnmounted } from 'vue'
import { Deferred, router } from '@inertiajs/vue3'

import { useAppearance } from '@/composables/useAppearance'
// import { fetchBoundaries } from '@/composables/useFetchBoundaries';

const { api_key, counties, constituencies, wards, boundaries, customInfoWindowHtml, salesPersonnel, available_map_data } = defineProps({
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
	},
	available_map_data: {
		type: Array,
		default: () => []
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
const showMarkersModal = ref(false);
const drillDownData = ref({
	boundariesVisible: true,
	boundariesCount: 0,
	county: null,
	constituency: null,
	ward: null
});

const demorgraphicsHeatMapData = shallowRef([]);

// State for managing which infowindow is open
const openInfoWindow = ref(null);

// State for single InfoWindow
const infoWindowPosition = ref(null);
const infoWindowContent = ref('');
const showInfoWindow = ref(false);

// State for hover effects
const hoveredBoundary = ref(null);
const mousePosition = ref(null);
const showHoverInfoWindow = ref(false);

// State for marker hover effects
const hoveredMarker = ref(null);

// Computed property for current boundary
const currentBoundary = computed(() => {
	if (!openInfoWindow.value) return null;
	return processedBoundaries(boundaries).find(b => b.id === openInfoWindow.value);
});

// Computed property for hovered boundary
const hoveredBoundaryData = computed(() => {
	if (!hoveredBoundary.value) return null;
	return processedBoundaries(boundaries).find(b => b.id === hoveredBoundary.value);
});

// Computed property for filtered boundaries ready for rendering
const renderableBoundaries = computed(() => {
	console.log('Computing renderable boundaries, mapReady:', mapReady.value);
	const processed = processedBoundaries(boundaries);
	console.log('Processed boundaries:', processed);
	const filtered = processed.filter(boundary => 
		boundary.path && boundary.path.length > 0
	);
	console.log('Filtered boundaries:', filtered);
	return filtered;
});

// Function to set InfoWindow content
// Usage: setInfoWindowContent('<div>Custom content</div>', { lat: -1.2921, lng: 36.8219 })
// Or: setInfoWindowContent('<div>Custom content</div>') - will use current position
const setInfoWindowContent = (content, position = null) => {
	infoWindowContent.value = content;
	if (position) {
		infoWindowPosition.value = position;
	}
	showInfoWindow.value = true;
};

// Function to close InfoWindow
const closeInfoWindow = () => {
	showInfoWindow.value = false;
	infoWindowContent.value = '';
	infoWindowPosition.value = null;
	openInfoWindow.value = null;
	
	// Also close hover InfoWindow
	showHoverInfoWindow.value = false;
	hoveredBoundary.value = null;
	mousePosition.value = null;
};

// Marker layers state (for modal checkboxes)
const markerLayers = ref({
	customers: true, // Set to true by default for testing
	mapDataIds: []
});

// Customer marker classification state
const customerClassification = ref('KD'); // 'KD' or 'RE'
const showColorLegend = ref(false);

// Color mapping for different classifications
const colorMappings = {
	KD: {
		'KD-001': '#FF6B6B', // Red
		'KD-002': '#4ECDC4', // Teal
		'KD-003': '#45B7D1', // Blue
		'KD-004': '#96CEB4', // Green
		'KD-005': '#FFEAA7', // Yellow
		'KD-006': '#DDA0DD', // Plum
		'KD-007': '#98D8C8', // Mint
		'KD-008': '#F7DC6F', // Light Yellow
		'KD-009': '#BB8FCE', // Light Purple
	},
	RE: {
		'RE-101': '#E74C3C', // Red
		'RE-102': '#3498DB', // Blue
		'RE-103': '#2ECC71', // Green
		'RE-104': '#F39C12', // Orange
		'RE-105': '#9B59B6', // Purple
		'RE-106': '#1ABC9C', // Turquoise
		'RE-107': '#E67E22', // Carrot
		'RE-108': '#34495E', // Dark Blue
	}
};

// Function to get color for a customer based on current classification
const getCustomerColor = (customer) => {
	const classification = customerClassification.value;
	const code = customer[classification];
	return colorMappings[classification][code] || '#666666'; // Default gray if not found
};

// Function to toggle customer classification
const toggleCustomerClassification = () => {
	customerClassification.value = customerClassification.value === 'KD' ? 'RE' : 'KD';
};

// Function to toggle color legend
const toggleColorLegend = () => {
	showColorLegend.value = !showColorLegend.value;
};

// Function to get description for a code
const getCodeDescription = (code) => {
	const descriptions = {
		// KD Descriptions
		'KD-001': 'Central Business District',
		'KD-002': 'Commercial Zone A',
		'KD-003': 'Residential High-End',
		'KD-004': 'Mixed Development',
		'KD-005': 'Industrial Zone',
		'KD-006': 'Agricultural Area',
		'KD-007': 'Transportation Hub',
		'KD-008': 'Service Center',
		'KD-009': 'Rural Development',
		
		// RE Descriptions
		'RE-101': 'Region East 1',
		'RE-102': 'Region East 2',
		'RE-103': 'Region East 3',
		'RE-104': 'Region East 4',
		'RE-105': 'Region East 5',
		'RE-106': 'Region East 6',
		'RE-107': 'Region East 7',
		'RE-108': 'Region East 8',
	};
	
	return descriptions[code] || 'No description available';
};

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
const handlePolygonClick = (boundary) => {
	// Close hover InfoWindow if open
	showHoverInfoWindow.value = false;
	hoveredBoundary.value = null;
	mousePosition.value = null;
	
	// Calculate center position for InfoWindow
	const centerLat = boundary.path.reduce((sum, point) => sum + point.lat, 0) / boundary.path.length;
	const centerLng = boundary.path.reduce((sum, point) => sum + point.lng, 0) / boundary.path.length;
	
	// Store boundary data for InfoWindow
	infoWindowPosition.value = { lat: centerLat, lng: centerLng };
	openInfoWindow.value = boundary.id;
	showInfoWindow.value = true;
	
	// Trigger drilldown functionality based on boundary type
	triggerDrilldown(boundary);
};

// Function to handle polygon hover
const handlePolygonHover = (boundary, event) => {
	hoveredBoundary.value = boundary.id;
	
	// Get mouse position from the event
	if (event && event.latLng) {
		mousePosition.value = {
			lat: event.latLng.lat(),
			lng: event.latLng.lng()
		};
		showHoverInfoWindow.value = true;
	}
};

// Function to handle polygon leave
const handlePolygonLeave = () => {
	hoveredBoundary.value = null;
	showHoverInfoWindow.value = false;
	mousePosition.value = null;
};

// Function to handle marker hover
const handleMarkerHover = (customer) => {
	hoveredMarker.value = customer;
};

// Function to handle marker leave
const handleMarkerLeave = () => {
	hoveredMarker.value = null;
};

// Function to trigger drilldown functionality
const triggerDrilldown = (boundary) => {
	const params = new URLSearchParams();
	
	// Set the appropriate boundary type and ID based on the clicked boundary
	if (boundary.type === 'county') {
		params.set('boundary_type', 'county');
		params.set('boundary_id', boundary.id);
	} else if (boundary.type === 'constituency') {
		params.set('boundary_type', 'constituency');
		params.set('boundary_id', boundary.id);
	} else if (boundary.type === 'ward') {
		params.set('boundary_type', 'ward');
		params.set('boundary_id', boundary.id);
	}
	
	// Navigate with the new query parameters to trigger drilldown
	if (params.toString()) {
		router.get(`${window.location.pathname}?${params.toString()}`, {}, {
			preserveState: true,
			preserveScroll: true,
			replace: true
		});
	}
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



// Sales Personnel data with colors
const salesPersonnelData = [
  {
    id: 1,
    name: "Alice Mwangi",
    color: "#FF6B6B", // Red
    is_active: true,
    role: "Senior Sales Rep",
    code: "SP001"
  },
  {
    id: 2,
    name: "Brian Otieno", 
    color: "#4ECDC4", // Teal
    is_active: true,
    role: "Sales Rep",
    code: "SP002"
  },
  {
    id: 3,
    name: "Catherine Njeri",
    color: "#45B7D1", // Blue
    is_active: true,
    role: "Sales Rep",
    code: "SP003"
  },
  {
    id: 4,
    name: "David Mutua",
    color: "#96CEB4", // Green
    is_active: true,
    role: "Sales Rep",
    code: "SP004"
  }
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
	console.log('Processing boundaries:', boundaries);
	if (!boundaries || !Array.isArray(boundaries)) {
		console.log('No boundaries or not array');
		return [];
	}
	if (!mapRef.value?.api) {
		console.log('Map API not ready, returning empty paths');
		return boundaries.map(boundary => ({
			id: boundary.id,
			name: boundary.name,
			type: boundary.type,
			path: [] // Return empty path if map API not ready
		}));
	}

	console.log('Map API ready, processing boundaries');
	drillDownData.value.boundariesCount = boundaries.length;

	const mapBounds = mapRef.value?.api?.LatLngBounds ? new mapRef.value.api.LatLngBounds() : null;
	let hasValidBounds = false;
	
	const processedBoundaries = boundaries.map(boundary => {
		let geometry = JSON.parse(boundary.geometry);
		let path = [];
		
		// Handle FeatureCollection format
		if (geometry.type === 'FeatureCollection' && geometry.features && geometry.features.length > 0) {
			const feature = geometry.features[0];
			const featureGeometry = feature.geometry;
			
			if (featureGeometry.type === 'Polygon') {
				const exteriorRing = featureGeometry.coordinates[0];
				if (exteriorRing && exteriorRing.length > 0 && !isNaN(exteriorRing[0][1]) && !isNaN(exteriorRing[0][0])) {
					if (mapBounds) mapBounds.extend(new mapRef.value.api.LatLng(exteriorRing[0][1], exteriorRing[0][0]));
					hasValidBounds = true;
				}
				path = exteriorRing.map(coord => ({
					lat: coord[1],
					lng: coord[0]
				}));
			} else if (featureGeometry.type === 'MultiPolygon') {
				if (featureGeometry.coordinates.length > 0) {
					const firstPolygon = featureGeometry.coordinates[0];
					const exteriorRing = firstPolygon[0];
					if (exteriorRing && exteriorRing.length > 0 && !isNaN(exteriorRing[0][1]) && !isNaN(exteriorRing[0][0])) {
						if (mapBounds) mapBounds.extend(new mapRef.value.api.LatLng(exteriorRing[0][1], exteriorRing[0][0]));
						hasValidBounds = true;
					}
					path = exteriorRing.map(coord => ({
						lat: coord[1],
						lng: coord[0]
					}));
				}
			}
		}
		// Handle direct Polygon/MultiPolygon format (fallback)
		else if (geometry.type === 'Polygon') {
			const exteriorRing = geometry.coordinates[0];
			if (exteriorRing && exteriorRing.length > 0 && !isNaN(exteriorRing[0][1]) && !isNaN(exteriorRing[0][0])) {
				if (mapBounds) mapBounds.extend(new mapRef.value.api.LatLng(exteriorRing[0][1], exteriorRing[0][0]));
				hasValidBounds = true;
			}
			path = exteriorRing.map(coord => ({
				lat: coord[1],
				lng: coord[0]
			}));
		} else if (geometry.type === 'MultiPolygon') {
			if (geometry.coordinates.length > 0) {
				const firstPolygon = geometry.coordinates[0];
				const exteriorRing = firstPolygon[0];
				if (exteriorRing && exteriorRing.length > 0 && !isNaN(exteriorRing[0][1]) && !isNaN(exteriorRing[0][0])) {
					if (mapBounds) mapBounds.extend(new mapRef.value.api.LatLng(exteriorRing[0][1], exteriorRing[0][0]));
					hasValidBounds = true;
				}
				path = exteriorRing.map(coord => ({
					lat: coord[1],
					lng: coord[0]
				}));
			}
		}

		const processed = {
			id: boundary.id,
			name: boundary.name,
			type: boundary.type,
			path: path
		};
		
		return processed;
	});

	// Only fit bounds if we have valid coordinates
	if (hasValidBounds && mapBounds && !mapBounds.isEmpty()) {
		mapRef.value.map.fitBounds(mapBounds);
	}

	return processedBoundaries;
}

const shapesLoaded = computed(() => {
	return boundaries.value && boundaries.value.length > 0;
});

const customerData = ref([]);

const fetchCustomerData = async () => {
	let data = await fetch(route('demarcations.customer')).then(response => response.json());
	return data;
}

const toggleHeatMap = async () => {
	if(demorgraphicsHeatMapData.value.length === 0){
		let data = await fetch(route('demarcations.demographics-heat-map')).then(response => response.json());
		if(demorgraphicsHeatMapData.value.length === 0){
			// Remove null entries
			demorgraphicsHeatMapData.value = data.map(point => {
				const lat = parseFloat(point.latitude)
				const lng = parseFloat(point.longitude)

				if (isNaN(lat) || isNaN(lng)) return null

				if (point.Average_Score !== undefined && point.Average_Score !== null) {
					return { location: { lat, lng }, weight: point.Average_Score }
				} else {
					return { lat, lng }
				}
			}).filter(point => point !== null)
		}
	}
	showHeatMap.value = !showHeatMap.value;
};

const toggleMarkersModal = () => {
	showMarkersModal.value = !showMarkersModal.value;
};

const toggleBoundaries = () => {
	drillDownData.value.boundariesVisible = !drillDownData.value.boundariesVisible;
};

const showPersonnelCard = ref(false);
const showSidebar = ref(false);

const togglePersonnelCard = () => {
	showPersonnelCard.value = !showPersonnelCard.value;
};

const toggleSidebar = () => {
	showSidebar.value = !showSidebar.value;
};

const closeSidebar = () => {
	showSidebar.value = false;
};

// Reactive window width for responsive behavior
const windowWidth = ref(window.innerWidth);

// Window resize handler
const handleResize = () => {
	windowWidth.value = window.innerWidth;
};

// Lifecycle hooks
onMounted(() => {
	window.addEventListener('resize', handleResize);
});

onUnmounted(() => {
	window.removeEventListener('resize', handleResize);
});

// Computed property for responsive behavior
const isFullScreenSidebar = computed(() => {
	// Check if 30% of screen width is less than 250px
	return windowWidth.value * 0.3 < 250;
});

// Computed property for map width based on sidebar state
const mapWidth = computed(() => {
	if (!showSidebar.value) return '100%';
	return isFullScreenSidebar.value ? '0%' : '70%';
});

// Sales Personnel state management
const personnelVisibility = ref({
	showAll: true,
	individual: {} // Track individual personnel visibility
});

// Hovered personnel for highlighting
const hoveredPersonnel = ref(null);

// Function to get personnel color by name
const getPersonnelColor = (personnelName) => {
	const personnelList = salesPersonnelData.length > 0 ? salesPersonnelData : salesPersonnel;
	const person = personnelList.find(p => p.name === personnelName);
	return person ? (person.color || '#666666') : '#666666';
};

// Function to toggle all personnel visibility
const toggleAllPersonnel = () => {
	personnelVisibility.value.showAll = !personnelVisibility.value.showAll;
	// Update all individual toggles to match the showAll state
	Object.keys(personnelVisibility.value.individual).forEach(name => {
		personnelVisibility.value.individual[name] = personnelVisibility.value.showAll;
	});
};

// Function to toggle individual personnel visibility
const togglePersonnelVisibility = (personnelName) => {
	// Toggle the individual visibility
	personnelVisibility.value.individual[personnelName] = !personnelVisibility.value.individual[personnelName];
	
	// Update showAll based on individual toggles
	const allVisible = Object.values(personnelVisibility.value.individual).every(visible => visible);
	const allHidden = Object.values(personnelVisibility.value.individual).every(visible => !visible);
	
	if (allVisible) {
		personnelVisibility.value.showAll = true;
	} else if (allHidden) {
		personnelVisibility.value.showAll = false;
	} else {
		// Mixed state - some visible, some hidden
		personnelVisibility.value.showAll = false;
	}
};

// Function to handle personnel hover
const handlePersonnelHover = (personnelName) => {
	hoveredPersonnel.value = personnelName;
};

// Function to handle personnel leave
const handlePersonnelLeave = () => {
	hoveredPersonnel.value = null;
};

// Computed property to filter customers based on personnel visibility
const visibleCustomers = computed(() => {
	// If showAll is false, filter by individual visibility
	if (!personnelVisibility.value.showAll) {
		return customers.filter(customer => 
			personnelVisibility.value.individual[customer.sales_person] === true
		);
	}
	// If showAll is true, show all customers
	return customers;
});

// Initialize personnel visibility when component is ready
watchEffect(() => {
	const personnelList = salesPersonnelData.length > 0 ? salesPersonnelData : salesPersonnel;
	if (personnelList && personnelList.length > 0) {
		// Only initialize if not already initialized
		if (Object.keys(personnelVisibility.value.individual).length === 0) {
			personnelList.forEach(person => {
				personnelVisibility.value.individual[person.name] = true;
			});
		}
	}
});

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

const getOps = (boundary) => {
	const isHovered = hoveredBoundary.value === boundary.id;
	const isSelected = openInfoWindow.value === boundary.id;
	
	return {
		paths: boundary.path,
		strokeColor: isHovered ? 'hsl(0, 85%, 60%)' : 'hsl(0, 70%, 45%)',
		strokeOpacity: isHovered ? 0.9 : 0.7,
		strokeWeight: isHovered ? 4 : 2, // Increased border weight on hover
		fillColor: isHovered ? 'hsl(0, 75%, 55%)' : 'hsl(0, 60%, 50%)',
		fillOpacity: isHovered ? 0.5 : 0.25,
		clickable: true,
		zIndex: isSelected ? 1000 : (isHovered ? 100 : 1),
	}
}

const mapRef = ref(null);
const mapReady = ref(false);

// Function to handle map ready event
const onMapReady = () => {
	console.log('Map ready event triggered');
	mapReady.value = true;
};

// Watch for map ready state and trigger boundary processing
watch(mapReady, (isReady) => {
	console.log('Map ready state changed:', isReady);
	if (isReady && boundaries.value) {
		// Force re-processing of boundaries when map is ready
		processedBoundaries(boundaries.value);
	}
});

// Also watch for boundaries changes
watch(() => boundaries, (newBoundaries) => {
	console.log('Boundaries changed:', newBoundaries);
	if (mapReady.value && newBoundaries) {
		processedBoundaries(newBoundaries);
	}
}, { immediate: true });

// Expose functions for external use
defineExpose({
	setInfoWindowContent,
	closeInfoWindow
});

const customers = [
  {
    name: "Nairobi CBD",
    KD: "KD-001",
    RE: "RE-101",
    location: { lat: -1.286389, lng: 36.817223 },
    sales_person: "Alice Mwangi"
  },
  {
    name: "Westlands, Nairobi",
    KD: "KD-002",
    RE: "RE-101",
    location: { lat: -1.2648, lng: 36.8028 },
    sales_person: "Alice Mwangi"
  },
  {
    name: "Karen, Nairobi",
    KD: "KD-003",
    RE: "RE-102",
    location: { lat: -1.373333, lng: 36.858889 },
    sales_person: "Brian Otieno"
  },
  {
    name: "Ruiru, Kiambu",
    KD: "KD-004",
    RE: "RE-103",
    location: { lat: -1.145833, lng: 36.958611 },
    sales_person: "Catherine Njeri"
  },
  {
    name: "Thika, Kiambu",
    KD: "KD-005",
    RE: "RE-104",
    location: { lat: -1.033333, lng: 37.069444 },
    sales_person: "Catherine Njeri"
  },
  {
    name: "Juja, Kiambu",
    KD: "KD-004",
    RE: "RE-105",
    location: { lat: -1.101944, lng: 37.014444 },
    sales_person: "Catherine Njeri"
  },
  {
    name: "Machakos Town",
    KD: "KD-006",
    RE: "RE-106",
    location: { lat: -1.517683, lng: 37.263414 },
    sales_person: "David Mutua"
  },
  {
    name: "Athi River, Machakos",
    KD: "KD-007",
    RE: "RE-107",
    location: { lat: -1.4564, lng: 36.9783 },
    sales_person: "David Mutua"
  },
  {
    name: "Syokimau, Machakos",
    KD: "KD-008",
    RE: "RE-107",
    location: { lat: -1.3575, lng: 36.9631 },
    sales_person: "David Mutua"
  },
  {
    name: "Githurai, Nairobi/Kiambu Border",
    KD: "KD-002",
    RE: "RE-103",
    location: { lat: -1.183333, lng: 36.933333 },
    sales_person: "Catherine Njeri"
  },
  {
    name: "Kikuyu, Kiambu",
    KD: "KD-009",
    RE: "RE-104",
    location: { lat: -1.254167, lng: 36.668611 },
    sales_person: "Catherine Njeri"
  },
  {
    name: "Mlolongo, Machakos",
    KD: "KD-008",
    RE: "RE-108",
    location: { lat: -1.357222, lng: 36.931944 },
    sales_person: "David Mutua"
  }
];

</script>

<template>
	<div class="relative overflow-hidden" :style="{ width: '100%', height: mapHeight }">
		<GoogleMap ref="mapRef" :apiKey="api_key" :zoom="zoom" :mapTypeId="mapTypeId" :disableDefaultUi="true" :validated-boundaries="processedBoundaries(boundaries)"
			:styles="mapTypeId === 'roadmap' ? currentMapStyles : null" :style="{ width: mapWidth, height: mapHeight, transition: 'width 0.3s ease-in-out' }" @click="closeInfoWindow" @ready="onMapReady">
			<template v-if="drillDownData.boundariesVisible">
				<MarkerCluster v-if="markerLayers.customers">
					<CustomMarker v-for="(customer, i) in visibleCustomers" :options="{ position: customer.location, anchorPoint: 'BOTTOM_RIGHT' }" :key="i"
						@mouseover="handleMarkerHover(customer)"
						@mouseleave="handleMarkerLeave">
						<div class="relative">
							<!-- Marker Content with Personnel Border -->
							<div style="text-align: center" class="flex gap-2">
								<!-- Personnel-colored circular border -->
								<div class="relative">
									<div 
										class="w-8 h-8 rounded-full border-3 flex items-center justify-center bg-gradient-to-br from-gray-800 to-gray-900"
										:style="{ 
											borderColor: getPersonnelColor(customer.sales_person),
											background: hoveredPersonnel === customer.sales_person 
												? `linear-gradient(135deg, ${getPersonnelColor(customer.sales_person)}20, ${getPersonnelColor(customer.sales_person)}40)`
												: 'linear-gradient(135deg, #1f2937, #111827)'
										}"
									>
										<svg xmlns="http://www.w3.org/2000/svg" 
											width="20"
											height="20"
											viewBox="0 0 24 24"
											style="display: inline-block;"
											:style="{ color: getCustomerColor(customer) }"><!-- Icon from Solar by 480 Design - https://creativecommons.org/licenses/by/4.0/ --><path fill="currentColor" d="M3.778 3.655c-.181.36-.27.806-.448 1.696l-.598 2.99a3.06 3.06 0 1 0 6.043.904l.07-.69a3.167 3.167 0 1 0 6.307-.038l.073.728a3.06 3.06 0 1 0 6.043-.904l-.598-2.99c-.178-.89-.267-1.335-.448-1.696a3 3 0 0 0-1.888-1.548C17.944 2 17.49 2 16.582 2H7.418c-.908 0-1.362 0-1.752.107a3 3 0 0 0-1.888 1.548M18.269 13.5a4.53 4.53 0 0 0 2.231-.581V14c0 3.771 0 5.657-1.172 6.828c-.943.944-2.348 1.127-4.828 1.163V18.5c0-.935 0-1.402-.201-1.75a1.5 1.5 0 0 0-.549-.549C13.402 16 12.935 16 12 16s-1.402 0-1.75.201a1.5 1.5 0 0 0-.549.549c-.201.348-.201.815-.201 1.75v3.491c-2.48-.036-3.885-.22-4.828-1.163C3.5 19.657 3.5 17.771 3.5 14v-1.081a4.53 4.53 0 0 0 2.232.581a4.55 4.55 0 0 0 3.112-1.228A4.64 4.64 0 0 0 12 13.5a4.64 4.64 0 0 0 3.156-1.228a4.55 4.55 0 0 0 3.112 1.228"/></svg>
									</div>
								</div>
								<div
									class="flex items-center justify-center
										px-2 py-0.5 rounded-xl border text-[10px] font-medium shadow-md backdrop-blur-sm
										bg-gradient-to-r from-gray-900/80 via-gray-800/70 to-gray-900/80
										text-white border-white/10"
									v-if="(hoveredMarker && hoveredMarker.name === customer.name) || zoom > 11"
								>
									{{ customer.name }}
								</div>
							</div>
							
							<!-- Beautiful Badge - Only visible when zoom >= 12 and on hover -->
							<transition name="badge-fade" appear>
								<div v-if="hoveredMarker && hoveredMarker.name === customer.name" 
									class="absolute left-full ml-3 top-1/2 transform -translate-y-1/2 z-50">
									<div class="marker-badge bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 shadow-xl rounded-lg">
										<!-- Badge Content -->
										<div class="badge-content p-4">
											<!-- Header with customer name -->
											<div class="badge-header mb-2">
												<h4 class="badge-title text-gray-900 dark:text-gray-100 font-semibold text-base">{{ customer.name }}</h4>
												<div class="badge-subtitle text-xs text-gray-500 dark:text-gray-400">
													{{ getCodeDescription(customer[customerClassification]) }}
												</div>
											</div>
											
											<!-- Classification info -->
											<div class="badge-body space-y-1">
												<div class="badge-row flex items-center">
													<span class="badge-label text-gray-700 dark:text-gray-300 font-medium mr-1">Classification:</span>
													<span class="badge-value font-semibold" :style="{ color: getCustomerColor(customer) }">
														{{ customer[customerClassification] }}
													</span>
												</div>
												<div class="badge-row flex items-center">
													<span class="badge-label text-gray-700 dark:text-gray-300 font-medium mr-1">Type:</span>
													<span class="badge-value text-gray-900 dark:text-gray-100">{{ customerClassification }}</span>
												</div>
												<div class="badge-row flex items-center">
													<span class="badge-label text-gray-700 dark:text-gray-300 font-medium mr-1">Sales Rep:</span>
													<span class="badge-value text-gray-900 dark:text-gray-100 flex items-center">
														<div 
															class="w-2 h-2 rounded-full mr-1"
															:style="{ backgroundColor: getPersonnelColor(customer.sales_person) }"
														></div>
														{{ customer.sales_person }}
													</span>
												</div>
											</div>
											
											<!-- Badge arrow pointing to marker -->
											<div class="badge-arrow"></div>
										</div>
									</div>
								</div>
							</transition>
						</div>
					</CustomMarker>
				</MarkerCluster>
				<Deferred :data="['boundaries']">
					<template #fallback>
						<div>Loading...</div>
					</template>
					<template v-if="renderableBoundaries.length > 0">
						<Polygon
							v-for="boundary in renderableBoundaries"
							:key="boundary.id"
							:options="getOps(boundary)"
							@click="handlePolygonClick(boundary)"
							@mouseover="(event) => handlePolygonHover(boundary, event)"
							@mouseleave="handlePolygonLeave"
						/>
					</template>
					<div v-else class="text-center text-gray-500 p-4">
						No boundaries to display. Map ready: {{ mapReady }}, Boundaries count: {{ processedBoundaries(boundaries).length }}
					</div>
				</Deferred>
			</template>
			
			<!-- Single InfoWindow -->
			<InfoWindow
				v-if="showInfoWindow && infoWindowPosition && mapReady"
				:options="{
					position: infoWindowPosition
				}"
			>
				<!-- Custom content from setInfoWindowContent function -->
				<div v-if="infoWindowContent" v-html="infoWindowContent"></div>
				
				<!-- Default boundary content -->
				<div v-else-if="currentBoundary" class="bg-white rounded-lg shadow-xl border border-gray-200 p-4 min-w-[280px] max-w-[320px]">
					<!-- Header Section -->
					<div class="border-b border-gray-200 pb-3 mb-3">
						<h3 class="text-lg font-semibold text-gray-800 mb-1">
							{{ currentBoundary.name }}
						</h3>
						<div class="flex items-center">
							<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
								{{ currentBoundary.type?.charAt(0).toUpperCase() + currentBoundary.type?.slice(1) }}
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
			
			<!-- Hover InfoWindow that follows mouse -->
			<InfoWindow
				v-if="showHoverInfoWindow && mousePosition && hoveredBoundaryData && mapReady"
				:options="{
					position: mousePosition
				}"
			>
				<div class="bg-white rounded-lg shadow-lg border border-gray-200 p-3 min-w-[200px] max-w-[250px]">
					<!-- Header Section -->
					<div class="border-b border-gray-200 pb-2 mb-2">
						<h4 class="text-sm font-semibold text-gray-800">
							{{ hoveredBoundaryData.name }}
						</h4>
						<div class="flex items-center">
							<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
								{{ hoveredBoundaryData.type?.charAt(0).toUpperCase() + hoveredBoundaryData.type?.slice(1) }}
							</span>
						</div>
					</div>
					<!-- Simple content for hover -->
					<div class="text-xs text-gray-600">
						Click to view details
					</div>
				</div>
			</InfoWindow>
			
			<template v-if="showHeatMap">
				<!-- <HeatmapLayer :data="heatmapData" /> -->
			</template>
			<template v-if="showHeatCustomers">
				<!-- Placeholder for customer heatmap -->
			</template>
		</GoogleMap>
		<div class="absolute right-2 top-1/2 -translate-y-1/2 space-y-2 z-20" :style="{ right: showSidebar && !isFullScreenSidebar ? 'calc(30% + 8px)' : '8px' }">
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
		<div class="absolute left-2 top-1/2 -translate-y-1/2 space-y-2 z-20">
			<button @click="toggleHeatMap"
				:class="[getControlBaseClasses, showHeatMap ? getControlActiveClasses : '']">
				<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
					viewBox="0 0 24 24">
					<path fill="currentColor"
						d="M4 14q0-2.825 1.675-5.425t4.6-4.55q.55-.375 1.138-.038T12 5v1.3q0 .85.588 1.425t1.437.575q.425 0 .813-.188t.687-.537q.2-.25.513-.312t.587.137Q18.2 8.525 19.1 10.275T20 14q0 2.2-1.075 4.013T16.1 20.874q.425-.6.663-1.312T17 18.05q0-1-.375-1.888t-1.075-1.587L12 11.1l-3.525 3.475q-.725.725-1.1 1.6T7 18.05q0 .8.238 1.513t.662 1.312q-1.75-1.05-2.825-2.863T4 14m8-.1l2.125 2.075q.425.425.65.95T15 18.05q0 1.225-.875 2.088T12 21t-2.125-.862T9 18.05q0-.575.225-1.112t.65-.963z" />
				</svg>
			</button>
			<button @click="toggleMarkersModal"
				:class="[getControlBaseClasses, showMarkersModal ? getControlActiveClasses : '']">
				<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
					viewBox="0 0 24 24">
					<path fill="currentColor"
						d="M12 21.325q-.35 0-.7-.125t-.625-.375Q9.05 19.325 7.8 17.9t-2.087-2.762t-1.275-2.575T4 10.2q0-3.75 2.413-5.975T12 2t5.588 2.225T20 10.2q0 1.125-.437 2.363t-1.275 2.575T16.2 17.9t-2.875 2.925q-.275.25-.625.375t-.7.125M12 12q.825 0 1.413-.587T14 10t-.587-1.412T12 8t-1.412.588T10 10t.588 1.413T12 12" />
				</svg>
			</button>
			<button @click="toggleBoundaries"
				:class="[getControlBaseClasses, drillDownData.boundariesVisible ? getControlActiveClasses : '']">
				<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
					viewBox="0 0 512 512">
					<path fill="currentColor"
						d="M480 150L256 48L32 150l224 104zM255.71 392.95l-144.81-66.2L32 362l224 102l224-102l-78.69-35.3z" />
					<path fill="currentColor" d="m480 256l-75.53-33.53L256.1 290.6l-148.77-68.17L32 256l224 102z" />
				</svg>
			</button>
			<button @click="togglePersonnelCard"
				:class="[getControlBaseClasses, showPersonnelCard ? getControlActiveClasses : '']">
				<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 32 32"><path fill="currentColor" d="M30 6V4h-3V2h-2v2h-1c-1.103 0-2 .898-2 2v2c0 1.103.897 2 2 2h4v2h-6v2h3v2h2v-2h1c1.103 0 2-.897 2-2v-2c0-1.102-.897-2-2-2h-4V6zm-6 14v2h2.586L23 25.586l-2.292-2.293a1 1 0 0 0-.706-.293H20a1 1 0 0 0-.706.293L14 28.586L15.414 30l4.587-4.586l2.292 2.293a1 1 0 0 0 1.414 0L28 23.414V26h2v-6zM4 30H2v-5c0-3.86 3.14-7 7-7h6c1.989 0 3.89.85 5.217 2.333l-1.49 1.334A5 5 0 0 0 15 20H9c-2.757 0-5 2.243-5 5zm8-14a7 7 0 1 0 0-14a7 7 0 0 0 0 14m0-12a5 5 0 1 1 0 10a5 5 0 0 1 0-10"/></svg>
			</button>
		</div>
		<DrillDown class="absolute top-10 left-1/2 -translate-x-1/2 z-10" v-model="drillDownData" :counties="counties" :constituencies="constituencies" :wards="wards" :style="{ left: showSidebar && !isFullScreenSidebar ? '35%' : '50%' }" />
		
		<!-- Personnel Card -->
		<div v-if="showPersonnelCard" class="absolute sm:left-16 left-4 top-24 z-20 bg-white dark:bg-gray-800 rounded-lg shadow-xl border border-gray-200 dark:border-gray-700 p-4 min-w-[380px] max-w-[480px] max-h-[500px] overflow-y-auto" :style="{ right: showSidebar && !isFullScreenSidebar ? 'calc(30% + 20px)' : 'auto' }">
			<!-- Header -->
			<div class="flex items-center justify-between mb-4 pb-3 border-b border-gray-200 dark:border-gray-600">
				<h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 flex">
					<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 32 32"><path fill="currentColor" d="M30 6V4h-3V2h-2v2h-1c-1.103 0-2 .898-2 2v2c0 1.103.897 2 2 2h4v2h-6v2h3v2h2v-2h1c1.103 0 2-.897 2-2v-2c0-1.102-.897-2-2-2h-4V6zm-6 14v2h2.586L23 25.586l-2.292-2.293a1 1 0 0 0-.706-.293H20a1 1 0 0 0-.706.293L14 28.586L15.414 30l4.587-4.586l2.292 2.293a1 1 0 0 0 1.414 0L28 23.414V26h2v-6zM4 30H2v-5c0-3.86 3.14-7 7-7h6c1.989 0 3.89.85 5.217 2.333l-1.49 1.334A5 5 0 0 0 15 20H9c-2.757 0-5 2.243-5 5zm8-14a7 7 0 1 0 0-14a7 7 0 0 0 0 14m0-12a5 5 0 1 1 0 10a5 5 0 0 1 0-10"/></svg>
					<span class="ml-2">Sales Personnel</span>
				</h3>
				<div class="flex items-center space-x-2">
					<!-- Toggle All Button in Header -->
					<button 
						@click="toggleAllPersonnel"
						:class="[
							'flex items-center justify-center space-x-1 px-2 py-1 rounded-md border transition-colors text-xs font-medium',
							personnelVisibility.showAll 
								? 'bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-700 text-blue-700 dark:text-blue-300' 
								: 'bg-gray-50 dark:bg-gray-700 border-gray-200 dark:border-gray-600 text-gray-700 dark:text-gray-300'
						]"
					>
						<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
							<path d="M9 12l2 2 4-4"/>
							<path d="M21 12c-1 0-3-1-3-3s2-3 3-3 3 1 3 3-2 3-3 3"/>
							<path d="M3 12c1 0 3-1 3-3s-2-3-3-3-3 1-3 3 2 3 3 3"/>
							<path d="M13 12h3a2 2 0 0 1 2 2v1"/>
							<path d="M13 12H9a2 2 0 0 0-2 2v1"/>
							<path d="M13 12v-1a2 2 0 0 1 2-2h3"/>
							<path d="M13 12v-1a2 2 0 0 0-2-2H9"/>
						</svg>
						<span>{{ personnelVisibility.showAll ? 'Hide All' : 'Show All' }}</span>
					</button>
					<button @click="togglePersonnelCard" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
						<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
							<line x1="18" y1="6" x2="6" y2="18"></line>
							<line x1="6" y1="6" x2="18" y2="18"></line>
						</svg>
					</button>
				</div>
			</div>

			
			<!-- Personnel List -->
			<div v-if="(salesPersonnelData && salesPersonnelData.length > 0) || (salesPersonnel && salesPersonnel.length > 0)" class="space-y-2">
				<div 
					v-for="person in (salesPersonnelData.length > 0 ? salesPersonnelData : salesPersonnel)" 
					:key="person.id || person.name" 
					:class="[
						'flex items-center space-x-3 p-3 rounded-lg transition-all duration-200 cursor-pointer',
						hoveredPersonnel === person.name 
							? 'bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700' 
							: 'hover:bg-gray-50 dark:hover:bg-gray-700'
					]"
					@mouseenter="handlePersonnelHover(person.name)"
					@mouseleave="handlePersonnelLeave"
					@click="togglePersonnelVisibility(person.name)"
				>
					<!-- Color Indicator -->
					<div class="flex-shrink-0">
						<div 
							class="w-4 h-4 rounded-full border-2 border-white dark:border-gray-800 shadow-sm"
							:style="{ backgroundColor: person.color }"
						></div>
					</div>
					
					<!-- Profile Photo -->
					<div class="flex-shrink-0">
						<img :src="`https://ui-avatars.com/api/?name=${encodeURIComponent(person.name)}&color=${person.color.replace('#', '')}&background=EBF4FF`" 
							 :alt="person.name"
							 class="w-10 h-10 rounded-full border-2 border-gray-200 dark:border-gray-600"
							 @error="$event.target.src = `https://ui-avatars.com/api/?name=${encodeURIComponent(person.name)}&color=7F9CF5&background=EBF4FF`">
					</div>
					
					<!-- Personnel Info -->
					<div class="flex-1 min-w-0">
						<p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">{{ person.name }}</p>
						<p v-if="person.code" class="text-xs text-gray-500 dark:text-gray-400 truncate">Code: {{ person.code }}</p>
					</div>
					
					<!-- Toggle Switch -->
					<div class="flex-shrink-0">
						<div 
							:class="[
								'relative inline-flex h-5 w-9 items-center rounded-full transition-colors',
								personnelVisibility.individual[person.name] 
									? 'bg-blue-600' 
									: 'bg-gray-200 dark:bg-gray-600'
							]"
						>
							<span 
								:class="[
									'inline-block h-3 w-3 transform rounded-full bg-white transition-transform',
									personnelVisibility.individual[person.name] ? 'translate-x-5' : 'translate-x-1'
								]"
							></span>
						</div>
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
	
	<!-- Map Sidebar -->
	<MapSidebar 
		:isOpen="showSidebar" 
		@toggle="toggleSidebar" 
		@close="closeSidebar"
	/>
	
	<!-- Marker Layers Modal -->
	<transition name="fade">
		<div v-if="showMarkersModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-40" :style="{ left: showSidebar && !isFullScreenSidebar ? '0' : '0', right: showSidebar && !isFullScreenSidebar ? '30%' : '0' }">
			<div class="bg-white dark:bg-gray-900 rounded-xl shadow-2xl w-full max-w-md mx-4 p-6 relative">
				<!-- Close Button -->
				<button
					@click="showMarkersModal = false"
					class="absolute top-3 right-3 text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition-colors"
					aria-label="Close"
				>
					<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
						<line x1="18" y1="6" x2="6" y2="18"/>
						<line x1="6" y1="6" x2="18" y2="18"/>
					</svg>
				</button>
				<!-- Modal Title -->
				<h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4 flex items-center">
					<svg class="mr-2 text-red-500" width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
						<circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" fill="none"/>
						<circle cx="12" cy="12" r="4" fill="currentColor"/>
					</svg>
					Marker Layers
				</h2>
				<div class="divide-y divide-gray-200 dark:divide-gray-700">
					<!-- Customers Layer -->
					<div class="py-3">
						<label class="flex items-center cursor-pointer group">
							<input
								type="checkbox"
								class="form-checkbox h-5 w-5 text-red-600 transition duration-150 ease-in-out"
								v-model="markerLayers.customers"
							>
							<span class="ml-3 text-gray-900 dark:text-gray-100 font-medium flex items-center justify-between w-full">
								<div class="flex items-center">
									<svg class="w-5 h-5 mr-2 text-red-500" fill="currentColor" viewBox="0 0 20 20">
										<path d="M10 2a6 6 0 016 6c0 4.418-6 10-6 10S4 12.418 4 8a6 6 0 016-6zm0 8a2 2 0 100-4 2 2 0 000 4z"/>
									</svg>
									Customers
								</div>
								
								<!-- Inline Classification Toggle - Only show when checked -->
								<div v-if="markerLayers.customers" class="flex items-center space-x-2">
									<span class="text-xs text-gray-500 dark:text-gray-400">Type:</span>
									<div class="flex bg-gray-100 dark:bg-gray-700 rounded-lg p-0.5">
										<button
											@click="customerClassification = 'KD'"
											:class="[
												'px-2 py-0.5 text-xs font-medium rounded transition-colors',
												customerClassification === 'KD' 
													? 'bg-white dark:bg-gray-600 text-gray-900 dark:text-gray-100 shadow-sm' 
													: 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100'
											]"
										>
											KD
										</button>
										<button
											@click="customerClassification = 'RE'"
											:class="[
												'px-2 py-0.5 text-xs font-medium rounded transition-colors',
												customerClassification === 'RE' 
													? 'bg-white dark:bg-gray-600 text-gray-900 dark:text-gray-100 shadow-sm' 
													: 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100'
											]"
										>
											RE
										</button>
									</div>
									
									<!-- Color Legend Button -->
									<button
										@click="toggleColorLegend"
										class="flex items-center space-x-1 text-xs text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 transition-colors"
									>
										<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
											<circle cx="12" cy="12" r="10"/>
											<path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/>
											<line x1="12" y1="17" x2="12.01" y2="17"/>
										</svg>
									</button>
								</div>
							</span>
						</label>
					</div>
					<label
						v-for="mapData in available_map_data"
						:key="mapData.id"
						class="flex items-center py-3 cursor-pointer group"
					>
						<input
							type="checkbox"
							class="form-checkbox h-5 w-5 text-red-600 transition duration-150 ease-in-out"
							:checked="markerLayers.mapDataIds && markerLayers.mapDataIds.includes(mapData.id)"
							@change="e => {
								const checked = e.target.checked;
								if (!markerLayers.mapDataIds) markerLayers.mapDataIds = [];
								if (checked) {
									if (!markerLayers.mapDataIds.includes(mapData.id)) markerLayers.mapDataIds.push(mapData.id);
								} else {
									markerLayers.mapDataIds = markerLayers.mapDataIds.filter(id => id !== mapData.id);
								}
							}"
						>
						<span class="ml-3 text-gray-900 dark:text-gray-100 font-medium flex flex-col">
							<span class="flex items-center">
								<svg class="w-5 h-5 mr-2 text-red-500" fill="currentColor" viewBox="0 0 20 20">
									<circle cx="10" cy="10" r="8" />
								</svg>
								{{ mapData.name }}
								<span v-if="mapData.type" class="ml-2 text-xs px-2 py-0.5 rounded bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400">{{ mapData.type }}</span>
							</span>
							<span v-if="mapData.description" class="text-xs text-gray-500 dark:text-gray-400">{{ mapData.description }}</span>
							<span v-if="typeof mapData.dynamic_row_count === 'number'" class="text-xs text-gray-400 dark:text-gray-500">Rows: {{ mapData.dynamic_row_count }}</span>
						</span>
					</label>
				</div>
				<div class="mt-6 flex justify-end">
					<button
						@click="showMarkersModal = false"
						class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg shadow transition-colors font-semibold"
					>
						Done
					</button>
				</div>
			</div>
		</div>
	</transition>
	
	<!-- Color Legend Popup -->
	<transition name="fade">
		<div v-if="showColorLegend" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-40" :style="{ left: showSidebar && !isFullScreenSidebar ? '0' : '0', right: showSidebar && !isFullScreenSidebar ? '30%' : '0' }">
			<div class="bg-white dark:bg-gray-900 rounded-xl shadow-2xl w-full max-w-md mx-4 p-6 relative">
				<!-- Close Button -->
				<button
					@click="showColorLegend = false"
					class="absolute top-3 right-3 text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition-colors"
					aria-label="Close"
				>
					<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
						<line x1="18" y1="6" x2="6" y2="18"/>
						<line x1="6" y1="6" x2="18" y2="18"/>
					</svg>
				</button>
				
				<!-- Modal Title -->
				<h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4 flex items-center">
					<svg class="mr-2 text-blue-500" width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
						<circle cx="12" cy="12" r="10"/>
						<path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/>
						<line x1="12" y1="17" x2="12.01" y2="17"/>
					</svg>
					{{ customerClassification }} Color Legend
				</h2>
				
				<!-- Color Legend Content -->
				<div class="space-y-3 max-h-96 overflow-y-auto">
					<div v-for="(color, code) in colorMappings[customerClassification]" :key="code" 
						 class="flex items-center space-x-3 p-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
						<!-- Color Indicator -->
						<div 
							class="w-6 h-6 rounded-full border-2 border-gray-200 dark:border-gray-600 flex-shrink-0"
							:style="{ backgroundColor: color }"
						></div>
						
						<!-- Code and Description -->
						<div class="flex-1 min-w-0">
							<div class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ code }}</div>
							<div class="text-xs text-gray-500 dark:text-gray-400">
								{{ getCodeDescription(code) }}
							</div>
						</div>
					</div>
				</div>
				
				<!-- Footer -->
				<div class="mt-6 flex justify-end">
					<button
						@click="showColorLegend = false"
						class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg shadow transition-colors font-semibold"
					>
						Close
					</button>
				</div>
			</div>
		</div>
	</transition>
</template>

<style>
.gm-style .gm-control-active {
	background-color: hsl(var(--primary)) !important;
	border-color: hsl(var(--primary)) !important;
	color: #fff !important;
}
.animated-bounce {
	animation: bounce 1.2s infinite;
}
@keyframes bounce {
	0%, 100% {
		transform: translateY(0);
	}
	20% {
		transform: translateY(-12px);
	}
	40% {
		transform: translateY(0);
	}
	60% {
		transform: translateY(-6px);
	}
	80% {
		transform: translateY(0);
	}
}

/* Beautiful Badge Styles */
.marker-badge {
	position: relative;
	background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
	border: 1px solid #e2e8f0;
	border-radius: 12px;
	box-shadow: 
		0 10px 25px rgba(0, 0, 0, 0.1),
		0 4px 6px rgba(0, 0, 0, 0.05),
		0 0 0 1px rgba(255, 255, 255, 0.8);
	backdrop-filter: blur(10px);
	min-width: 200px;
	max-width: 280px;
	overflow: hidden;
}

.badge-content {
	position: relative;
	padding: 16px;
	z-index: 2;
}

.badge-header {
	margin-bottom: 12px;
	padding-bottom: 8px;
	border-bottom: 1px solid #e2e8f0;
}

.badge-title {
	font-size: 14px;
	font-weight: 600;
	color: #1e293b;
	margin: 0 0 4px 0;
	line-height: 1.3;
}

.badge-subtitle {
	font-size: 11px;
	color: #64748b;
	font-weight: 500;
	line-height: 1.2;
}

.badge-body {
	display: flex;
	flex-direction: column;
	gap: 6px;
}

.badge-row {
	display: flex;
	justify-content: space-between;
	align-items: center;
	font-size: 12px;
}

.badge-label {
	color: #64748b;
	font-weight: 500;
}

.badge-value {
	color: #1e293b;
	font-weight: 600;
	font-size: 11px;
}

.badge-arrow {
	position: absolute;
	left: -8px;
	top: 50%;
	transform: translateY(-50%);
	width: 0;
	height: 0;
	border-top: 8px solid transparent;
	border-bottom: 8px solid transparent;
	border-right: 8px solid #ffffff;
	filter: drop-shadow(-2px 0 2px rgba(0, 0, 0, 0.1));
}

.badge-arrow::before {
	content: '';
	position: absolute;
	left: 1px;
	top: -8px;
	width: 0;
	height: 0;
	border-top: 8px solid transparent;
	border-bottom: 8px solid transparent;
	border-right: 8px solid #e2e8f0;
}

/* Badge Animation */
.badge-fade-enter-active {
	transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.badge-fade-leave-active {
	transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

.badge-fade-enter-from {
	opacity: 0;
	transform: translateX(-10px) scale(0.95);
}

.badge-fade-leave-to {
	opacity: 0;
	transform: translateX(-10px) scale(0.95);
}

.badge-fade-enter-to,
.badge-fade-leave-from {
	opacity: 1;
	transform: translateX(0) scale(1);
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
	.marker-badge {
		background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
		border-color: #334155;
		box-shadow: 
			0 10px 25px rgba(0, 0, 0, 0.3),
			0 4px 6px rgba(0, 0, 0, 0.2),
			0 0 0 1px rgba(255, 255, 255, 0.1);
	}
	
	.badge-title {
		color: #f1f5f9;
	}
	
	.badge-subtitle {
		color: #94a3b8;
	}
	
	.badge-label {
		color: #94a3b8;
	}
	
	.badge-value {
		color: #f1f5f9;
	}
	
	.badge-header {
		border-bottom-color: #334155;
	}
	
	.badge-arrow {
		border-right-color: #1e293b;
	}
	
	.badge-arrow::before {
		border-right-color: #334155;
	}
}

/* Hover effects */
.marker-badge:hover {
	transform: translateY(-2px);
	box-shadow: 
		0 15px 35px rgba(0, 0, 0, 0.15),
		0 6px 10px rgba(0, 0, 0, 0.08),
		0 0 0 1px rgba(255, 255, 255, 0.9);
	transition: all 0.2s ease;
}

@media (prefers-color-scheme: dark) {
	.marker-badge:hover {
		box-shadow: 
			0 15px 35px rgba(0, 0, 0, 0.4),
			0 6px 10px rgba(0, 0, 0, 0.3),
			0 0 0 1px rgba(255, 255, 255, 0.15);
	}
}

/* Personnel marker border styling */
.border-3 {
	border-width: 3px;
}

/* Personnel hover effects */
.personnel-hover-highlight {
	transition: all 0.2s ease-in-out;
}

.personnel-hover-highlight:hover {
	transform: scale(1.05);
	box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}
</style>