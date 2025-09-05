<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import Heading from '@/components/Heading.vue';
import { type BreadcrumbItem, type Customer, type SalesPerson, type Route, type Boundary } from '@/types';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import axios from 'axios';
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
import { Textarea } from '@/components/ui/input';
import { PlusIcon, PencilIcon, TrashIcon, ShieldXIcon, UserXIcon, UserCheckIcon } from 'lucide-vue-next';
import Dropdown from '@/components/custom/Dropdown.vue';
import Pagination from '@/components/custom/Pagination.vue';

const props = defineProps({
	customers: {
		type: Object,
		required: true,
		default: () => { }
	},
	salesPersonnel: {
		type: Array as () => SalesPerson[],
		default: () => []
	},
	routes: {
		type: Array as () => Route[],
		default: () => []
	},
	counties: {
		type: Array as () => Boundary[],
		default: () => []
	},
	constituencies: {
		type: Array as () => Boundary[],
		default: () => []
	},
	wards: {
		type: Array as () => Boundary[],
		default: () => []
	},
	customerKds: {
		type: Array as () => any[],
		default: () => []
	},
	reReferences: {
		type: Array as () => any[],
		default: () => []
	},
	filters: {
		type: Object,
		default: () => ({})
	}
});

const breadcrumbs: BreadcrumbItem[] = [
	{
		title: 'Customers',
		href: route('customers.index'),
	},
];

const showAddDialog = ref(false);
const editingCustomer = ref<Customer | null>(null);

// Dialog form dropdown selections
const selectedDialogSalesPerson = ref<SalesPerson | null>(null);
const selectedDialogRoute = ref<Route | null>(null);
const selectedDialogCounty = ref<Boundary | null>(null);
const selectedDialogConstituency = ref<Boundary | null>(null);
const selectedDialogWard = ref<Boundary | null>(null);

const form = useForm({
	name: '',
	phone: '',
	email: '',
	address: '',
	average_ims: '',
	county_code: '', // This will store the county code
	constituency_code: '', // This will store the constituency code
	ward_code: '', // This will store the ward code
	sales_person_id: '',
	route_id: '',
	customer_kd_code: '',
	re_ref: ''
});

// Reactive arrays for filtered dropdowns
const filteredConstituencies = ref<Boundary[]>([]);
const filteredWards = ref<Boundary[]>([]);

// Filter state - Updated to work with Dropdown objects
const selectedFilterSalesPerson = ref<SalesPerson | null>(null);
const selectedFilterRoute = ref<Route | null>(null);
const selectedFilterCounty = ref<Boundary | null>(null);
const selectedFilterConstituency = ref<Boundary | null>(null);
const selectedFilterWard = ref<Boundary | null>(null);

const filterForm = useForm({
	search: props.filters.search || '',
	sales_person_id: props.filters.sales_person_id || '',
	route_id: props.filters.route_id || '',
	county_id: props.filters.county_id || '',
	constituency_id: props.filters.constituency_id || '',
	ward_id: props.filters.ward_id || '',
	per_page: props.filters.per_page || '15'
});

// Filter boundaries for dropdowns
const filterConstituencies = ref<Boundary[]>([]);
const filterWards = ref<Boundary[]>([]);

// Page size options
const pageSizeOptions = [
	{ value: '10', label: '10 per page' },
	{ value: '15', label: '15 per page' },
	{ value: '25', label: '25 per page' },
	{ value: '50', label: '50 per page' },
	{ value: '100', label: '100 per page' }
];

// Watch for changes in filter county selection
watch(() => selectedFilterCounty.value, async (newCounty: Boundary | null) => {
	if (newCounty) {
		filterForm.county_id = newCounty.id.toString();
		try {
			const response = await axios.get(`/customers/boundaries/constituencies/${newCounty.code}`);
			filterConstituencies.value = response.data;
		} catch (error) {
			console.error('Error fetching filter constituencies:', error);
			filterConstituencies.value = [];
		}
	} else {
		filterForm.county_id = '';
		filterConstituencies.value = [];
	}
	// Reset dependent fields
	selectedFilterConstituency.value = null;
	selectedFilterWard.value = null;
	filterForm.constituency_id = '';
	filterForm.ward_id = '';
	filterWards.value = [];
	applyFilters();
});

