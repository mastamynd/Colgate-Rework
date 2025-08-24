<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/vue3';
import { KeySquare, Shield, Users, LayoutGrid, UsersRound, SquareUserRound, Handshake, MapPinned, Route } from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';

const mainNavItems: NavItem[] = [
	{
		title: 'Dashboard',
		href: '/dashboard',
		icon: LayoutGrid,
	},
	{
		title: 'Sales Personnel',
		href: route('sales-personnel.index'),
		icon: SquareUserRound,
	},
	{
		title: 'Routes',
		href: '/routes',
		icon: Route,
	},
	{
		title: 'Customers',
		href: '/customers',
		icon: UsersRound,
	},
	{
		title: 'Partners',
		href: '/partners',
		icon: Handshake,
	},
	{
		title: 'Map Data',
		href: '/map-data',
		icon: MapPinned,
	},
];

const footerNavItems: NavItem[] = [
	{
		title: 'Roles',
		href: route('roles.index'),
		icon: Shield,
	},
	{
		title: 'Permissions',
		href: route('permissions.index'),
		icon: KeySquare,
	},
	{
		title: 'Users',
		href: route('users.index'),
		icon: Users,
	},
];
</script>

<template>
	<Sidebar collapsible="icon" variant="inset">
		<SidebarHeader>
			<SidebarMenu>
				<SidebarMenuItem>
					<SidebarMenuButton size="lg" as-child>
						<Link :href="route('dashboard')">
						<AppLogo />
						</Link>
					</SidebarMenuButton>
				</SidebarMenuItem>
			</SidebarMenu>
		</SidebarHeader>

		<SidebarContent>
			<NavMain :items="mainNavItems" />
		</SidebarContent>

		<SidebarFooter>
			<NavFooter :items="footerNavItems" />
			<NavUser />
		</SidebarFooter>
	</Sidebar>
	<slot />
</template>
