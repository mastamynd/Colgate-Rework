<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import Heading from '@/components/Heading.vue';
import { type BreadcrumbItem, type ReReference } from '@/types';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import {
	Dialog,
	DialogContent,
	DialogHeader,
	DialogTitle,
	DialogFooter
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { PlusIcon, PencilIcon, TrashIcon, ShieldXIcon, EyeIcon, UserCheckIcon, UserXIcon } from 'lucide-vue-next';
import ColorPicker from '@/components/custom/ColorPicker.vue';
import Pagination from '@/components/custom/Pagination.vue';

const props = defineProps({
	reReferences: {
		type: Object,
		required: true,
		default: () => { }
	},
	filters: {
		type: Object,
		default: () => ({})
	}
});

const breadcrumbs: BreadcrumbItem[] = [
	{
		title: 'RE References',
		href: route('re-references.index'),
	},
];

const showAddDialog = ref(false);
const editingReReference = ref<ReReference | null>(null);

const form = useForm({
	code: '',
	name: '',
	color: '#8B5CF6',
	is_active: true
});

const filterForm = useForm({
	search: props.filters.search || '',
	status: props.filters.status || '',
	per_page: props.filters.per_page || '15'
});

// Page size options
const pageSizeOptions = [
	{ value: '10', label: '10 per page' },
	{ value: '15', label: '15 per page' },
	{ value: '25', label: '25 per page' },
	{ value: '50', label: '50 per page' },
	{ value: '100', label: '100 per page' }
];

// Watch for changes in page size
watch(() => filterForm.per_page, () => {
	applyFilters();
});

// Apply filters function
const applyFilters = () => {
	filterForm.get(route('re-references.index'), {
		preserveState: true,
		preserveScroll: true
	});
};

// Clear filters function
const clearFilters = () => {
	filterForm.reset();
	filterForm.per_page = '15';
	filterForm.get(route('re-references.index'));
};

const closeDialog = () => {
	showAddDialog.value = false;
	editingReReference.value = null;
	form.reset();
	form.clearErrors();
};

const editReReference = (reReference: ReReference) => {
	editingReReference.value = reReference;
	form.code = reReference.code || '';
	form.name = reReference.name || '';
	form.color = reReference.color || '#8B5CF6';
	form.is_active = reReference.is_active || false;
	showAddDialog.value = true;
};

const handleSubmit = async () => {
	try {
		if (editingReReference.value) {
			form.put(route('re-references.update', editingReReference.value.id), {
				onSuccess: () => {
					closeDialog();
				},
				onError: (errors: any) => {
					console.error('Validation errors:', errors);
				}
			});
		} else {
			form.post(route('re-references.store'), {
				onSuccess: () => {
					closeDialog();
				},
				onError: (errors: any) => {
					console.error('Validation errors:', errors);
				}
			});
		}
	} catch (error) {
		console.error('Error saving RE reference:', error);
	}
};

const deleteReReference = (reReference: ReReference) => {
	if (confirm(`Are you sure you want to delete ${reReference.name}? This action cannot be undone.`)) {
		router.delete(route('re-references.destroy', reReference.id), {
			onSuccess: () => {
				// Data will be automatically refreshed by Inertia
			},
			onError: (errors: any) => {
				console.error('Error deleting RE reference:', errors);
			}
		});
	}
};

const toggleStatus = (reReference: ReReference) => {
	const routeName = reReference.is_active 
		? 're-references.deactivate' 
		: 're-references.activate';
	
	router.patch(route(routeName, reReference.id), {}, {
		onSuccess: () => {
			// Data will be automatically refreshed by Inertia
		},
		onError: (errors: any) => {
			console.error('Error toggling status:', errors);
		}
	});
};
</script>

<template>
	<Head title="RE References" description="Manage RE references" />

	<AppLayout :breadcrumbs="breadcrumbs">
		<div class="px-4 py-6">
			<div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6">
				<Heading title="RE References" description="Manage RE reference classifications" />
				<Button @click="showAddDialog = true" class="w-full sm:w-auto">
					Add RE Reference
					<PlusIcon class="h-4 w-4 ml-2" />
				</Button>
			</div>

			<!-- Filters Section -->
			<div class="bg-card rounded-lg border p-4 mb-4">
				<h3 class="text-sm font-medium mb-3">Filters</h3>
				<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
					<!-- Search -->
					<div class="space-y-2">
						<Label for="search">Search</Label>
						<Input 
							id="search"
							v-model="filterForm.search"
							placeholder="Search RE references..."
							@input="applyFilters"
						/>
					</div>
					
					<!-- Status Filter -->
					<div class="space-y-2">
						<Label for="status">Status</Label>
						<select 
							id="status"
							v-model="filterForm.status"
							@change="applyFilters"
							class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
						>
							<option value="">All Status</option>
							<option value="active">Active</option>
							<option value="inactive">Inactive</option>
						</select>
					</div>
					
					<!-- Page Size Filter -->
					<div class="space-y-2">
						<Label for="per_page">Per Page</Label>
						<select 
							id="per_page"
							v-model="filterForm.per_page"
							@change="applyFilters"
							class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
						>
							<option v-for="option in pageSizeOptions" :key="option.value" :value="option.value">
								{{ option.label }}
							</option>
						</select>
					</div>
				</div>
				
				<!-- Filter Actions -->
				<div class="flex justify-end gap-2 mt-4">
					<Button variant="ghost" @click="clearFilters" :disabled="filterForm.processing">
						Clear Filters
					</Button>
				</div>
			</div>

			<div class="flex justify-between my-2 items-center flex-col sm:flex-row gap-1">
				<span class="py-2 text-sm text-muted-foreground">
					Showing {{ reReferences.data?.length || 0 }} RE references
				</span>
			</div>

			<!-- Desktop Table View -->
			<div class="hidden lg:block rounded-lg border bg-card">
				<div class="relative overflow-x-auto">
					<table class="w-full text-sm text-left">
						<thead class="text-xs uppercase bg-muted">
							<tr>
								<th scope="col" class="px-6 py-3">Code</th>
								<th scope="col" class="px-6 py-3">Name</th>
								<th scope="col" class="px-6 py-3">Color</th>
								<th scope="col" class="px-6 py-3">Customers</th>
								<th scope="col" class="px-6 py-3">Status</th>
								<th scope="col" class="px-6 py-3">Actions</th>
							</tr>
						</thead>
						<tbody>
							<tr v-if="!reReferences.data || reReferences.data.length === 0" class="border-b">
								<td colspan="6" class="px-6 py-8">
									<div class="flex flex-col items-center justify-center text-center">
										<ShieldXIcon class="h-12 w-12 text-muted-foreground/50" />
										<h3 class="mt-4 text-sm font-medium text-muted-foreground">No RE references found</h3>
										<p class="mt-1 text-sm text-muted-foreground/80">Get started by creating a new RE reference.</p>
									</div>
								</td>
							</tr>
							<tr v-for="reReference in reReferences.data" :key="reReference.id" class="border-b hover:bg-muted/50 transition-colors">
								<td class="px-6 py-4 font-medium">{{ reReference.code }}</td>
								<td class="px-6 py-4">{{ reReference.name }}</td>
								<td class="px-6 py-4">
									<div class="flex items-center gap-2">
										<div 
											class="w-6 h-6 rounded border border-gray-300"
											:style="{ backgroundColor: reReference.color }"
										></div>
										<span class="text-sm text-muted-foreground">{{ reReference.color }}</span>
									</div>
								</td>
								<td class="px-6 py-4">{{ reReference.customers_count || 0 }}</td>
								<td class="px-6 py-4">
									<span 
										class="inline-flex items-center px-2 py-1 rounded-full text-xs border"
										:class="reReference.is_active 
											? 'bg-green-100 text-green-800 border-green-200 dark:bg-green-900/20 dark:text-green-400 dark:border-green-800' 
											: 'bg-gray-100 text-gray-800 border-gray-200 dark:bg-gray-900/20 dark:text-gray-400 dark:border-gray-800'"
									>
										{{ reReference.is_active ? 'Active' : 'Inactive' }}
									</span>
								</td>
								<td class="px-6 py-4">
									<div class="flex items-center gap-2">
										<button @click="toggleStatus(reReference)" 
											class="p-2 hover:bg-muted rounded-md transition-colors"
											:class="reReference.is_active ? 'text-orange-600 hover:text-orange-700' : 'text-green-600 hover:text-green-700'"
										>
											<UserXIcon v-if="reReference.is_active" class="h-4 w-4" />
											<UserCheckIcon v-else class="h-4 w-4" />
										</button>
										<button @click="editReReference(reReference)" class="p-2 hover:bg-muted rounded-md transition-colors">
											<PencilIcon class="h-4 w-4" />
										</button>
										<button @click="deleteReReference(reReference)"
											class="p-2 hover:bg-destructive/10 rounded-md text-destructive transition-colors">
											<TrashIcon class="h-4 w-4" />
										</button>
									</div>
								</td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>

			<!-- Mobile/Tablet Card View -->
			<div class="lg:hidden space-y-4">
				<div v-if="!reReferences.data || reReferences.data.length === 0" class="rounded-lg border bg-card p-8">
					<div class="flex flex-col items-center justify-center text-center">
						<ShieldXIcon class="h-12 w-12 text-muted-foreground/50" />
						<h3 class="mt-4 text-sm font-medium text-muted-foreground">No RE references found</h3>
						<p class="mt-1 text-sm text-muted-foreground/80">Get started by creating a new RE reference.</p>
					</div>
				</div>
				
				<div v-for="reReference in reReferences.data" :key="reReference.id" class="rounded-lg border bg-card p-4 space-y-3">
					<div class="flex items-start justify-between">
						<div class="flex-1 min-w-0">
							<h3 class="font-medium text-base truncate">{{ reReference.name }}</h3>
							<p class="text-sm text-muted-foreground">{{ reReference.code }}</p>
						</div>
						<div class="flex items-center gap-1 ml-2">
							<button @click="toggleStatus(reReference)" 
								class="p-2 hover:bg-muted rounded-md transition-colors"
								:class="reReference.is_active ? 'text-orange-600 hover:text-orange-700' : 'text-green-600 hover:text-green-700'"
							>
								<UserXIcon v-if="reReference.is_active" class="h-4 w-4" />
								<UserCheckIcon v-else class="h-4 w-4" />
							</button>
							<button @click="editReReference(reReference)" class="p-2 hover:bg-muted rounded-md transition-colors">
								<PencilIcon class="h-4 w-4" />
							</button>
							<button @click="deleteReReference(reReference)"
								class="p-2 hover:bg-destructive/10 rounded-md text-destructive transition-colors">
								<TrashIcon class="h-4 w-4" />
							</button>
						</div>
					</div>
					
					<div class="flex items-center justify-between text-sm">
						<div class="flex items-center gap-2">
							<div 
								class="w-4 h-4 rounded border border-gray-300"
								:style="{ backgroundColor: reReference.color }"
							></div>
							<span class="text-muted-foreground">{{ reReference.color }}</span>
						</div>
						<div class="flex items-center gap-2">
							<span class="text-muted-foreground">Customers:</span>
							<span>{{ reReference.customers_count || 0 }}</span>
						</div>
					</div>
					
					<div class="flex items-center justify-between">
						<span 
							class="inline-flex items-center px-2 py-1 rounded-full text-xs border"
							:class="reReference.is_active 
								? 'bg-green-100 text-green-800 border-green-200 dark:bg-green-900/20 dark:text-green-400 dark:border-green-800' 
								: 'bg-gray-100 text-gray-800 border-gray-200 dark:bg-gray-900/20 dark:text-gray-400 dark:border-gray-800'"
						>
							{{ reReference.is_active ? 'Active' : 'Inactive' }}
						</span>
					</div>
				</div>
			</div>

			<!-- Pagination -->
			<Pagination :paginationData="reReferences" />

			<!-- Add/Edit Dialog -->
			<Dialog :open="showAddDialog" @update:open="showAddDialog = $event">
				<DialogContent class="sm:max-w-[425px]">
					<DialogHeader>
						<DialogTitle>{{ editingReReference ? 'Edit RE Reference' : 'Add RE Reference' }}</DialogTitle>
					</DialogHeader>
					<form @submit.prevent="handleSubmit" class="space-y-4">
						<div class="space-y-2">
							<Label for="code">Code</Label>
							<Input 
								id="code" 
								v-model="form.code" 
								placeholder="Enter RE reference code" 
								:disabled="form.processing" 
							/>
						</div>
						<div class="space-y-2">
							<Label for="name">Name</Label>
							<Input 
								id="name" 
								v-model="form.name" 
								placeholder="Enter RE reference name" 
								:disabled="form.processing" 
							/>
						</div>
						<div class="space-y-2">
							<Label for="color">Color</Label>
							<ColorPicker 
								v-model="form.color"
								:disabled="form.processing"
							/>
						</div>
						<div class="flex items-center space-x-2">
							<input 
								id="is_active" 
								v-model="form.is_active" 
								type="checkbox" 
								class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
							/>
							<Label for="is_active">Active</Label>
						</div>
						<DialogFooter class="flex-col sm:flex-row gap-2">
							<Button type="button" variant="ghost" @click="closeDialog" :disabled="form.processing" class="w-full sm:w-auto">
								Cancel
							</Button>
							<Button type="submit" :disabled="form.processing" class="w-full sm:w-auto">
								{{ editingReReference ? 'Update' : 'Create' }}
							</Button>
						</DialogFooter>
					</form>
				</DialogContent>
			</Dialog>
		</div>
	</AppLayout>
</template>
