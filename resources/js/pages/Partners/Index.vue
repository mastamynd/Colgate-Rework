<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import Heading from '@/components/Heading.vue';
import { type BreadcrumbItem, type Partner } from '@/types';
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
import { PlusIcon, PencilIcon, TrashIcon, ShieldXIcon, UserXIcon, UserCheckIcon, ExternalLinkIcon } from 'lucide-vue-next';
import axios from 'axios';

const props = defineProps({
	partners: {
		type: Object,
		required: true,
		default: () => { }
	}
});

const breadcrumbs: BreadcrumbItem[] = [
	{
		title: 'Partners',
		href: route('partners.index'),
	},
];

const showAddDialog = ref(false);
const editingPartner = ref<Partner | null>(null);

const form = useForm({
	name: '',
	link: '',
	photo: ''
});

const closeDialog = () => {
	showAddDialog.value = false;
	editingPartner.value = null;
	form.reset();
	form.clearErrors();
};

const editPartner = (partner: Partner) => {
	editingPartner.value = partner;
	form.name = partner.name;
	form.link = partner.link || '';
	form.photo = partner.photo || '';
	showAddDialog.value = true;
};

const handleSubmit = async () => {
	try {
		if (editingPartner.value) {
			form.put(route('partners.update', editingPartner.value.id), {
				onSuccess: () => {
					closeDialog();
				},
				onError: (errors: any) => {
					console.error('Validation errors:', errors);
				}
			});
		} else {
			form.post(route('partners.store'), {
				onSuccess: () => {
					closeDialog();
				},
				onError: (errors: any) => {
					console.error('Validation errors:', errors);
				}
			});
		}
	} catch (error) {
		console.error('Error saving partner:', error);
	}
};

const deactivatePartner = async (partner: Partner) => {
	if (confirm(`Are you sure you want to deactivate ${partner.name}?`)) {
		try {
			await axios.patch(route('partners.deactivate', partner.id));
			window.location.reload();
		} catch (error) {
			console.error('Error deactivating partner:', error);
		}
	}
};

const activatePartner = async (partner: Partner) => {
	try {
		await axios.patch(route('partners.activate', partner.id));
		window.location.reload();
	} catch (error) {
		console.error('Error activating partner:', error);
	}
};

const deletePartner = async (partner: Partner) => {
	if (confirm(`Are you sure you want to permanently delete ${partner.name}? This action cannot be undone.`)) {
		try {
			await axios.delete(route('partners.destroy', partner.id));
			window.location.reload();
		} catch (error) {
			console.error('Error deleting partner:', error);
		}
	}
};
</script>

