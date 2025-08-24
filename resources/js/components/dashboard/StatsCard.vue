<script setup lang="ts">
interface Props {
	title: string;
	value: string | number;
	subtitle?: string;
	icon?: any;
	trend?: {
		value: number;
		label: string;
		isPositive: boolean;
	};
}

defineProps<Props>();
</script>

<template>
	<div class="relative overflow-hidden rounded-xl border bg-card p-6">
		<div class="flex items-center justify-between">
			<div>
				<p class="text-sm font-medium text-muted-foreground">{{ title }}</p>
				<p class="text-3xl font-bold">{{ value }}</p>
				<p v-if="subtitle" class="text-sm text-muted-foreground">{{ subtitle }}</p>
				
				<div v-if="trend" class="flex items-center gap-2 mt-2">
					<div 
						class="flex items-center gap-1 text-xs px-2 py-1 rounded-full"
						:class="trend.isPositive ? 'bg-green-100 text-green-700 dark:bg-green-900/20 dark:text-green-400' : 'bg-red-100 text-red-700 dark:bg-red-900/20 dark:text-red-400'"
					>
						<span>{{ trend.isPositive ? '↗' : '↘' }}</span>
						<span>{{ Math.abs(trend.value) }}%</span>
					</div>
					<span class="text-xs text-muted-foreground">{{ trend.label }}</span>
				</div>
			</div>
			
			<div v-if="icon" class="opacity-60">
				<component :is="icon" class="h-8 w-8" />
			</div>
		</div>
	</div>
</template>
