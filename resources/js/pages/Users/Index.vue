<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import Heading from '@/components/Heading.vue';
import { type BreadcrumbItem } from '@/types';
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
import { Checkbox } from '@/components/ui/checkbox';
import { Textarea } from '@/components/ui/input';
import { PlusIcon, PencilIcon, TrashIcon, ShieldXIcon, Loader2 } from 'lucide-vue-next';
import axios from 'axios';

const props = defineProps({
	users: {
		type: Object,
		required: true,
		default: () => { }
	},
	allRoles: {
		type: Array,
		default: () => []
	}
});

const breadcrumbs: BreadcrumbItem[] = [
	{
		title: 'Users',
		href: route('users.index'),
	},
];

const showAddDialog = ref(false);
const editingUser = ref(null);
const isSubmitting = ref(false);

const form = useForm({
	name: '',
	email: '',
	phone: '',
	password: '',
	password_confirmation: '',
	roles: []
});

const closeDialog = () => {
	showAddDialog.value = false;
	editingUser.value = null;
	form.name = '';
	form.email = '';
	form.phone = '';
	form.password = '';
	form.password_confirmation = '';
	form.roles = [];
	form.reset();
};

const editUser = (user: any) => {
	editingUser.value = user;
	form.name = user.name;
	form.email = user.email;
	form.phone = user.phone || '';
	// Ensure role IDs are strings to match what the backend expects
	form.roles = user.roles ? user.roles.map((role: any) => String(role.id)) : [];
	showAddDialog.value = true;
};

const handleSubmit = async () => {
	// Debug: log the form data
	console.log('Form data:', {
		name: form.name,
		email: form.email,
		phone: form.phone,
		roles: form.roles,
		editing: !!editingUser.value
	});
	
	if (editingUser.value) {
		form.put(route('users.update', editingUser.value.id), {
			onSuccess: () => {
				closeDialog();
				// Force page refresh to see updated data
				window.location.reload();
			},
			onError: (errors) => {
				console.error('Update errors:', errors);
			}
		});
	} else {
		form.post(route('users.store'), {
			onSuccess: () => {
				closeDialog();
				// Force page refresh to see new user
				window.location.reload();
			},
			onError: (errors) => {
				console.error('Create errors:', errors);
			}
		});
	}
};

const addUser = () => {
	editingUser.value = null;
	form.name = '';
	form.email = '';
	form.phone = '';
	form.password = '';
	form.password_confirmation = '';
	form.roles = [];
	form.reset();
	showAddDialog.value = true;
};

