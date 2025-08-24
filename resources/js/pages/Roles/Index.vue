<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import Heading from '@/components/Heading.vue';
import { type BreadcrumbItem, type Permission, type Role } from '@/types';
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
import { Checkbox } from '@/components/ui/checkbox';
import { TransitionRoot, TransitionGroup } from '@headlessui/vue';
import { PlusIcon, PencilIcon, TrashIcon, ShieldXIcon, SearchIcon, PackageXIcon, LoaderIcon } from 'lucide-vue-next';
import axios from 'axios';


const props = defineProps({
	roles: {
		type: Object,
		default: {}
	},
	permissions: {
		type: Object,
		default: {}
	},
});

const breadcrumbs: BreadcrumbItem[] = [
	{
		title: 'Roles',
		href: route('roles.index'),
	},
];

const showAddDialog = ref(false);
const editingRole = ref<number | null>(null);
const permissionSearch = ref('');
const permissionsLoading = ref(false);
const permissionsError = ref<string | null>(null);

const form = useForm({
	name: '',
	permissions: []
});

const filteredPermissions = computed(() => {
	return props.permissions?.filter((permission: Permission) =>
		permission.name.toLowerCase().includes(permissionSearch.value.toLowerCase())
	);
});


const selectAllState = computed(() => {
	return filteredPermissions.value?.every((permission: Permission) =>
		form.permissions.includes(permission.name));
});

const addToSelectedPermissions = (permissionName: string) => {
	const index = form.permissions.indexOf(permissionName);
	if (index > -1) {
		form.permissions.splice(index, 1);
	} else {
		form.permissions.push(permissionName);
	}
}

const toggleAllPermissions = () => {
	const allNames = filteredPermissions.value?.map((p: Permission) => p.name);
	if (selectAllState.value) {
		form.permissions = form.permissions.filter((name: string) => !allNames.includes(name));
	} else {
		form.permissions = [...new Set([...form.permissions, ...allNames])];
	}
};

const isSelected = (permission: Permission) => {
	return form.permissions.includes(permission.name);
};

const closeDialog = () => {
	showAddDialog.value = false;
	editingRole.value = null;
	permissionSearch.value = '';
	form.reset();
	form.clearErrors();
};

const editRole = (role: Role) => {
	editingRole.value = role.id;
	form.name = role.name;
	form.permissions = role.permissions.map((p: Permission) => p.name);
	showAddDialog.value = true;
};

const handleSubmit = async () => {
	try {
		if (editingRole.value) {
			form.put(route('roles.update', editingRole.value), {
				onSuccess: () => {
					closeDialog();
				},
				onError: (errors: any) => {
					console.error('Validation errors:', errors);
				}
			});
		} else {
			form.post(route('roles.store'), {
				onSuccess: () => {
					closeDialog();
				},
				onError: (errors: any) => {
					console.error('Validation errors:', errors);
				}
			});
		}
	} catch (error) {
		console.error('Error saving role:', error);
	}
};

const deleteRole = async (id: number) => {
	if (confirm('Are you sure you want to delete this role?')) {
		try {
			await axios.delete(route('roles.destroy', id));
			window.location.reload();
		} catch (error) {
			console.error('Error deleting role:', error);
		}
	}
};
</script>