// Watch for changes in filter constituency selection
watch(() => selectedFilterConstituency.value, async (newConstituency: Boundary | null) => {
	if (newConstituency) {
		filterForm.constituency_id = newConstituency.id.toString();
		try {
			const response = await axios.get(`/customers/boundaries/wards/${newConstituency.code}`);
			filterWards.value = response.data;
		} catch (error) {
			console.error('Error fetching filter wards:', error);
			filterWards.value = [];
		}
	} else {
		filterForm.constituency_id = '';
		filterWards.value = [];
	}
	// Reset dependent field
	selectedFilterWard.value = null;
	filterForm.ward_id = '';
	applyFilters();
});

// Watch for changes in filter ward selection
watch(() => selectedFilterWard.value, (newWard: Boundary | null) => {
	if (newWard) {
		filterForm.ward_id = newWard.id.toString();
	} else {
		filterForm.ward_id = '';
	}
	applyFilters();
});

// Watch for changes in filter sales person selection
watch(() => selectedFilterSalesPerson.value, (newSalesPerson: SalesPerson | null) => {
	if (newSalesPerson) {
		filterForm.sales_person_id = newSalesPerson.id.toString();
	} else {
		filterForm.sales_person_id = '';
	}
	applyFilters();
});

// Watch for changes in filter route selection
watch(() => selectedFilterRoute.value, (newRoute: Route | null) => {
	if (newRoute) {
		filterForm.route_id = newRoute.id.toString();
	} else {
		filterForm.route_id = '';
	}
	applyFilters();
});

// Watch for changes in page size
watch(() => filterForm.per_page, (newValue, oldValue) => {
	if (newValue !== oldValue) {
		applyFilters();
	}
});

// Apply filters function
const applyFilters = () => {
	filterForm.get(route('customers.index'), {
		preserveState: true,
		preserveScroll: true
	});
};

// Clear filters function
const clearFilters = () => {
	// Clear dropdown selections first
	selectedFilterSalesPerson.value = null;
	selectedFilterRoute.value = null;
	selectedFilterCounty.value = null;
	selectedFilterConstituency.value = null;
	selectedFilterWard.value = null;
	// Clear dependent arrays
	filterConstituencies.value = [];
	filterWards.value = [];
	// Reset form with default values
	filterForm.reset();
	filterForm.per_page = '15';
	filterForm.get(route('customers.index'));
};

// Initialize filter constituencies and wards if filters are set
const initializeFilters = async () => {
	// Initialize sales person filter
	if (filterForm.sales_person_id) {
		const salesPerson = props.salesPersonnel.find(sp => sp.id.toString() === filterForm.sales_person_id);
		if (salesPerson) {
			selectedFilterSalesPerson.value = salesPerson;
		}
	}
	
	// Initialize route filter
	if (filterForm.route_id) {
		const route = props.routes.find(r => r.id.toString() === filterForm.route_id);
		if (route) {
			selectedFilterRoute.value = route;
		}
	}
	
	// Initialize boundary filters
	if (filterForm.county_id) {
		const county = props.counties.find(c => c.id === parseInt(filterForm.county_id));
		if (county) {
			selectedFilterCounty.value = county;
			try {
				const response = await axios.get(`/customers/boundaries/constituencies/${county.code}`);
				filterConstituencies.value = response.data;
				
				if (filterForm.constituency_id) {
					const constituency = filterConstituencies.value.find(c => c.id === parseInt(filterForm.constituency_id));
					if (constituency) {
						selectedFilterConstituency.value = constituency;
						const wardsResponse = await axios.get(`/customers/boundaries/wards/${constituency.code}`);
						filterWards.value = wardsResponse.data;
						
						if (filterForm.ward_id) {
							const ward = filterWards.value.find(w => w.id === parseInt(filterForm.ward_id));
							if (ward) {
								selectedFilterWard.value = ward;
							}
						}
					}
				}
			} catch (error) {
				console.error('Error initializing filters:', error);
			}
		}
	}
};

