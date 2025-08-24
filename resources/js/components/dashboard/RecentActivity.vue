<script setup lang="ts">
interface ActivityItem {
	id: string;
	name: string;
	phone?: string;
	sales_person?: string;
	route?: string;
	county?: string;
	is_active: boolean;
	created_at: string;
}

interface Props {
	title: string;
	items: ActivityItem[];
	emptyMessage?: string;
}

withDefaults(defineProps<Props>(), {
	emptyMessage: 'No recent activity'
});
</script>

<template>
	<div class="rounded-xl border bg-card p-6">
		<h3 class="text-lg font-semibold mb-4">{{ title }}</h3>
		
		<div v-if="items.length === 0" class="flex flex-col items-center justify-center py-8 text-center">
			<div class="text-muted-foreground mb-2">
				<svg class="w-12 h-12 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
					<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
				</svg>
			</div>
			<p class="text-sm text-muted-foreground">{{ emptyMessage }}</p>
		</div>

		<div v-else class="space-y-3 max-h-96 overflow-y-auto">
			<div 
				v-for="item in items" 
				:key="item.id"
				class="flex items-center justify-between p-3 rounded-lg border transition-colors hover:bg-muted/50"
			>
				<div class="flex-1 min-w-0">
					<div class="flex items-center gap-2">
						<h4 class="font-medium truncate">{{ item.name }}</h4>
						<span 
							class="inline-flex items-center px-2 py-1 rounded-full text-xs"
							:class="item.is_active 
								? 'bg-green-100 text-green-700 dark:bg-green-900/20 dark:text-green-400' 
								: 'bg-gray-100 text-gray-700 dark:bg-gray-900/20 dark:text-gray-400'"
						>
							{{ item.is_active ? 'Active' : 'Inactive' }}
						</span>
					</div>
					
					<div class="mt-1 space-y-1">
						<p v-if="item.phone" class="text-sm text-muted-foreground">{{ item.phone }}</p>
						<div class="flex gap-4 text-xs text-muted-foreground">
							<span v-if="item.sales_person">Sales: {{ item.sales_person }}</span>
							<span v-if="item.route">Route: {{ item.route }}</span>
							<span v-if="item.county">{{ item.county }}</span>
						</div>
					</div>
				</div>
				
				<div class="text-right">
					<p class="text-xs text-muted-foreground">{{ item.created_at }}</p>
				</div>
			</div>
		</div>
	</div>
</template>
