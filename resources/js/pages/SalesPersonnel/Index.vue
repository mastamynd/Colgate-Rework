<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import Heading from '@/components/Heading.vue';
import { type BreadcrumbItem, type SalesPerson } from '@/types';
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
import { PlusIcon, PencilIcon, TrashIcon, ShieldXIcon, UserXIcon, UserCheckIcon } from 'lucide-vue-next';
import axios from 'axios';

const props = defineProps({
	salesPersonnel: {
		type: Object,
		required: true,
		default: () => { }
	}
});

const breadcrumbs: BreadcrumbItem[] = [
	{
		title: 'Sales Personnel',
		href: route('sales-personnel.index'),
	},
];

const showAddDialog = ref(false);
const editingPerson = ref<SalesPerson | null>(null);

const form = useForm({
	name: '',
	code: '',
	email: '',
	phone: '',
	type: ''
});

const closeDialog = () => {
	showAddDialog.value = false;
	editingPerson.value = null;
	form.reset();
	form.clearErrors();
};

const editPerson = (person: SalesPerson) => {
	editingPerson.value = person;
	form.name = person.name;
	form.code = person.code;
	form.email = person.email || '';
	form.phone = person.phone || '';
	form.type = person.type || '';
	showAddDialog.value = true;
};

const handleSubmit = async () => {
	try {
		if (editingPerson.value) {
			form.put(route('sales-personnel.update', editingPerson.value.id), {
				onSuccess: () => {
					closeDialog();
				},
				onError: (errors: any) => {
					console.error('Validation errors:', errors);
				}
			});
		} else {
			form.post(route('sales-personnel.store'), {
				onSuccess: () => {
					closeDialog();
				},
				onError: (errors: any) => {
					console.error('Validation errors:', errors);
				}
			});
		}
	} catch (error) {
		console.error('Error saving sales person:', error);
	}
};

const deactivatePerson = async (person: SalesPerson) => {
	if (confirm(`Are you sure you want to deactivate ${person.name}?`)) {
		try {
			await axios.patch(route('sales-personnel.deactivate', person.id));
			window.location.reload();
		} catch (error) {
			console.error('Error deactivating person:', error);
		}
	}
};

const activatePerson = async (person: SalesPerson) => {
	try {
		await axios.patch(route('sales-personnel.activate', person.id));
		window.location.reload();
	} catch (error) {
		console.error('Error activating person:', error);
	}
};

const deletePerson = async (person: SalesPerson) => {
	if (confirm(`Are you sure you want to permanently delete ${person.name}? This action cannot be undone.`)) {
		try {
			await axios.delete(route('sales-personnel.destroy', person.id));
			window.location.reload();
		} catch (error) {
			console.error('Error deleting person:', error);
		}
	}
};
</script>

