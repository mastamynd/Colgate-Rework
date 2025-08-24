<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import Heading from '@/components/Heading.vue';
import { type BreadcrumbItem, type Route } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
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
import axios from 'axios';

const props = defineProps({
	routes: {
		type: Object,
		required: true,
		default: () => { }
	}
});

const breadcrumbs: BreadcrumbItem[] = [
	{
		title: 'Routes',
		href: route('routes.index'),
	},
];

const showAddDialog = ref(false);
const editingRoute = ref<Route | null>(null);

const form = useForm({
	name: '',
	description: ''
});

const closeDialog = () => {
	showAddDialog.value = false;
	editingRoute.value = null;
	form.reset();
	form.clearErrors();
};

const editRoute = (route: Route) => {
	editingRoute.value = route;
	form.name = route.name;
	form.description = route.description || '';
	showAddDialog.value = true;
};

const handleSubmit = async () => {
	try {
		if (editingRoute.value) {
			form.put(route('routes.update', editingRoute.value.id), {
				onSuccess: () => {
					closeDialog();
				},
				onError: (errors: any) => {
					console.error('Validation errors:', errors);
				}
			});
		} else {
			form.post(route('routes.store'), {
				onSuccess: () => {
					closeDialog();
				},
				onError: (errors: any) => {
					console.error('Validation errors:', errors);
				}
			});
		}
	} catch (error) {
		console.error('Error saving route:', error);
	}
};

const deactivateRoute = async (route: Route) => {
	if (confirm(`Are you sure you want to deactivate ${route.name}?`)) {
		try {
			await axios.patch(route('routes.deactivate', route.id));
			window.location.reload();
		} catch (error) {
			console.error('Error deactivating route:', error);
		}
	}
};

const activateRoute = async (route: Route) => {
	try {
		await axios.patch(route('routes.activate', route.id));
		window.location.reload();
	} catch (error) {
		console.error('Error activating route:', error);
	}
};

const deleteRoute = async (route: Route) => {
	if (confirm(`Are you sure you want to permanently delete ${route.name}? This action cannot be undone.`)) {
		try {
			await axios.delete(route('routes.destroy', route.id));
			window.location.reload();
		} catch (error) {
			console.error('Error deleting route:', error);
		}
	}
};
</script>

<template>
	<Head title="Routes" description="Manage routes" />

	<AppLayout :breadcrumbs="breadcrumbs">
		<div class="px-4 py-6">
			<div class="flex items-center justify-between">
				<Heading title="Routes" description="Manage routes" />
				<Button @click="showAddDialog = true">
					Add Route
					<PlusIcon class="h-4 w-4 ml-2" />
				</Button>
			</div>

			<div class="flex justify-between my-2 items-center flex-col sm:flex-row gap-1">
				<span class="py-2 text-sm text-muted-foreground">
					Showing {{ routes.data?.length || 0 }} routes
				</span>
				<Input type="search" placeholder="Search..." class="w-full sm:w-64" />
			</div>

			<div class="rounded-lg border bg-card">
				<div class="relative overflow-x-auto">
					<table class="w-full text-sm text-left">
						<thead class="text-xs uppercase bg-muted">
							<tr>
								<th scope="col" class="px-6 py-3">Name</th>
								<th scope="col" class="px-6 py-3">Description</th>
								<th scope="col" class="px-6 py-3">Status</th>
								<th scope="col" class="px-6 py-3">Actions</th>
							</tr>
						</thead>
						<tbody>
							<tr v-if="!routes.data || routes.data.length === 0" class="border-b">
								<td colspan="4" class="px-6 py-8">
									<div class="flex flex-col items-center justify-center text-center">
										<ShieldXIcon class="h-12 w-12 text-muted-foreground/50" />
										<h3 class="mt-4 text-sm font-medium text-muted-foreground">No routes found</h3>
										<p class="mt-1 text-sm text-muted-foreground/80">Get started by creating a new route.</p>
									</div>
								</td>
							</tr>
							<tr v-for="routeItem in routes.data" :key="routeItem.id" class="border-b hover:bg-muted/50 transition-colors">
								<td class="px-6 py-4 font-medium">{{ routeItem.name }}</td>
								<td class="px-6 py-4">{{ routeItem.description || '-' }}</td>
								<td class="px-6 py-4">
									<span 
										class="inline-flex items-center px-2 py-1 rounded-full text-xs border"
										:class="routeItem.is_active 
											? 'bg-green-100 text-green-800 border-green-200 dark:bg-green-900/20 dark:text-green-400 dark:border-green-800' 
											: 'bg-gray-100 text-gray-800 border-gray-200 dark:bg-gray-900/20 dark:text-gray-400 dark:border-gray-800'"
									>
										{{ routeItem.is_active ? 'Active' : 'Inactive' }}
									</span>
								</td>
								<td class="px-6 py-4">
									<div class="flex items-center gap-2">
										<button @click="editRoute(routeItem)" class="p-2 hover:bg-muted rounded-md transition-colors">
											<PencilIcon class="h-4 w-4" />
										</button>
										<button 
											v-if="routeItem.is_active"
											@click="deactivateRoute(routeItem)"
											class="p-2 hover:bg-yellow-100 dark:hover:bg-yellow-900/20 rounded-md text-yellow-600 dark:text-yellow-400 transition-colors"
											title="Deactivate"
										>
											<UserXIcon class="h-4 w-4" />
										</button>
										<button 
											v-else
											@click="activateRoute(routeItem)"
											class="p-2 hover:bg-green-100 dark:hover:bg-green-900/20 rounded-md text-green-600 dark:text-green-400 transition-colors"
											title="Activate"
										>
											<UserCheckIcon class="h-4 w-4" />
										</button>
										<button @click="deleteRoute(routeItem)"
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

			<!-- Add/Edit Dialog -->
			<Dialog :open="showAddDialog" @update:open="showAddDialog = $event">
				<DialogContent class="sm:max-w-[425px]">
					<DialogHeader>
						<DialogTitle>{{ editingRoute ? 'Edit Route' : 'Add Route' }}</DialogTitle>
					</DialogHeader>
					<form @submit.prevent="handleSubmit" class="space-y-4">
						<div class="space-y-2">
							<Label for="name">Name</Label>
							<Input 
								id="name" 
								v-model="form.name" 
								placeholder="Enter route name" 
								:disabled="form.processing" 
							/>
						</div>
						<div class="space-y-2">
							<Label for="description">Description</Label>
							<Textarea 
								id="description" 
								v-model="form.description" 
								placeholder="Enter route description" 
								:disabled="form.processing" 
								class="min-h-20"
							/>
						</div>
						<div class="bg-muted/30 border border-border/50 rounded-md p-3">
							<p class="text-xs text-muted-foreground mb-1">Note:</p>
							<p class="text-xs text-muted-foreground">Route geometry will be set to a default point. For complete route mapping functionality, integrate with a map component.</p>
						</div>
						<DialogFooter>
							<Button type="button" variant="ghost" @click="closeDialog" :disabled="form.processing">
								Close
							</Button>
							<Button type="submit" :disabled="form.processing">
								{{ editingRoute ? 'Update' : 'Create' }}
							</Button>
						</DialogFooter>
					</form>
				</DialogContent>
			</Dialog>
		</div>
	</AppLayout>
</template>
