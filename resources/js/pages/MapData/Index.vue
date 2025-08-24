<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import Heading from '@/components/Heading.vue';
import { type BreadcrumbItem, type User } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
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

import { PlusIcon, PencilIcon, TrashIcon, ShieldXIcon, UserXIcon, UserCheckIcon, UploadIcon } from 'lucide-vue-next';
import Dropdown from '@/components/custom/Dropdown.vue';

// Define types for MapData
interface MapData {
	id: string;
	name: string;
	description?: string;
	type: string;
	is_active: boolean;
	user: User;
	data?: string;
	map_data_rows_count?: number;
	created_at: string;
	updated_at: string;
}

const props = defineProps({
	mapData: {
		type: Object,
		required: true,
		default: () => ({})
	},
	users: {
		type: Array as () => User[],
		default: () => []
	},
	types: {
		type: Object,
		default: () => ({})
	},
	filters: {
		type: Object,
		default: () => ({})
	}
});

const breadcrumbs: BreadcrumbItem[] = [
	{
		title: 'Map Data',
		href: route('map-data.index'),
	},
];

const showAddDialog = ref(false);
const showUploadDialog = ref(false);
const editingMapData = ref<MapData | null>(null);
const selectedMapDataForUpload = ref<MapData | null>(null);
const isUploading = ref(false);

// Dialog form selections
const selectedDialogUser = ref<User | null>(null);
const selectedDialogType = ref<{id: string, name: string} | null>(null);

const form = useForm({
	name: '',
	description: '',
	type: 'point',
	user_id: '',
	spatial_data: ''
});

// Filter state
const selectedFilterUser = ref<User | null>(null);
const selectedFilterType = ref<{id: string, name: string} | null>(null);
const selectedFilterStatus = ref<{id: string, name: string} | null>(null);

const filterForm = useForm({
	search: props.filters.search || '',
	user_id: props.filters.user_id || '',
	type: props.filters.type || '',
	is_active: props.filters.is_active !== undefined ? props.filters.is_active : ''
});

// Computed properties
const mapDataItems = computed(() => props.mapData.data || []);
const totalMapData = computed(() => props.mapData.total || 0);

const mapDataTypes = computed(() => {
	return Object.entries(props.types).map(([value, label]) => ({
		id: value,
		name: label as string
	}));
});

// Methods
const openAddDialog = () => {
	resetForm();
	editingMapData.value = null;
	showAddDialog.value = true;
};

const openEditDialog = (mapData: MapData) => {
	resetForm();
	editingMapData.value = mapData;
	
	// Populate form
	form.name = mapData.name;
	form.description = mapData.description || '';
	form.type = mapData.type;
	form.user_id = mapData.user.id.toString();
	form.spatial_data = mapData.data || '';
	
	// Set selected user and type
	selectedDialogUser.value = mapData.user;
	const selectedType = mapDataTypes.value.find((type: {id: string, name: string}) => type.id === mapData.type);
	selectedDialogType.value = selectedType || null;
	
	showAddDialog.value = true;
};

const resetForm = () => {
	form.reset();
	selectedDialogUser.value = null;
	selectedDialogType.value = null;
	form.type = 'point';
	
	// Set default type selection
	const defaultType = mapDataTypes.value.find((type: {id: string, name: string}) => type.id === 'point');
	selectedDialogType.value = defaultType || null;
};

const closeDialog = () => {
	showAddDialog.value = false;
	resetForm();
	editingMapData.value = null;
};

const closeUploadDialog = () => {
	showUploadDialog.value = false;
	selectedMapDataForUpload.value = null;
};

const submitForm = () => {
	if (!form.user_id) {
		alert('Please select a user');
		return;
	}

	// Transform spatial_data back to data for backend compatibility
	const formData = {
		name: form.name,
		description: form.description,
		type: form.type,
		user_id: form.user_id,
		data: form.spatial_data
	};

	if (editingMapData.value) {
		useForm(formData).put(route('map-data.update', editingMapData.value.id), {
			onSuccess: () => closeDialog()
		});
	} else {
		useForm(formData).post(route('map-data.store'), {
			onSuccess: () => closeDialog()
		});
	}
};

