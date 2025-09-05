<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import Heading from '@/components/Heading.vue';
import { type BreadcrumbItem, type CustomerKd } from '@/types';
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
import { PlusIcon, PencilIcon, TrashIcon, ShieldXIcon, EyeIcon } from 'lucide-vue-next';
import ColorPicker from '@/components/custom/ColorPicker.vue';
import Pagination from '@/components/custom/Pagination.vue';

const props = defineProps({
	customerKds: {
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
		title: 'Customer KDs',
		href: route('customer-kds.index'),
	},
];

const showAddDialog = ref(false);
const editingCustomerKd = ref<CustomerKd | null>(null);

const form = useForm({
	code: '',
	name: '',
	color: '#10B981'
});

const filterForm = useForm({
	search: props.filters.search || '',
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
	filterForm.get(route('customer-kds.index'), {
		preserveState: true,
		preserveScroll: true
	});
};

// Clear filters function
const clearFilters = () => {
	filterForm.reset();
	// Reset to default page size
	filterForm.per_page = '15';
	filterForm.get(route('customer-kds.index'));
};

const closeDialog = () => {
	showAddDialog.value = false;
	editingCustomerKd.value = null;
	form.reset();
	form.clearErrors();
};

const editCustomerKd = (customerKd: CustomerKd) => {
	editingCustomerKd.value = customerKd;
	form.code = customerKd.code;
	form.name = customerKd.name;
	form.color = customerKd.color || '#10B981';
	showAddDialog.value = true;
};

const handleSubmit = async () => {
	try {
		if (editingCustomerKd.value) {
			form.put(route('customer-kds.update', editingCustomerKd.value.code), {
				onSuccess: () => {
					closeDialog();
				},
				onError: (errors: any) => {
					console.error('Validation errors:', errors);
				}
			});
		} else {
			form.post(route('customer-kds.store'), {
				onSuccess: () => {
					closeDialog();
				},
				onError: (errors: any) => {
					console.error('Validation errors:', errors);
				}
			});
		}
	} catch (error) {
		console.error('Error saving customer KD:', error);
	}
};

const deleteCustomerKd = (customerKd: CustomerKd) => {
	if (confirm(`Are you sure you want to delete ${customerKd.name}? This action cannot be undone.`)) {
		router.delete(route('customer-kds.destroy', customerKd.code), {
			onSuccess: () => {
				// Data will be automatically refreshed by Inertia
			},
			onError: (errors: any) => {
				console.error('Error deleting customer KD:', errors);
			}
		});
	}
};
</script>

<template>
	<Head title="Customer KDs" description="Manage customer KDs" />

	<AppLayout :breadcrumbs="breadcrumbs">
		<div class="px-4 py-6">
			<div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6">
				<Heading title="Customer KDs" description="Manage customer KD classifications" />
				<Button @click="showAddDialog = true" class="w-full sm:w-auto">
					Add Customer KD
					<PlusIcon class="h-4 w-4 ml-2" />
				</Button>
			</div>

			<!-- Filters Section -->
			<div class="bg-card rounded-lg border p-4 mb-4">
				<h3 class="text-sm font-medium mb-3">Filters</h3>
				<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
					<!-- Search -->
					<div class="space-y-2">
						<Label for="search">Search</Label>
						<Input 
							id="search"
							v-model="filterForm.search"
							placeholder="Search customer KDs..."
							@input="applyFilters"
						/>
					</div>
					
					<!-- Page Size Filter -->
					<div class="space-y-2">
						<Label for="per_page">Per Page</Label>
						<select 
							id="per_page"
							v-model="filterForm.per_page"
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
					Showing {{ customerKds.data?.length || 0 }} customer KDs
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
								<th scope="col" class="px-6 py-3">Actions</th>
							</tr>
						</thead>
						<tbody>
							<tr v-if="!customerKds.data || customerKds.data.length === 0" class="border-b">
								<td colspan="5" class="px-6 py-8">
									<div class="flex flex-col items-center justify-center text-center">
										<ShieldXIcon class="h-12 w-12 text-muted-foreground/50" />
										<h3 class="mt-4 text-sm font-medium text-muted-foreground">No customer KDs found</h3>
										<p class="mt-1 text-sm text-muted-foreground/80">Get started by creating a new customer KD.</p>
									</div>
								</td>
							</tr>
							<tr v-for="customerKd in customerKds.data" :key="customerKd.code" class="border-b hover:bg-muted/50 transition-colors">
								<td class="px-6 py-4 font-medium">{{ customerKd.code }}</td>
								<td class="px-6 py-4">{{ customerKd.name }}</td>
								<td class="px-6 py-4">
									<div class="flex items-center gap-2">
										<div 
											class="w-4 h-4 rounded border border-gray-300"
											:style="{ backgroundColor: customerKd.color || '#10B981' }"
										></div>
										<span class="text-sm text-muted-foreground">{{ customerKd.color || '#10B981' }}</span>
									</div>
								</td>
								<td class="px-6 py-4">{{ customerKd.customers_count || 0 }}</td>
								<td class="px-6 py-4">
									<div class="flex items-center gap-2">
										<button @click="editCustomerKd(customerKd)" class="p-2 hover:bg-muted rounded-md transition-colors">
											<PencilIcon class="h-4 w-4" />
										</button>
										<button @click="deleteCustomerKd(customerKd)"
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
				<div v-if="!customerKds.data || customerKds.data.length === 0" class="rounded-lg border bg-card p-8">
					<div class="flex flex-col items-center justify-center text-center">
						<ShieldXIcon class="h-12 w-12 text-muted-foreground/50" />
						<h3 class="mt-4 text-sm font-medium text-muted-foreground">No customer KDs found</h3>
						<p class="mt-1 text-sm text-muted-foreground/80">Get started by creating a new customer KD.</p>
					</div>
				</div>
				
				<div v-for="customerKd in customerKds.data" :key="customerKd.code" class="rounded-lg border bg-card p-4 space-y-3">
					<div class="flex items-start justify-between">
						<div class="flex-1 min-w-0">
							<h3 class="font-medium text-base truncate">{{ customerKd.name }}</h3>
							<p class="text-sm text-muted-foreground">{{ customerKd.code }}</p>
						</div>
						<div class="flex items-center gap-1 ml-2">
							<button @click="editCustomerKd(customerKd)" class="p-2 hover:bg-muted rounded-md transition-colors">
								<PencilIcon class="h-4 w-4" />
							</button>
							<button @click="deleteCustomerKd(customerKd)"
								class="p-2 hover:bg-destructive/10 rounded-md text-destructive transition-colors">
								<TrashIcon class="h-4 w-4" />
							</button>
						</div>
					</div>
					
					<div class="flex items-center justify-between text-sm">
						<div class="flex items-center gap-2">
							<div 
								class="w-3 h-3 rounded border border-gray-300"
								:style="{ backgroundColor: customerKd.color || '#10B981' }"
							></div>
							<span class="text-muted-foreground">{{ customerKd.color || '#10B981' }}</span>
						</div>
						<div>
							<span class="text-muted-foreground">Customers:</span>
							<span class="ml-2">{{ customerKd.customers_count || 0 }}</span>
						</div>
					</div>
				</div>
			</div>

			<!-- Pagination -->
			<Pagination :paginationData="customerKds" />

			<!-- Add/Edit Dialog -->
			<Dialog :open="showAddDialog" @update:open="showAddDialog = $event">
				<DialogContent class="sm:max-w-[425px]">
					<DialogHeader>
						<DialogTitle>{{ editingCustomerKd ? 'Edit Customer KD' : 'Add Customer KD' }}</DialogTitle>
					</DialogHeader>
					<form @submit.prevent="handleSubmit" class="space-y-4">
						<div class="space-y-2">
							<Label for="code">Code</Label>
							<Input 
								id="code" 
								v-model="form.code" 
								placeholder="Enter customer KD code" 
								:disabled="form.processing" 
							/>
						</div>
						<div class="space-y-2">
							<Label for="name">Name</Label>
							<Input 
								id="name" 
								v-model="form.name" 
								placeholder="Enter customer KD name" 
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
						<DialogFooter class="flex-col sm:flex-row gap-2">
							<Button type="button" variant="ghost" @click="closeDialog" :disabled="form.processing" class="w-full sm:w-auto">
								Cancel
							</Button>
							<Button type="submit" :disabled="form.processing" class="w-full sm:w-auto">
								{{ editingCustomerKd ? 'Update' : 'Create' }}
							</Button>
						</DialogFooter>
					</form>
				</DialogContent>
			</Dialog>
		</div>
	</AppLayout>
</template>