<template>
	<Head title="Roles" description="Manage system roles" />

	<AppLayout :breadcrumbs="breadcrumbs">
		<div class="px-4 py-6">
			<div class="flex items-center justify-between">
				<Heading title="Roles" description="Manage system roles" />
				<Button @click="showAddDialog = true">
					Add Role
					<PlusIcon class="h-4 w-4 ml-2" />
				</Button>
			</div>

			<div class="flex justify-between my-2 items-center flex-col sm:flex-row gap-1">
				<span class="py-2 text-sm text-muted-foreground">
					Showing {{ roles.data.length }} roles
				</span>
				<Input type="search" placeholder="Search..." class="w-full sm:w-64" />
			</div>

			<div class="rounded-lg border bg-card">
				<div class="relative overflow-x-auto">
					<table class="w-full text-sm text-left">
						<thead class="text-xs uppercase bg-muted">
							<tr>
								<th scope="col" class="px-6 py-3">Name</th>
								<th scope="col" class="px-6 py-3">Permissions</th>
								<th scope="col" class="px-6 py-3">Actions</th>
							</tr>
						</thead>
						<tbody>
							<tr v-if="roles.data.length === 0" class="border-b">
								<td colspan="3" class="px-6 py-8">
									<div class="flex flex-col items-center justify-center text-center">
										<ShieldXIcon class="h-12 w-12 text-muted-foreground/50" />
										<h3 class="mt-4 text-sm font-medium text-muted-foreground">No roles found</h3>
										<p class="mt-1 text-sm text-muted-foreground/80">Get started by creating a new role.</p>
									</div>
								</td>
							</tr>
							<tr v-for="role in roles.data" :key="role.id" class="border-b hover:bg-muted/50 transition-colors">
								<td class="px-6 py-4 font-medium">{{ role.name }}</td>
								<td class="px-6 py-4">
									<div class="flex flex-wrap gap-1">
										<span v-for="permission in role.permissions" :key="permission.id"
											class="px-2 py-1 text-xs rounded-full bg-primary/10 text-primary">
											{{ permission.name }}
										</span>
									</div>
								</td>
								<td class="px-6 py-4">
									<div class="flex items-center gap-2">
										<button @click="editRole(role)" class="p-2 hover:bg-muted rounded-md transition-colors">
											<PencilIcon class="h-4 w-4" />
										</button>
										<button @click="deleteRole(role.id)"
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
						<DialogTitle>{{ editingRole ? 'Edit Role' : 'Add Role' }}</DialogTitle>
					</DialogHeader>
					<form @submit.prevent="handleSubmit" class="space-y-4">
						<div class="space-y-2">
							<Label for="name">Name</Label>
							<Input id="name" v-model="form.name" placeholder="Enter role name..." :disabled="form.processing" />
						</div>

						<div class="space-y-2">
							<div class="flex items-center justify-between">
								<Label for="permissions">Permissions</Label>
								<Button type="button" variant="ghost" size="sm" @click="toggleAllPermissions"
									class="text-primary text-xs">
									{{ selectAllState ? 'Deselect All' : 'Select All' }}
								</Button>
							</div>

							<div class="relative group">
								<Input v-model="permissionSearch" placeholder="Search permissions..." class="pr-8 mb-2"
									:disabled="form.processing" />
								<SearchIcon class="h-4 w-4 absolute right-3 top-2.5 text-muted-foreground" />
							</div>

							<TransitionRoot tag="div" appear :show="!form.processing"
								enter="transform transition duration-300 ease-out" enter-from="opacity-0 -translate-y-2"
								enter-to="opacity-100 translate-y-0">
								<div class="max-h-48 overflow-y-auto space-y-1 p-1 rounded-lg border bg-muted/50"
									:class="{ 'animate-pulse': permissionsLoading }">
									<TransitionGroup name="list" tag="div" class="grid grid-cols-1 gap-1">
										<div v-for="permission in filteredPermissions" :key="permission.id" class="relative">
											<div
												class="flex items-center space-x-2 p-2 rounded-md transition-all duration-200 ease-in-out cursor-pointer"
												:class="{
													'bg-primary/10 border border-primary/20': isSelected(permission),
													'hover:bg-accent/50 hover:scale-[1.005]': !isSelected(permission)
												}">
																							<Checkbox :id="permission.id" @update:checked="addToSelectedPermissions(permission.name)" 
												:checked="isSelected(permission)"
												class="rounded-[4px] border-2 data-[state=checked]:border-primary"
												:disabled="form.processing" />
												<Label :for="permission.id" class="text-sm font-medium cursor-pointer flex-1">
													{{ permission.name }}
												</Label>
												<span v-if="isSelected(permission)" class="h-2 w-2 bg-primary rounded-full animate-pulse" />
											</div>
										</div>
									</TransitionGroup>

									<div v-if="filteredPermissions?.length === 0" class="text-center p-4 text-muted-foreground">
										<PackageXIcon class="h-6 w-6 mx-auto mb-2 opacity-50" />
										<p class="text-xs">No permissions found</p>
									</div>
								</div>
							</TransitionRoot>

							<div v-if="permissionsError" class="text-red-500 text-sm">
								Error loading permissions. Please try again.
							</div>
						</div>

						<DialogFooter>
							<Button type="button" variant="ghost" @click="closeDialog" class="hover:scale-105 transition-transform"
								:disabled="form.processing">
								Close
							</Button>
							<Button type="submit" :disabled="form.processing" class="relative hover:scale-105 transition-transform">
								<span class="transition-opacity" :class="{ 'opacity-0': form.processing }">
									{{ editingRole ? 'Update' : 'Create' }}
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