const deleteMapData = (mapData: MapData) => {
	if (confirm(`Are you sure you want to delete "${mapData.name}"? This action cannot be undone.`)) {
		useForm({}).delete(route('map-data.destroy', mapData.id));
	}
};

const toggleMapDataStatus = (mapData: MapData) => {
	const action = mapData.is_active ? 'deactivate' : 'activate';
	const actionText = mapData.is_active ? 'deactivate' : 'activate';
	
	if (confirm(`Are you sure you want to ${actionText} "${mapData.name}"?`)) {
		useForm({}).patch(route(`map-data.${action}`, mapData.id));
	}
};

const applyFilters = () => {
	filterForm.get(route('map-data.index'), {
		preserveState: true,
		preserveScroll: true
	});
};

const clearFilters = () => {
	filterForm.reset();
	selectedFilterUser.value = null;
	selectedFilterType.value = null;
	selectedFilterStatus.value = null;
	applyFilters();
};

const handleUserSelect = (user: User | null) => {
	selectedDialogUser.value = user;
	form.user_id = user ? user.id.toString() : '';
};

const handleFilterUserSelect = (user: User | null) => {
	selectedFilterUser.value = user;
	filterForm.user_id = user ? user.id.toString() : '';
	applyFilters();
};

const handleTypeChange = (type: {id: string, name: string} | null) => {
	selectedDialogType.value = type;
	form.type = type ? type.id : 'point';
};

const handleFilterTypeChange = (type: {id: string, name: string} | null) => {
	selectedFilterType.value = type;
	filterForm.type = type ? type.id : '';
	applyFilters();
};

const handleFilterStatusChange = (status: {id: string, name: string} | null) => {
	selectedFilterStatus.value = status;
	filterForm.is_active = status ? status.id : '';
	applyFilters();
};

const openUploadDialog = (mapData: MapData) => {
	selectedMapDataForUpload.value = mapData;
	showUploadDialog.value = true;
};

const handleFileUpload = async (event: Event) => {
	const target = event.target as HTMLInputElement;
	const file = target.files?.[0];
	
	if (!file || !selectedMapDataForUpload.value) return;
	
	// Check file type
	if (!file.name.endsWith('.xlsx') && !file.name.endsWith('.xls')) {
		alert('Please select an Excel file (.xlsx or .xls)');
		return;
	}
	
	// Check file size (10MB max)
	if (file.size > 10 * 1024 * 1024) {
		alert('File size must be less than 10MB');
		return;
	}
	
	isUploading.value = true;
	
	// Create FormData for file upload
	const formData = new FormData();
	formData.append('excel_file', file);
	formData.append('map_data_id', selectedMapDataForUpload.value.id);
	
	try {
		const response = await fetch(route('map-data.upload-rows'), {
			method: 'POST',
			body: formData,
			headers: {
				'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
			}
		});
		
		if (response.ok) {
			const result = await response.json();
			alert(`Successfully uploaded ${result.rows_created} rows from Excel file.`);
			closeUploadDialog();
			// Refresh the page to show updated data
			window.location.reload();
		} else {
			const error = await response.json();
			alert(`Upload failed: ${error.message || 'Unknown error'}`);
		}
	} catch (error) {
		console.error('Upload error:', error);
		alert('Upload failed. Please try again.');
	} finally {
		isUploading.value = false;
	}
	
	// Reset file input
	target.value = '';
};
</script>