<template>
	<Head title="Sales Personnel" description="Manage sales personnel" />

	<AppLayout :breadcrumbs="breadcrumbs">
		<div class="px-4 py-6">
			<div class="flex items-center justify-between">
				<Heading title="Sales Personnel" description="Manage sales personnel" />
				<Button @click="showAddDialog = true">
					Add Sales Person
					<PlusIcon class="h-4 w-4 ml-2" />
				</Button>
			</div>

			<div class="flex justify-between my-2 items-center flex-col sm:flex-row gap-1">
				<span class="py-2 text-sm text-muted-foreground">
					Showing {{ salesPersonnel.data?.length || 0 }} sales personnel
				</span>
				<Input type="search" placeholder="Search..." class="w-full sm:w-64" />
			</div>

			<div class="rounded-lg border bg-card">
				<div class="relative overflow-x-auto">
					<table class="w-full text-sm text-left">
						<thead class="text-xs uppercase bg-muted">
							<tr>
								<th scope="col" class="px-6 py-3">Name</th>
								<th scope="col" class="px-6 py-3">Code</th>
								<th scope="col" class="px-6 py-3">Email</th>
								<th scope="col" class="px-6 py-3">Phone</th>
								<th scope="col" class="px-6 py-3">Type</th>
								<th scope="col" class="px-6 py-3">Status</th>
								<th scope="col" class="px-6 py-3">Actions</th>
							</tr>
						</thead>
						<tbody>
							<tr v-if="!salesPersonnel.data || salesPersonnel.data.length === 0" class="border-b">
								<td colspan="7" class="px-6 py-8">
									<div class="flex flex-col items-center justify-center text-center">
										<ShieldXIcon class="h-12 w-12 text-muted-foreground/50" />
										<h3 class="mt-4 text-sm font-medium text-muted-foreground">No sales personnel found</h3>
										<p class="mt-1 text-sm text-muted-foreground/80">Get started by creating a new sales person.</p>
									</div>
								</td>
							</tr>
							<tr v-for="person in salesPersonnel.data" :key="person.id" class="border-b hover:bg-muted/50 transition-colors">
								<td class="px-6 py-4 font-medium">{{ person.name }}</td>
								<td class="px-6 py-4">{{ person.code }}</td>
								<td class="px-6 py-4">{{ person.email || '-' }}</td>
								<td class="px-6 py-4">{{ person.phone || '-' }}</td>
								<td class="px-6 py-4">{{ person.type || '-' }}</td>
								<td class="px-6 py-4">
									<span 
										class="inline-flex items-center px-2 py-1 rounded-full text-xs border"
										:class="person.is_active 
											? 'bg-green-100 text-green-800 border-green-200 dark:bg-green-900/20 dark:text-green-400 dark:border-green-800' 
											: 'bg-gray-100 text-gray-800 border-gray-200 dark:bg-gray-900/20 dark:text-gray-400 dark:border-gray-800'"
									>
										{{ person.is_active ? 'Active' : 'Inactive' }}
									</span>
								</td>
								<td class="px-6 py-4">
									<div class="flex items-center gap-2">
										<button @click="editPerson(person)" class="p-2 hover:bg-muted rounded-md transition-colors">
											<PencilIcon class="h-4 w-4" />
										</button>
										<button 
											v-if="person.is_active"
											@click="deactivatePerson(person)"
											class="p-2 hover:bg-yellow-100 dark:hover:bg-yellow-900/20 rounded-md text-yellow-600 dark:text-yellow-400 transition-colors"
											title="Deactivate"
										>
											<UserXIcon class="h-4 w-4" />
										</button>
										<button 
											v-else
											@click="activatePerson(person)"
											class="p-2 hover:bg-green-100 dark:hover:bg-green-900/20 rounded-md text-green-600 dark:text-green-400 transition-colors"
											title="Activate"
										>
											<UserCheckIcon class="h-4 w-4" />
										</button>
										<button @click="deletePerson(person)"
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
						<DialogTitle>{{ editingPerson ? 'Edit Sales Person' : 'Add Sales Person' }}</DialogTitle>
					</DialogHeader>
					<form @submit.prevent="handleSubmit" class="space-y-4">
						<div class="space-y-2">
							<Label for="name">Name</Label>
							<Input 
								id="name" 
								v-model="form.name" 
								placeholder="Enter sales person name" 
								:disabled="form.processing" 
							/>
						</div>
						<div class="space-y-2">
							<Label for="code">Code</Label>
							<Input 
								id="code" 
								v-model="form.code" 
								placeholder="Enter sales person code" 
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
							<Label for="phone">Phone</Label>
							<Input 
								id="phone" 
								v-model="form.phone" 
								placeholder="Enter phone number" 
								:disabled="form.processing" 
							/>
						</div>
						<div class="space-y-2">
							<Label for="type">Type</Label>
							<select 
								id="type"
								v-model="form.type"
								class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus:outline-none focus:ring-1 focus:ring-ring disabled:cursor-not-allowed disabled:opacity-50"
								:disabled="form.processing"
							>
								<option value="">Select type</option>
								<option value="Sales Representative">Sales Representative</option>
								<option value="Distributor">Distributor</option>
							</select>
						</div>
						<DialogFooter>
							<Button type="button" variant="ghost" @click="closeDialog" :disabled="form.processing">
								Close
							</Button>
							<Button type="submit" :disabled="form.processing">
								{{ editingPerson ? 'Update' : 'Create' }}
							</Button>
						</DialogFooter>
					</form>
				</DialogContent>
			</Dialog>
		</div>
	</AppLayout>
</template>
