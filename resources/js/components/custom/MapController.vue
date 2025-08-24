<template>
<div>
	<GoogleMap :apiKey="api_key" :center="userLocation" :zoom="zoom" :mapTypeId="mapTypeId" :disableDefaultUi="true"
			:styles="mapTypeId === 'roadmap' ? colgateMapStyles : null" :style="{ width: '100%', height: mapHeight }"></GoogleMap>
</div>
</template>
<script setup>
import { ref } from 'vue';

const { api_key, mapHeight, center, zoom, mapTypeId, colgateMapStyles } = defineProps({
	api_key: String,
	mapHeight: String,
	center: Object,
	zoom: Number,
	mapTypeId: String,
	colgateMapStyles: Array
});
</script>