<template>
	<Head title="Map Data Management" />
	
	<AppLayout :breadcrumbs="breadcrumbs">
		<div class="px-4 py-6">
			<div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6">
				<Heading 
					title="Map Data Management" 
					description="Manage spatial data layers including points, polygons, and boundaries that overlay on maps for enhanced geographical visualization and analysis"
				/>
				<Button @click="openAddDialog" class="w-full sm:w-auto gap-2">
					<PlusIcon class="w-4 h-4" />
					Add Map Data
				</Button>
			</div>

			<!-- Filters -->
			<div class="bg-card rounded-lg border p-4 mb-4">
				<h3 class="text-sm font-medium mb-3">Filters</h3>
				<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4">
					<div class="space-y-2">
						<Label for="search">Search</Label>
						<Input 
							id="search"
							v-model="filterForm.search" 
							placeholder="Search name or description..."
							@keyup.enter="applyFilters"
						/>
					</div>
					<div class="space-y-2">
						<Label for="user_filter">User</Label>
						<Dropdown
							:options="props.users"
							v-model="selectedFilterUser"
							placeholder="All Users"
							:clearable="true"
							@update:modelValue="handleFilterUserSelect"
						/>
					</div>
					<div class="space-y-2">
						<Label for="type_filter">Type</Label>
						<Dropdown
							:options="mapDataTypes"
							v-model="selectedFilterType"
							placeholder="All Types"
							:clearable="true"
							@update:modelValue="handleFilterTypeChange"
						/>
					</div>
					<div class="space-y-2">
						<Label for="status_filter">Status</Label>
						<Dropdown
							:options="[
								{ id: '', name: 'All' },
								{ id: 'true', name: 'Active' },
								{ id: 'false', name: 'Inactive' }
							]"
							v-model="selectedFilterStatus"
							placeholder="All"
							:clearable="true"
							@update:modelValue="handleFilterStatusChange"
						/>
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
					Showing {{ mapDataItems.length || 0 }} map data records
				</span>
			</div>

			<!-- Desktop Table View -->
			<div class="rounded-lg border bg-card overflow-hidden">
				<div class="relative overflow-x-auto">
					<table class="w-full text-sm text-left">
						<thead class="text-xs uppercase bg-muted">
							<tr>
								<th scope="col" class="px-6 py-3">Name</th>
								<th scope="col" class="px-6 py-3">Type</th>
								<th scope="col" class="px-6 py-3">User</th>
								<th scope="col" class="px-6 py-3">Data Rows</th>
								<th scope="col" class="px-6 py-3">Status</th>
								<th scope="col" class="px-6 py-3">Created</th>
								<th scope="col" class="px-6 py-3">Actions</th>
							</tr>
						</thead>
						<tbody>
							<tr v-if="!mapDataItems || mapDataItems.length === 0" class="border-b">
								<td colspan="6" class="px-6 py-8">
									<div class="flex flex-col items-center justify-center text-center">
										<ShieldXIcon class="h-12 w-12 text-muted-foreground/50" />
										<h3 class="mt-4 text-sm font-medium text-muted-foreground">No map data found</h3>
										<p class="mt-1 text-sm text-muted-foreground/80">Get started by creating new spatial data layers.</p>
									</div>
								</td>
							</tr>
							<tr v-for="mapDataItem in mapDataItems" :key="mapDataItem.id" class="border-b hover:bg-muted/50 transition-colors">
								<td class="px-6 py-4">
									<div class="text-sm font-medium">{{ mapDataItem.name }}</div>
									<div v-if="mapDataItem.description" class="text-sm text-muted-foreground">{{ mapDataItem.description }}</div>
								</td>
								<td class="px-6 py-4">
									<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400">
										{{ props.types[mapDataItem.type] || mapDataItem.type }}
									</span>
								</td>
								<td class="px-6 py-4">
									<div class="text-sm">{{ mapDataItem.user.name }}</div>
								</td>
								<td class="px-6 py-4">
									<span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-gray-100 text-gray-800 border border-gray-200 dark:bg-gray-900/20 dark:text-gray-400 dark:border-gray-800">
										{{ mapDataItem.map_data_rows_count || 0 }} rows
									</span>
								</td>
								<td class="px-6 py-4">
									<span 
										class="inline-flex items-center px-2 py-1 rounded-full text-xs border"
										:class="mapDataItem.is_active 
											? 'bg-green-100 text-green-800 border-green-200 dark:bg-green-900/20 dark:text-gray-400 dark:border-green-800' 
											: 'bg-gray-100 text-gray-800 border-gray-200 dark:bg-gray-900/20 dark:text-gray-400 dark:border-gray-800'"
									>
										{{ mapDataItem.is_active ? 'Active' : 'Inactive' }}
									</span>
								</td>
								<td class="px-6 py-4 text-sm text-muted-foreground">
									{{ new Date(mapDataItem.created_at).toLocaleDateString() }}
								</td>
								<td class="px-6 py-4">
									<div class="flex items-center gap-2">
										<button @click="openEditDialog(mapDataItem)" class="p-2 hover:bg-muted rounded-md transition-colors" title="Edit">
											<PencilIcon class="h-4 w-4" />
										</button>
										<button @click="openUploadDialog(mapDataItem)" class="p-2 hover:bg-blue-100 dark:hover:bg-blue-900/20 rounded-md text-blue-600 dark:text-blue-400 transition-colors" title="Upload Excel Data">
											<UploadIcon class="h-4 w-4" />
										</button>
										<button 
											v-if="mapDataItem.is_active"
											@click="toggleMapDataStatus(mapDataItem)" 
											class="p-2 hover:bg-yellow-100 dark:hover:bg-yellow-900/20 rounded-md text-yellow-600 dark:text-yellow-400 transition-colors"
											title="Deactivate"
										>
											<UserXIcon class="h-4 w-4" />
										</button>
										<button 
											v-else
											@click="toggleMapDataStatus(mapDataItem)" 
											class="p-2 hover:bg-green-100 dark:hover:bg-green-900/20 rounded-md text-green-600 dark:text-green-400 transition-colors"
											title="Activate"
										>
											<UserCheckIcon class="h-4 w-4" />
										</button>
										<button @click="deleteMapData(mapDataItem)"
											class="p-2 hover:bg-destructive/10 rounded-md text-destructive transition-colors" title="Delete">
											<TrashIcon class="h-4 w-4" />
										</button>
									</div>
								</td>
							</tr>
						</tbody>
					</table>
				</div>

				<!-- Pagination -->
				<div v-if="props.mapData.links" class="bg-card px-4 py-3 border-t sm:px-6">
					<div class="flex items-center justify-between">
						<div class="flex-1 flex justify-between sm:hidden">
							<a v-if="props.mapData.prev_page_url" :href="props.mapData.prev_page_url" 
								class="relative inline-flex items-center px-4 py-2 border border-border text-sm font-medium rounded-md hover:bg-muted transition-colors">
								Previous
							</a>
							<a v-if="props.mapData.next_page_url" :href="props.mapData.next_page_url"
								class="ml-3 relative inline-flex items-center px-4 py-2 border border-border text-sm font-medium rounded-md hover:bg-muted transition-colors">
								Next
							</a>
						</div>
						<div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
							<div>
								<p class="text-sm text-muted-foreground">
									Showing {{ props.mapData.from || 0 }} to {{ props.mapData.to || 0 }} of {{ totalMapData }} results
								</p>
							</div>
							<div>
								<nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
									<template v-for="link in props.mapData.links" :key="link.label">
										<a v-if="link.url" :href="link.url" 
											:class="[
												'relative inline-flex items-center px-4 py-2 border text-sm font-medium transition-colors',
												link.active 
													? 'z-10 bg-primary/10 border-primary text-primary' 
													: 'bg-card border-border hover:bg-muted'
											]"
											v-html="link.label">
										</a>
										<span v-else 
											:class="[
												'relative inline-flex items-center px-4 py-2 border text-sm font-medium',
												'bg-card border-border text-muted-foreground cursor-not-allowed'
											]"
											v-html="link.label">
										</span>
									</template>
								</nav>
							</div>
						</div>
					</div>
				</div>
			</div>

			<!-- Add/Edit Dialog -->
		<Dialog :open="showAddDialog" @update:open="showAddDialog = $event">
			<DialogContent class="sm:max-w-[425px] max-h-[90vh] overflow-y-auto">
				<DialogHeader>
					<DialogTitle>{{ editingMapData ? 'Edit Map Data' : 'Add Map Data' }}</DialogTitle>
				</DialogHeader>
				<form @submit.prevent="submitForm" class="space-y-4">
					<div class="space-y-2">
						<Label for="name">Name</Label>
						<Input 
							id="name"
							v-model="form.name" 
							placeholder="Enter map data name"
							:disabled="form.processing"
							:class="{ 'border-red-500': form.errors.name }"
						/>
						<p v-if="form.errors.name" class="text-sm text-red-600 mt-1">{{ form.errors.name }}</p>
					</div>

					<div class="space-y-2">
						<Label for="description">Description</Label>
						<Textarea 
							id="description"
							v-model="form.description"
							placeholder="Enter description for this spatial data layer"
							:disabled="form.processing"
							class="min-h-20"
							:class="{ 'border-red-500': form.errors.description }"
						/>
						<p v-if="form.errors.description" class="text-sm text-red-600 mt-1">{{ form.errors.description }}</p>
					</div>

					<div class="space-y-2">
						<Label for="type">Type</Label>
						<Dropdown
							:options="mapDataTypes"
							v-model="selectedDialogType"
							placeholder="Select spatial data type..."
							:disabled="form.processing"
							@update:modelValue="handleTypeChange"
						/>
						<p v-if="form.errors.type" class="text-sm text-red-600 mt-1">{{ form.errors.type }}</p>
					</div>

					<div class="space-y-2">
						<Label for="user_id">User</Label>
						<Dropdown
							:options="props.users"
							v-model="selectedDialogUser"
							placeholder="Select user..."
							:disabled="form.processing"
							@update:modelValue="handleUserSelect"
						/>
						<p v-if="form.errors.user_id" class="text-sm text-red-600 mt-1">{{ form.errors.user_id }}</p>
					</div>

					<div class="space-y-2">
						<Label for="spatial_data">Spatial Data</Label>
						<Textarea 
							id="spatial_data"
							v-model="form.spatial_data"
							placeholder="Enter spatial data (JSON, GeoJSON, or text format)"
							:disabled="form.processing"
							class="min-h-24 font-mono text-sm"
							:class="{ 'border-red-500': form.errors.spatial_data }"
						/>
						<p v-if="form.errors.spatial_data" class="text-sm text-red-600 mt-1">{{ form.errors.spatial_data }}</p>
					</div>
					<DialogFooter class="flex-col sm:flex-row gap-2">
						<Button type="button" variant="ghost" @click="closeDialog" :disabled="form.processing" class="w-full sm:w-auto">
							Cancel
						</Button>
						<Button type="submit" :disabled="form.processing" class="w-full sm:w-auto">
							{{ form.processing ? 'Saving...' : (editingMapData ? 'Update' : 'Create') }}
						</Button>
					</DialogFooter>
				</form>
			</DialogContent>
		</Dialog>

		<!-- Upload Excel Dialog -->
		<Dialog :open="showUploadDialog" @update:open="showUploadDialog = $event">
			<DialogContent class="sm:max-w-[425px]">
				<DialogHeader>
					<DialogTitle>Upload Excel Data for {{ selectedMapDataForUpload?.name }}</DialogTitle>
				</DialogHeader>
				<div class="space-y-4">
					<div class="space-y-2">
						<Label for="excel_file">Excel File</Label>
						<Input 
							id="excel_file"
							type="file"
							accept=".xlsx,.xls"
							@change="handleFileUpload"
							:disabled="isUploading"
							class="cursor-pointer"
						/>
						<p class="text-sm text-muted-foreground">
							Upload an Excel file (.xlsx or .xls). Each row will be converted to JSON and stored as map data rows.
						</p>
					</div>
					
					<div class="bg-blue-50 dark:bg-blue-900/20 p-3 rounded-lg">
						<h4 class="text-sm font-medium text-blue-800 dark:text-blue-200 mb-2">Instructions:</h4>
						<ul class="text-sm text-blue-700 dark:text-blue-300 space-y-1">
							<li>• First row should contain column headers</li>
							<li>• Each subsequent row will become a JSON object</li>
							<li>• Column headers become JSON keys</li>
							<li>• Row values become JSON values</li>
						</ul>
					</div>
				</div>
				<DialogFooter>
					<Button type="button" variant="ghost" @click="closeUploadDialog" :disabled="isUploading">
						Cancel
					</Button>
					<div v-if="isUploading" class="flex items-center gap-2 text-sm text-muted-foreground">
						<div class="animate-spin rounded-full h-4 w-4 border-b-2 border-primary"></div>
						Uploading...
					</div>
				</DialogFooter>
			</DialogContent>
		</Dialog>
		</div>
	</AppLayout>
</template>