// Initialize filters on component mount
initializeFilters();

// Watch for changes in dialog county selection
watch(() => selectedDialogCounty.value, async (newCounty: Boundary | null) => {
	if (newCounty) {
		form.county_code = newCounty.code.toString();
		try {
			const response = await axios.get(`/customers/boundaries/constituencies/${newCounty.code}`);
			filteredConstituencies.value = response.data;
		} catch (error) {
			console.error('Error fetching constituencies:', error);
			filteredConstituencies.value = [];
		}
	} else {
		form.county_code = '';
		filteredConstituencies.value = [];
	}
	// Reset dependent fields
	selectedDialogConstituency.value = null;
	selectedDialogWard.value = null;
	form.constituency_code = '';
	form.ward_code = '';
	filteredWards.value = [];
});

// Watch for changes in dialog constituency selection
watch(() => selectedDialogConstituency.value, async (newConstituency: Boundary | null) => {
	if (newConstituency) {
		form.constituency_code = newConstituency.code.toString();
		try {
			const response = await axios.get(`/customers/boundaries/wards/${newConstituency.code}`);
			filteredWards.value = response.data;
		} catch (error) {
			console.error('Error fetching wards:', error);
			filteredWards.value = [];
		}
	} else {
		form.constituency_code = '';
		filteredWards.value = [];
	}
	// Reset dependent field
	selectedDialogWard.value = null;
	form.ward_code = '';
});

// Watch for changes in dialog ward selection
watch(() => selectedDialogWard.value, (newWard: Boundary | null) => {
	if (newWard) {
		form.ward_code = newWard.code.toString();
	} else {
		form.ward_code = '';
	}
});

// Watch for changes in dialog sales person selection
watch(() => selectedDialogSalesPerson.value, (newSalesPerson: SalesPerson | null) => {
	if (newSalesPerson) {
		form.sales_person_id = newSalesPerson.id.toString();
	} else {
		form.sales_person_id = '';
	}
});

// Watch for changes in dialog route selection
watch(() => selectedDialogRoute.value, (newRoute: Route | null) => {
	if (newRoute) {
		form.route_id = newRoute.id.toString();
	} else {
		form.route_id = '';
	}
});

const closeDialog = () => {
	showAddDialog.value = false;
	editingCustomer.value = null;
	form.reset();
	form.clearErrors();
	// Reset filtered arrays
	filteredConstituencies.value = [];
	filteredWards.value = [];
	// Reset dropdown selections
	selectedDialogSalesPerson.value = null;
	selectedDialogRoute.value = null;
	selectedDialogCounty.value = null;
	selectedDialogConstituency.value = null;
	selectedDialogWard.value = null;
};