const deleteUser = async (id: any) => {
	if (confirm('Are you sure you want to delete this user?')) {
		try {
			await axios.delete(route('users.destroy', id)).then(() => {
				window.location.reload();
			});
		} catch (error) {
			console.error('Error deleting user:', error);
		}
	}
};
</script>
<template>

	<Head title="Users" description="View and manage user accounts, including their roles, users and account settings" />

	<AppLayout :breadcrumbs="breadcrumbs">
		<div class="px-4 py-6">
			<div class="flex items-center justify-between">
				<Heading title="Users" description="Manage system users" />
				<Button @click="addUser">
					Add User
					<PlusIcon class="h-4 w-4" />
				</Button>
			</div>

			<!-- Users list -->
			<div class="flex justify-between my-2 items-center flex-col sm:flex-row gap-1">
				<span class="py-2 text-sm dark:text-gray-300 text-gray-600">Showing {{ users.data.length }}
					users</span>
				<Input type="search" placeholder="Search..." class="w-full  sm:w-64" />
			</div>
			<div class="rounded-lg border bg-card">
				<div class="relative overflow-x-auto">
					<table class="w-full text-sm text-left">
						<thead class="text-xs uppercase bg-muted">
							<tr>
								<th scope="col" class="px-6 py-3">Name</th>
								<th scope="col" class="px-6 py-3">Email</th>
								<th scope="col" class="px-6 py-3">Phone</th>
								<th scope="col" class="px-6 py-3">Roles</th>
								<th scope="col" class="px-6 py-3">Actions</th>
							</tr>
						</thead>
						<tbody>
							<tr v-if="!users.data || users.data.length === 0" class="border-b">
								<td colspan="5" class="px-6 py-8">
									<div class="flex flex-col items-center justify-center text-center">
										<ShieldXIcon class="h-12 w-12 text-muted-foreground/50" />
										<h3 class="mt-4 text-sm font-medium text-muted-foreground">No users found</h3>
										<p class="mt-1 text-sm text-muted-foreground/80">Get started by creating a new user.</p>
									</div>
								</td>
							</tr>
							<tr v-for="user in users.data" :key="user.id"
								class="border-b hover:bg-muted/50 transition-colors">
								<td class="px-6 py-4 font-medium">{{ user.name }}</td>
								<td class="px-6 py-4">{{ user.email }}</td>
								<td class="px-6 py-4">{{ user.phone || '-' }}</td>
								<td class="px-6 py-4">
									<div class="flex flex-wrap gap-1">
										<span v-if="!user.roles || user.roles.length === 0" class="text-muted-foreground text-sm">-</span>
										<span v-for="role in user.roles" :key="role.id"
											class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-primary/20 text-primary border border-primary/30 dark:bg-primary/30 dark:text-primary-foreground dark:border-primary/50">
											{{ role.name }}
										</span>
									</div>
								</td>
								<td class="px-6 py-4">
									<div class="flex items-center gap-2">
										<button @click="editUser(user)" class="p-2 hover:bg-muted rounded-md transition-colors">
											<PencilIcon class="h-4 w-4" />
										</button>
										<button @click="deleteUser(user.id)"
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
				<DialogContent class="sm:max-w-[500px] max-h-[80vh] overflow-y-auto">
					<DialogHeader>
						<DialogTitle>{{ editingUser ? 'Edit User' : 'Add User' }}</DialogTitle>
					</DialogHeader>
					<form @submit.prevent="handleSubmit" class="space-y-4">
						<div class="space-y-2">
							<Label for="name">Name</Label>
							<Input id="name" v-model="form.name" placeholder="Enter user name" :disabled="form.loading" />
						</div>
						<div class="space-y-2">
							<Label for="email">Email</Label>
							<Input id="email" type="email" v-model="form.email" placeholder="Enter email address" :disabled="form.loading" />
						</div>
						<div class="space-y-2">
							<Label for="phone">Phone</Label>
							<Input id="phone" v-model="form.phone" placeholder="Enter phone number" :disabled="form.loading" />
						</div>
						<div v-if="!editingUser" class="space-y-2">
							<Label for="password">Password</Label>
							<Input id="password" type="password" v-model="form.password" placeholder="Enter password" :disabled="form.loading" />
						</div>
						<div v-if="!editingUser" class="space-y-2">
							<Label for="password_confirmation">Confirm Password</Label>
							<Input id="password_confirmation" type="password" v-model="form.password_confirmation" placeholder="Confirm password" :disabled="form.loading" />
						</div>
						<div v-if="editingUser" class="space-y-2">
							<Label for="password">New Password (leave blank to keep current)</Label>
							<Input id="password" type="password" v-model="form.password" placeholder="Enter new password" :disabled="form.loading" />
						</div>
						<div v-if="editingUser" class="space-y-2">
							<Label for="password_confirmation">Confirm New Password</Label>
							<Input id="password_confirmation" type="password" v-model="form.password_confirmation" placeholder="Confirm new password" :disabled="form.loading" />
						</div>
						<div class="space-y-3">
							<Label class="text-sm font-medium">Assign Roles</Label>
							<div class="grid grid-cols-1 gap-3 max-h-32 overflow-y-auto pr-2">
								<div v-for="role in allRoles" :key="role.id" 
									class="flex items-center space-x-3 p-3 rounded-lg border border-border hover:bg-muted/50 transition-colors duration-200"
									:class="{ 'bg-muted/30': form.roles.includes(String(role.id)) }">
									<Checkbox 
										:id="`role-${role.id}`"
										:checked="form.roles.includes(String(role.id))"
										:disabled="form.loading"
										@update:checked="(checked) => {
											const roleIdStr = String(role.id);
											if (checked) {
												if (!form.roles.includes(roleIdStr)) {
													form.roles.push(roleIdStr);
												}
											} else {
												const index = form.roles.indexOf(roleIdStr);
												if (index > -1) {
													form.roles.splice(index, 1);
												}
											}
										}"
										class="data-[state=checked]:bg-primary data-[state=checked]:border-primary"
									/>
									<label :for="`role-${role.id}`" class="flex-1 text-sm font-medium cursor-pointer select-none">
										{{ role.name }}
									</label>
								</div>
								<div v-if="!allRoles || allRoles.length === 0" class="text-center py-4 text-muted-foreground text-sm">
									No roles available
								</div>
							</div>
						</div>
						<DialogFooter>
							<Button type="button" variant="ghost" :disabled="form.loading" @click="closeDialog">
								Close
							</Button>
							<Button type="submit" :disabled="form.loading">
								<Loader2 v-if="form.loading" class="h-4 w-4 mr-2 animate-spin" />
								{{ form.loading ? (editingUser ? 'Updating...' : 'Creating...') : (editingUser ? 'Update' : 'Create') }}
							</Button>
						</DialogFooter>
					</form>
				</DialogContent>
			</Dialog>
		</div>
	</AppLayout>
</template>
