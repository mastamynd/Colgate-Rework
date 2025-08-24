<script setup lang="ts">
interface ChartData {
	label: string;
	value: number;
	color?: string;
}

interface Props {
	title: string;
	data: ChartData[];
	type?: 'bar' | 'pie' | 'line';
	height?: number;
}

const props = withDefaults(defineProps<Props>(), {
	type: 'bar',
	height: 300
});

// Calculate total for pie chart
const total = props.data.reduce((sum, item) => sum + item.value, 0);

// Get max value for bar chart scaling
const maxValue = Math.max(...props.data.map(item => item.value));
</script>

<template>
	<div class="rounded-xl border bg-card p-6">
		<h3 class="text-lg font-semibold mb-4">{{ title }}</h3>
		
		<!-- Bar Chart -->
		<div v-if="type === 'bar'" class="space-y-3" :style="`height: ${height}px`">
			<div 
				v-for="(item, index) in data" 
				:key="index"
				class="flex items-center gap-3"
			>
				<div class="w-24 text-sm text-muted-foreground truncate">{{ item.label }}</div>
				<div class="flex-1 flex items-center gap-2">
					<div 
						class="rounded-full transition-all duration-500 ease-out"
						:style="`width: ${(item.value / maxValue) * 100}%; height: 8px; background-color: ${item.color || 'hsl(var(--primary))'}`"
					></div>
					<span class="text-sm font-medium w-12 text-right">{{ item.value }}</span>
				</div>
			</div>
		</div>

		<!-- Pie Chart (Simple CSS version) -->
		<div v-else-if="type === 'pie'" class="flex items-center justify-center" :style="`height: ${height}px`">
			<div class="grid grid-cols-1 gap-3 w-full">
				<div 
					v-for="(item, index) in data.slice(0, 5)" 
					:key="index"
					class="flex items-center justify-between"
				>
					<div class="flex items-center gap-3">
						<div 
							class="w-4 h-4 rounded-full"
							:style="`background-color: ${item.color || `hsl(${index * 137.5}, 70%, 50%)`}`"
						></div>
						<span class="text-sm">{{ item.label }}</span>
					</div>
					<div class="text-right">
						<div class="text-sm font-medium">{{ item.value }}</div>
						<div class="text-xs text-muted-foreground">{{ Math.round((item.value / total) * 100) }}%</div>
					</div>
				</div>
			</div>
		</div>

		<!-- Line Chart (Simple version) -->
		<div v-else-if="type === 'line'" class="relative" :style="`height: ${height}px`">
			<div class="flex items-end justify-between h-full gap-1">
				<div 
					v-for="(item, index) in data" 
					:key="index"
					class="flex flex-col items-center gap-2 flex-1"
				>
					<div 
						class="w-full bg-primary/20 rounded-t-sm transition-all duration-500 ease-out flex items-end justify-center relative"
						:style="`height: ${(item.value / maxValue) * 80}%; min-height: 4px; background-color: ${item.color || 'hsl(var(--primary))'}`"
					>
						<span class="absolute -top-6 text-xs font-medium">{{ item.value }}</span>
					</div>
					<span class="text-xs text-muted-foreground text-center rotate-45 origin-center mt-2">{{ item.label }}</span>
				</div>
			</div>
		</div>
	</div>
</template>
