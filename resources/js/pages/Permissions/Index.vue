<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import Heading from '@/components/Heading.vue';
import { type BreadcrumbItem, type Permission } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';
import PlaceholderPattern from '../components/PlaceholderPattern.vue';

import { ref, reactive } from 'vue';
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
import { PlusIcon, PencilIcon, TrashIcon, ShieldXIcon, LoaderIcon } from 'lucide-vue-next';
import axios from 'axios';

const props = defineProps({
	permissions: {
		type: Object,
		required: true,
		default: () => { }
	}
});

const breadcrumbs: BreadcrumbItem[] = [
	{
		title: 'Permissions',
		href: route('permissions.index'),
	},
];

const showAddDialog = ref(false);
const editingPermission = ref(null);
const isSubmitting = ref(false);

const form = useForm({
	name: '',
	description: ''
});

const closeDialog = () => {
	showAddDialog.value = false;
	editingPermission.value = null;
	form.reset();
	form.clearErrors();
};

const editPermission = (permission: Permission) => {
	editingPermission.value = permission;
	form.name = permission.name;
	showAddDialog.value = true;
};

const handleSubmit = async () => {
	if (editingPermission.value) {
		form.put(route('permissions.update', editingPermission.value.id), {
			onSuccess: () => {
				closeDialog();
			},
			onError: (errors: any) => {
				console.error('Validation errors:', errors);
			}
		});
	} else {
		form.post(route('permissions.store'), {
			onSuccess: () => {
				closeDialog();
			},
			onError: (errors: any) => {
				console.error('Validation errors:', errors);
			}
		});
	}
};

const deletePermission = async (id: any) => {
	if (confirm('Are you sure you want to delete this permission?')) {
		try {
			await axios.delete(route('permissions.destroy', id)).then(() => {
				window.location.reload();
			});
		} catch (error) {
			console.error('Error deleting permission:', error);
		}
	}
};
</script>
<template>

	<Head title="Permissions" description="Manage system permissions" />

	<AppLayout :breadcrumbs="breadcrumbs">
		<div class="px-4 py-6">
			<div class="flex items-center justify-between">
				<Heading title="Permissions" description="Manage system permissions" />
				<Button @click="showAddDialog = true">
					Add Permission
					<PlusIcon class="h-4 w-4" />
				</Button>
			</div>

			<!-- Permissions list -->
			<div class="flex justify-between my-2 items-center flex-col sm:flex-row gap-1">
				<span class="py-2 text-sm dark:text-gray-300 text-gray-600">Showing {{ permissions.data.length }}
					permissions</span>
				<Input type="search" placeholder="Search..." class="w-full  sm:w-64" />
			</div>
			<div class="rounded-lg border bg-card">
				<div class="relative overflow-x-auto">
					<table class="w-full text-sm text-left">
						<thead class="text-xs uppercase bg-muted">
							<tr>
								<th scope="col" class="px-6 py-3">Name</th>
								<th scope="col" class="px-6 py-3">Roles</th>
								<th scope="col" class="px-6 py-3">Actions</th>
							</tr>
						</thead>
						<tbody>
							<tr v-if="!permissions.data || permissions.data.length === 0" class="border-b">
								<td colspan="3" class="px-6 py-8">
									<div class="flex flex-col items-center justify-center text-center">
										<ShieldXIcon class="h-12 w-12 text-muted-foreground/50" />
										<h3 class="mt-4 text-sm font-medium text-muted-foreground">No permissions found</h3>
										<p class="mt-1 text-sm text-muted-foreground/80">Get started by creating a new permission.</p>
									</div>
								</td>
							</tr>
							<tr v-for="permission in permissions.data" :key="permission.id"
								class="border-b hover:bg-muted/50 transition-colors">
								<td class="px-6 py-4 font-medium">{{ permission.name }}</td>
								<td class="px-6 py-4">{{ permission.description }}</td>
								<td class="px-6 py-4">
									<div class="flex items-center gap-2">
										<button @click="editPermission(permission)" class="p-2 hover:bg-muted rounded-md transition-colors">
											<PencilIcon class="h-4 w-4" />
										</button>
										<button @click="deletePermission(permission.id)"
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
						<DialogTitle>{{ editingPermission ? 'Edit Permission' : 'Add Permission' }}</DialogTitle>
					</DialogHeader>
					<form @submit.prevent="handleSubmit" class="space-y-4">
						<div class="space-y-2">
							<Label for="name">Name</Label>
							<Input id="name" v-model="form.name" placeholder="Enter permission name" :disabled="form.processing" />
						</div>
						<DialogFooter>
							<Button type="button" variant="ghost" :disabled="form.processing" @click="closeDialog" class="hover:scale-105 transition-transform">
								Close
							</Button>
							<Button type="submit" :disabled="form.processing" class="relative hover:scale-105 transition-transform">
								<span class="transition-opacity" :class="{ 'opacity-0': form.processing }">
									{{ editingPermission ? 'Update' : 'Create' }}
								</span>
								<span v-if="form.processing" class="absolute inset-0 flex items-center justify-center">
									<LoaderIcon class="h-4 w-4 animate-spin" />
								</span>
							</Button>
						</DialogFooter>
					</form>
				</DialogContent>
			</Dialog>
		</div>
	</AppLayout>
</template>