const editCustomer = async (customer: Customer) => {
	editingCustomer.value = customer;
	form.name = customer.name;
	form.phone = customer.phone || '';
	form.email = customer.email || '';
	form.address = customer.address || '';
	form.average_ims = customer.average_ims || '';
	form.sales_person_id = customer.sales_person_id || '';
	form.route_id = customer.route_id || '';
	form.customer_kd_code = customer.customer_kd_code || '';
	form.re_ref = customer.re_ref || '';
	
	// Set dropdown selections for sales person and route
	if (customer.sales_person_id) {
		const salesPerson = props.salesPersonnel.find(sp => sp.id.toString() === customer.sales_person_id!.toString());
		if (salesPerson) {
			// Map the sales person to match the format used in the dropdown
			selectedDialogSalesPerson.value = { 
				...salesPerson, 
				name: `${salesPerson.name} (${salesPerson.code})` 
			};
		}
	}
	
	if (customer.route_id) {
		const route = props.routes.find(r => r.id.toString() === customer.route_id!.toString());
		if (route) {
			selectedDialogRoute.value = route;
		}
	}
	
	// Handle boundaries - if they exist, use the boundary relationship data
	if (customer.county) {
		form.county_code = customer.county.code.toString();
		selectedDialogCounty.value = customer.county;
		
		// Load constituencies for this county
		try {
			const response = await axios.get(`/customers/boundaries/constituencies/${customer.county.code}`);
			filteredConstituencies.value = response.data;
			
			// If constituency exists, set it
			if (customer.constituency) {
				form.constituency_code = customer.constituency.code.toString();
				selectedDialogConstituency.value = customer.constituency;
				
				// Load wards for this constituency
				try {
					const wardsResponse = await axios.get(`/customers/boundaries/wards/${customer.constituency.code}`);
					filteredWards.value = wardsResponse.data;
					
					// If ward exists, set it
					if (customer.ward) {
						form.ward_code = customer.ward.code.toString();
						selectedDialogWard.value = customer.ward;
					}
				} catch (error) {
					console.error('Error loading wards for edit:', error);
				}
			}
		} catch (error) {
			console.error('Error loading constituencies for edit:', error);
		}
	}
	
	showAddDialog.value = true;
};

const handleSubmit = async () => {
	try {
		if (editingCustomer.value) {
			form.put(route('customers.update', editingCustomer.value.id), {
				onSuccess: () => {
					closeDialog();
				},
				onError: (errors: any) => {
					console.error('Validation errors:', errors);
				}
			});
		} else {
			form.post(route('customers.store'), {
				onSuccess: () => {
					closeDialog();
				},
				onError: (errors: any) => {
					console.error('Validation errors:', errors);
				}
			});
		}
	} catch (error) {
		console.error('Error saving customer:', error);
	}
};

const deactivateCustomer = (customer: Customer) => {
	if (confirm(`Are you sure you want to deactivate ${customer.name}?`)) {
		router.patch(route('customers.deactivate', customer.id), {}, {
			onSuccess: () => {
				// Data will be automatically refreshed by Inertia
			},
			onError: (error) => {
				console.error('Error deactivating customer:', error);
			}
		});
	}
};

const activateCustomer = (customer: Customer) => {
	router.patch(route('customers.activate', customer.id), {}, {
		onSuccess: () => {
			// Data will be automatically refreshed by Inertia
		},
		onError: (error) => {
			console.error('Error activating customer:', error);
		}
	});
};

const deleteCustomer = (customer: Customer) => {
	if (confirm(`Are you sure you want to permanently delete ${customer.name}? This action cannot be undone.`)) {
		router.delete(route('customers.destroy', customer.id), {
			onSuccess: () => {
				// Data will be automatically refreshed by Inertia
			},
			onError: (error) => {
				console.error('Error deleting customer:', error);
			}
		});
	}
};
</script>