<template>
	<Head title="Partners" description="Manage partners" />

	<AppLayout :breadcrumbs="breadcrumbs">
		<div class="px-4 py-6">
			<div class="flex items-center justify-between">
				<Heading title="Partners" description="Manage business partners" />
				<Button @click="showAddDialog = true">
					Add Partner
					<PlusIcon class="h-4 w-4 ml-2" />
				</Button>
			</div>

			<div class="flex justify-between my-2 items-center flex-col sm:flex-row gap-1">
				<span class="py-2 text-sm text-muted-foreground">
					Showing {{ partners.data?.length || 0 }} partners
				</span>
				<Input type="search" placeholder="Search..." class="w-full sm:w-64" />
			</div>

			<div class="rounded-lg border bg-card">
				<div class="relative overflow-x-auto">
					<table class="w-full text-sm text-left">
						<thead class="text-xs uppercase bg-muted">
							<tr>
								<th scope="col" class="px-6 py-3">Name</th>
								<th scope="col" class="px-6 py-3">Link</th>
								<th scope="col" class="px-6 py-3">Photo</th>
								<th scope="col" class="px-6 py-3">Status</th>
								<th scope="col" class="px-6 py-3">Actions</th>
							</tr>
						</thead>
						<tbody>
							<tr v-if="!partners.data || partners.data.length === 0" class="border-b">
								<td colspan="5" class="px-6 py-8">
									<div class="flex flex-col items-center justify-center text-center">
										<ShieldXIcon class="h-12 w-12 text-muted-foreground/50" />
										<h3 class="mt-4 text-sm font-medium text-muted-foreground">No partners found</h3>
										<p class="mt-1 text-sm text-muted-foreground/80">Get started by creating a new partner.</p>
									</div>
								</td>
							</tr>
							<tr v-for="partner in partners.data" :key="partner.id" class="border-b hover:bg-muted/50 transition-colors">
								<td class="px-6 py-4 font-medium">{{ partner.name }}</td>
								<td class="px-6 py-4">
									<div v-if="partner.link" class="flex items-center gap-2">
										<a :href="partner.link" target="_blank" class="text-primary hover:underline flex items-center gap-1">
											Visit Link
											<ExternalLinkIcon class="h-3 w-3" />
										</a>
									</div>
									<span v-else>-</span>
								</td>
								<td class="px-6 py-4">
									<div v-if="partner.photo" class="flex items-center gap-2">
										<img :src="partner.photo" :alt="partner.name" class="h-8 w-8 rounded object-cover" />
										<span class="text-xs text-muted-foreground">Image</span>
									</div>
									<span v-else>-</span>
								</td>
								<td class="px-6 py-4">
									<span 
										class="inline-flex items-center px-2 py-1 rounded-full text-xs border"
										:class="partner.is_active 
											? 'bg-green-100 text-green-800 border-green-200 dark:bg-green-900/20 dark:text-green-400 dark:border-green-800' 
											: 'bg-gray-100 text-gray-800 border-gray-200 dark:bg-gray-900/20 dark:text-gray-400 dark:border-gray-800'"
									>
										{{ partner.is_active ? 'Active' : 'Inactive' }}
									</span>
								</td>
								<td class="px-6 py-4">
									<div class="flex items-center gap-2">
										<button @click="editPartner(partner)" class="p-2 hover:bg-muted rounded-md transition-colors">
											<PencilIcon class="h-4 w-4" />
										</button>
										<button 
											v-if="partner.is_active"
											@click="deactivatePartner(partner)"
											class="p-2 hover:bg-yellow-100 dark:hover:bg-yellow-900/20 rounded-md text-yellow-600 dark:text-yellow-400 transition-colors"
											title="Deactivate"
										>
											<UserXIcon class="h-4 w-4" />
										</button>
										<button 
											v-else
											@click="activatePartner(partner)"
											class="p-2 hover:bg-green-100 dark:hover:bg-green-900/20 rounded-md text-green-600 dark:text-green-400 transition-colors"
											title="Activate"
										>
											<UserCheckIcon class="h-4 w-4" />
										</button>
										<button @click="deletePartner(partner)"
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
						<DialogTitle>{{ editingPartner ? 'Edit Partner' : 'Add Partner' }}</DialogTitle>
					</DialogHeader>
					<form @submit.prevent="handleSubmit" class="space-y-4">
						<div class="space-y-2">
							<Label for="name">Name</Label>
							<Input 
								id="name" 
								v-model="form.name" 
								placeholder="Enter partner name" 
								:disabled="form.processing" 
							/>
						</div>
						<div class="space-y-2">
							<Label for="link">Website Link</Label>
							<Input 
								id="link" 
								type="url"
								v-model="form.link" 
								placeholder="https://example.com" 
								:disabled="form.processing" 
							/>
						</div>
						<div class="space-y-2">
							<Label for="photo">Photo URL</Label>
							<Input 
								id="photo" 
								type="url"
								v-model="form.photo" 
								placeholder="https://example.com/logo.png" 
								:disabled="form.processing" 
							/>
						</div>
						<DialogFooter>
							<Button type="button" variant="ghost" @click="closeDialog" :disabled="form.processing">
								Close
							</Button>
							<Button type="submit" :disabled="form.processing">
								{{ editingPartner ? 'Update' : 'Create' }}
							</Button>
						</DialogFooter>
					</form>
				</DialogContent>
			</Dialog>
		</div>
	</AppLayout>
</template>