<template>
	<Head title="Customers" description="Manage customers" />

	<AppLayout :breadcrumbs="breadcrumbs">
		<div class="px-4 py-6">
			<div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6">
				<Heading title="Customers" description="Manage customers" />
				<Button @click="showAddDialog = true" class="w-full sm:w-auto">
					Add Customer
					<PlusIcon class="h-4 w-4 ml-2" />
				</Button>
			</div>

			<!-- Filters Section -->
			<div class="bg-card rounded-lg border p-4 mb-4">
				<h3 class="text-sm font-medium mb-3">Filters</h3>
				<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-7 gap-4">
					<!-- Search -->
					<div class="space-y-2">
						<Label for="search">Search</Label>
						<Input 
							id="search"
							v-model="filterForm.search"
							placeholder="Search customers..."
							@input="applyFilters"
						/>
					</div>
					
					<!-- Sales Person Filter -->
					<div class="space-y-2">
						<Label for="sales_person_filter">Sales Person</Label>
						<Dropdown 
							:options="salesPersonnel" 
							v-model="selectedFilterSalesPerson" 
							placeholder="All Sales Personnel" 
							:clearable="true"
						/>
					</div>
					
					<!-- Route Filter -->
					<div class="space-y-2">
						<Label for="route_filter">Route</Label>
						<Dropdown 
							:options="routes" 
							v-model="selectedFilterRoute" 
							placeholder="All Routes" 
							:clearable="true"
						/>
					</div>
					
					<!-- County Filter -->
					<div class="space-y-2">
						<Label for="county_filter">County</Label>
						<Dropdown 
							:options="counties" 
							v-model="selectedFilterCounty" 
							placeholder="All Counties" 
							:clearable="true"
						/>
					</div>
					
					<!-- Constituency Filter -->
					<div class="space-y-2">
						<Label for="constituency_filter">Constituency</Label>
						<Dropdown 
							:options="filterConstituencies" 
							v-model="selectedFilterConstituency" 
							placeholder="All Constituencies" 
							:disabled="!selectedFilterCounty"
							:clearable="true"
						/>
					</div>
					
					<!-- Ward Filter -->
					<div class="space-y-2">
						<Label for="ward_filter">Ward</Label>
						<Dropdown 
							:options="filterWards" 
							v-model="selectedFilterWard" 
							placeholder="All Wards" 
							:disabled="!selectedFilterConstituency"
							:clearable="true"
						/>
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
					Showing {{ customers.data?.length || 0 }} customers
				</span>
			</div>

			<!-- Desktop Table View -->
			<div class="hidden lg:block rounded-lg border bg-card">
				<div class="relative overflow-x-auto">
					<table class="w-full text-sm text-left">
						<thead class="text-xs uppercase bg-muted">
							<tr>
								<th scope="col" class="px-6 py-3">Name</th>
								<th scope="col" class="px-6 py-3">Phone</th>
								<th scope="col" class="px-6 py-3">Email</th>
								<th scope="col" class="px-6 py-3">Average IMS</th>
								<th scope="col" class="px-6 py-3">Sales Personnel</th>
								<th scope="col" class="px-6 py-3">Route</th>
								<th scope="col" class="px-6 py-3">County</th>
								<th scope="col" class="px-6 py-3">Constituency</th>
								<th scope="col" class="px-6 py-3">Ward</th>
								<th scope="col" class="px-6 py-3">Customer KD</th>
								<th scope="col" class="px-6 py-3">RE Reference</th>
								<th scope="col" class="px-6 py-3">Status</th>
								<th scope="col" class="px-6 py-3">Actions</th>
							</tr>
						</thead>
						<tbody>
							<tr v-if="!customers.data || customers.data.length === 0" class="border-b">
								<td colspan="13" class="px-6 py-8">
									<div class="flex flex-col items-center justify-center text-center">
										<ShieldXIcon class="h-12 w-12 text-muted-foreground/50" />
										<h3 class="mt-4 text-sm font-medium text-muted-foreground">No customers found</h3>
										<p class="mt-1 text-sm text-muted-foreground/80">Get started by creating a new customer.</p>
									</div>
								</td>
							</tr>
							<tr v-for="customer in customers.data" :key="customer.id" class="border-b hover:bg-muted/50 transition-colors">
								<td class="px-6 py-4 font-medium">{{ customer.name }}</td>
								<td class="px-6 py-4">{{ customer.phone || '-' }}</td>
								<td class="px-6 py-4">{{ customer.email || '-' }}</td>
								<td class="px-6 py-4">{{ customer.average_ims ? customer.average_ims.toFixed(6) : '-' }}</td>
								<td class="px-6 py-4">{{ customer.sales_person?.name || '-' }}</td>
								<td class="px-6 py-4">{{ customer.route?.name || '-' }}</td>
								<td class="px-6 py-4">{{ customer.county?.name || '-' }}</td>
								<td class="px-6 py-4">{{ customer.constituency?.name || '-' }}</td>
								<td class="px-6 py-4">{{ customer.ward?.name || '-' }}</td>
								<td class="px-6 py-4">
									<div v-if="customer.customer_kd" class="flex items-center gap-2">
										<div 
											class="w-4 h-4 rounded border border-gray-300"
											:style="{ backgroundColor: customer.customer_kd.color }"
										></div>
										<span>{{ customer.customer_kd.name }}</span>
									</div>
									<span v-else>-</span>
								</td>
								<td class="px-6 py-4">
									<div v-if="customer.re_reference" class="flex items-center gap-2">
										<div 
											class="w-4 h-4 rounded border border-gray-300"
											:style="{ backgroundColor: customer.re_reference.color }"
										></div>
										<span>{{ customer.re_reference.name }}</span>
									</div>
									<span v-else>-</span>
								</td>
								<td class="px-6 py-4">
									<span 
										class="inline-flex items-center px-2 py-1 rounded-full text-xs border"
										:class="customer.is_active 
											? 'bg-green-100 text-green-800 border-green-200 dark:bg-green-900/20 dark:text-green-400 dark:border-green-800' 
											: 'bg-gray-100 text-gray-800 border-gray-200 dark:bg-gray-900/20 dark:text-gray-400 dark:border-gray-800'"
									>
										{{ customer.is_active ? 'Active' : 'Inactive' }}
									</span>
								</td>
								<td class="px-6 py-4">
									<div class="flex items-center gap-2">
										<button @click="editCustomer(customer)" class="p-2 hover:bg-muted rounded-md transition-colors">
											<PencilIcon class="h-4 w-4" />
										</button>
										<button 
											v-if="customer.is_active"
											@click="deactivateCustomer(customer)"
											class="p-2 hover:bg-yellow-100 dark:hover:bg-yellow-900/20 rounded-md text-yellow-600 dark:text-yellow-400 transition-colors"
											title="Deactivate"
										>
											<UserXIcon class="h-4 w-4" />
										</button>
										<button 
											v-else
											@click="activateCustomer(customer)"
											class="p-2 hover:bg-green-100 dark:hover:bg-green-900/20 rounded-md text-green-600 dark:text-green-400 transition-colors"
											title="Activate"
										>
											<UserCheckIcon class="h-4 w-4" />
										</button>
										<button @click="deleteCustomer(customer)"
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
				<div v-if="!customers.data || customers.data.length === 0" class="rounded-lg border bg-card p-8">
					<div class="flex flex-col items-center justify-center text-center">
						<ShieldXIcon class="h-12 w-12 text-muted-foreground/50" />
						<h3 class="mt-4 text-sm font-medium text-muted-foreground">No customers found</h3>
						<p class="mt-1 text-sm text-muted-foreground/80">Get started by creating a new customer.</p>
					</div>
				</div>
				
				<div v-for="customer in customers.data" :key="customer.id" class="rounded-lg border bg-card p-4 space-y-3">
					<div class="flex items-start justify-between">
						<div class="flex-1 min-w-0">
							<h3 class="font-medium text-base truncate">{{ customer.name }}</h3>
							<div class="flex items-center gap-2 mt-1">
								<span 
									class="inline-flex items-center px-2 py-1 rounded-full text-xs border"
									:class="customer.is_active 
										? 'bg-green-100 text-green-800 border-green-200 dark:bg-green-900/20 dark:text-green-400 dark:border-green-800' 
										: 'bg-gray-100 text-gray-800 border-gray-200 dark:bg-gray-900/20 dark:text-gray-400 dark:border-gray-800'"
								>
									{{ customer.is_active ? 'Active' : 'Inactive' }}
								</span>
							</div>
						</div>
						<div class="flex items-center gap-1 ml-2">
							<button @click="editCustomer(customer)" class="p-2 hover:bg-muted rounded-md transition-colors">
								<PencilIcon class="h-4 w-4" />
							</button>
							<button 
								v-if="customer.is_active"
								@click="deactivateCustomer(customer)"
								class="p-2 hover:bg-yellow-100 dark:hover:bg-yellow-900/20 rounded-md text-yellow-600 dark:text-yellow-400 transition-colors"
								title="Deactivate"
							>
								<UserXIcon class="h-4 w-4" />
							</button>
							<button 
								v-else
								@click="activateCustomer(customer)"
								class="p-2 hover:bg-green-100 dark:hover:bg-green-900/20 rounded-md text-green-600 dark:text-green-400 transition-colors"
								title="Activate"
							>
								<UserCheckIcon class="h-4 w-4" />
							</button>
							<button @click="deleteCustomer(customer)"
								class="p-2 hover:bg-destructive/10 rounded-md text-destructive transition-colors">
								<TrashIcon class="h-4 w-4" />
							</button>
						</div>
					</div>
					
					<div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
						<div v-if="customer.phone">
							<span class="text-muted-foreground">Phone:</span>
							<span class="ml-2">{{ customer.phone }}</span>
						</div>
						<div v-if="customer.email">
							<span class="text-muted-foreground">Email:</span>
							<span class="ml-2 truncate">{{ customer.email }}</span>
						</div>
						<div v-if="customer.average_ims">
							<span class="text-muted-foreground">Average IMS:</span>
							<span class="ml-2">{{ customer.average_ims.toFixed(6) }}</span>
						</div>
						<div v-if="customer.sales_person">
							<span class="text-muted-foreground">Sales Person:</span>
							<span class="ml-2">{{ customer.sales_person.name }}</span>
						</div>
						<div v-if="customer.route">
							<span class="text-muted-foreground">Route:</span>
							<span class="ml-2">{{ customer.route.name }}</span>
						</div>
						<div v-if="customer.county">
							<span class="text-muted-foreground">County:</span>
							<span class="ml-2">{{ customer.county.name }}</span>
						</div>
						<div v-if="customer.constituency">
							<span class="text-muted-foreground">Constituency:</span>
							<span class="ml-2">{{ customer.constituency.name }}</span>
						</div>
						<div v-if="customer.ward">
							<span class="text-muted-foreground">Ward:</span>
							<span class="ml-2">{{ customer.ward.name }}</span>
						</div>
						<div v-if="customer.customer_kd">
							<span class="text-muted-foreground">Customer KD:</span>
							<div class="ml-2 flex items-center gap-2">
								<div 
									class="w-3 h-3 rounded border border-gray-300"
									:style="{ backgroundColor: customer.customer_kd.color }"
								></div>
								<span>{{ customer.customer_kd.name }}</span>
							</div>
						</div>
						<div v-if="customer.re_reference" class="sm:col-span-2">
							<span class="text-muted-foreground">RE Reference:</span>
							<div class="ml-2 flex items-center gap-2">
								<div 
									class="w-3 h-3 rounded border border-gray-300"
									:style="{ backgroundColor: customer.re_reference.color }"
								></div>
								<span>{{ customer.re_reference.name }}</span>
							</div>
						</div>
					</div>
				</div>
			</div>

			<!-- Pagination -->
			<Pagination :paginationData="customers" />

			<!-- Add/Edit Dialog -->
			<Dialog :open="showAddDialog" @update:open="showAddDialog = $event">
				<DialogContent class="sm:max-w-[425px] max-h-[90vh] overflow-y-auto">
					<DialogHeader>
						<DialogTitle>{{ editingCustomer ? 'Edit Customer' : 'Add Customer' }}</DialogTitle>
					</DialogHeader>
					<form @submit.prevent="handleSubmit" class="space-y-4">
						<div class="space-y-2">
							<Label for="name">Name</Label>
							<Input 
								id="name" 
								v-model="form.name" 
								placeholder="Enter customer name" 
								:disabled="form.processing" 
							/>
						</div>
						<div class="space-y-2">
							<Label for="phone">Phone</Label>
							<Input 
								id="phone" 
								v-model="form.phone" 
								placeholder="Enter phone number" 
								:disabled="form.processing" 
							/>
						</div>
						<div class="space-y-2">
							<Label for="email">Email</Label>
							<Input 
								id="email" 
								type="email" 
								v-model="form.email" 
								placeholder="Enter email address" 
								:disabled="form.processing" 
							/>
						</div>
						<div class="space-y-2">
							<Label for="address">Address</Label>
							<Textarea 
								id="address" 
								v-model="form.address" 
								placeholder="Enter customer address" 
								:disabled="form.processing" 
								class="min-h-20"
							/>
						</div>
						<div class="space-y-2">
							<Label for="average_ims">Average IMS</Label>
							<Input 
								id="average_ims" 
								type="number" 
								step="0.000001"
								v-model="form.average_ims" 
								placeholder="Enter average IMS value" 
								:disabled="form.processing" 
							/>
						</div>
						<div class="space-y-2">
							<Label for="county">County</Label>
							<Dropdown 
								:options="counties" 
								v-model="selectedDialogCounty" 
								placeholder="Select county" 
								:disabled="form.processing"
								:clearable="true"
							/>
						</div>
						<div class="space-y-2">
							<Label for="constituency">Constituency</Label>
							<Dropdown 
								:options="filteredConstituencies" 
								v-model="selectedDialogConstituency" 
								placeholder="Select constituency" 
								:disabled="form.processing || !selectedDialogCounty"
								:clearable="true"
							/>
						</div>
						<div class="space-y-2">
							<Label for="ward">Ward</Label>
							<Dropdown 
								:options="filteredWards" 
								v-model="selectedDialogWard" 
								placeholder="Select ward" 
								:disabled="form.processing || !selectedDialogConstituency"
								:clearable="true"
							/>
						</div>
						<div class="space-y-2">
							<Label for="sales_person_id">Sales Personnel</Label>
							<Dropdown 
								:options="salesPersonnel.map(person => ({ ...person, name: `${person.name} (${person.code})` }))" 
								v-model="selectedDialogSalesPerson" 
								placeholder="Select sales personnel" 
								:disabled="form.processing"
								:clearable="true"
							/>
						</div>
						<div class="space-y-2">
							<Label for="route_id">Route</Label>
							<Dropdown 
								:options="routes" 
								v-model="selectedDialogRoute" 
								placeholder="Select route" 
								:disabled="form.processing"
								:clearable="true"
							/>
						</div>
						<div class="space-y-2">
							<Label for="customer_kd_code">Customer KD</Label>
							<Dropdown 
								:options="customerKds" 
								v-model="form.customer_kd_code" 
								placeholder="Select customer KD" 
								:disabled="form.processing"
								:clearable="true"
							/>
						</div>
						<div class="space-y-2">
							<Label for="re_ref">RE Reference</Label>
							<Dropdown 
								:options="reReferences" 
								v-model="form.re_ref" 
								placeholder="Select RE reference" 
								:disabled="form.processing"
								:clearable="true"
							/>
						</div>
						<DialogFooter class="flex-col sm:flex-row gap-2">
							<Button type="button" variant="ghost" @click="closeDialog" :disabled="form.processing" class="w-full sm:w-auto">
								Cancel
							</Button>
							<Button type="submit" :disabled="form.processing" class="w-full sm:w-auto">
								{{ editingCustomer ? 'Update' : 'Create' }}
							</Button>
						</DialogFooter>
					</form>
				</DialogContent>
			</Dialog>
		</div>
	</AppLayout>
</template>
