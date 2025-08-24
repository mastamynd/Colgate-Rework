<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { Users, UserCheck, Route as RouteIcon, TrendingUp, MapPin, Activity } from 'lucide-vue-next';
import StatsCard from '@/components/dashboard/StatsCard.vue';
import SimpleChart from '@/components/dashboard/SimpleChart.vue';
import RecentActivity from '@/components/dashboard/RecentActivity.vue';

interface DashboardStats {
	total_customers: number;
	active_customers: number;
	inactive_customers: number;
	total_sales_people: number;
	active_sales_people: number;
	total_routes: number;
	active_routes: number;
}

interface ChartDataItem {
	label: string;
	value: number;
	color?: string;
}

interface SalesPersonPerformance {
	id: string;
	name: string;
	code: string;
	active_customers: number;
	is_active: boolean;
}

interface GeographicDistribution {
	county_name: string;
	customer_count: number;
}

interface RouteUtilization {
	id: string;
	name: string;
	customer_count: number;
	is_active: boolean;
}

interface RecentCustomer {
	id: string;
	name: string;
	phone?: string;
	sales_person?: string;
	route?: string;
	county?: string;
	is_active: boolean;
	created_at: string;
}

interface CustomerTrend {
	date: string;
	count: number;
}

defineProps<{
	stats: DashboardStats;
	customerTrends: CustomerTrend[];
	salesPersonPerformance: SalesPersonPerformance[];
	geographicDistribution: GeographicDistribution[];
	routeUtilization: RouteUtilization[];
	recentCustomers: RecentCustomer[];
	customerStatusDistribution: ChartDataItem[];
	salesPeopleActivity: ChartDataItem[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
	{
		title: 'Dashboard',
		href: '/dashboard',
	},
];
</script>

<template>
	<Head title="Dashboard" />

	<AppLayout :breadcrumbs="breadcrumbs">
		<div class="flex flex-col gap-6 p-6">
			<!-- Header -->
			<div>
				<h1 class="text-3xl font-bold tracking-tight">Dashboard</h1>
				<p class="text-muted-foreground">
					Overview of your customer management system
				</p>
			</div>

			<!-- Key Statistics -->
			<div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
				<StatsCard
					title="Total Customers"
					:value="stats.total_customers"
					:subtitle="`${stats.active_customers} active, ${stats.inactive_customers} inactive`"
					:icon="Users"
				/>
				<StatsCard
					title="Sales Personnel"
					:value="stats.active_sales_people"
					:subtitle="`${stats.total_sales_people} total`"
					:icon="UserCheck"
				/>
				<StatsCard
					title="Active Routes"
					:value="stats.active_routes"
					:subtitle="`${stats.total_routes} total routes`"
					:icon="RouteIcon"
				/>
				<StatsCard
					title="Customer Activity"
					:value="`${Math.round((stats.active_customers / stats.total_customers) * 100) || 0}%`"
					subtitle="customers are active"
					:icon="TrendingUp"
				/>
			</div>

			<!-- Charts Row 1 -->
			<div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
				<!-- Customer Status Distribution -->
				<SimpleChart
					title="Customer Status"
					:data="customerStatusDistribution"
					type="pie"
					:height="250"
				/>

				<!-- Sales People Activity -->
				<SimpleChart
					title="Sales People Activity"
					:data="salesPeopleActivity"
					type="pie"
					:height="250"
				/>
			</div>

			<!-- Charts Row 2 -->
			<div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
				<!-- Geographic Distribution -->
				<SimpleChart
					title="Customers by County (Top 10)"
					:data="geographicDistribution.map(item => ({
						label: item.county_name || 'Unknown',
						value: item.customer_count
					}))"
					type="bar"
					:height="300"
				/>

				<!-- Sales Person Performance -->
				<SimpleChart
					title="Sales Person Performance"
					:data="salesPersonPerformance.slice(0, 10).map(item => ({
						label: item.name,
						value: item.active_customers,
						color: item.is_active ? 'hsl(var(--primary))' : 'hsl(var(--muted-foreground))'
					}))"
					type="bar"
					:height="300"
				/>
			</div>

			<!-- Route Utilization -->
			<div class="grid grid-cols-1 gap-6">
				<SimpleChart
					title="Route Utilization (Customer Count per Route)"
					:data="routeUtilization.slice(0, 15).map(item => ({
						label: item.name,
						value: item.customer_count,
						color: item.is_active ? 'hsl(142, 71%, 45%)' : 'hsl(var(--muted-foreground))'
					}))"
					type="bar"
					:height="350"
				/>
			</div>

			<!-- Recent Activity -->
			<div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
				<!-- Recent Customers -->
				<RecentActivity
					title="Recent Customers"
					:items="recentCustomers"
					empty-message="No recent customers"
				/>

				<!-- Quick Stats Panel -->
				<div class="rounded-xl border bg-card p-6">
					<h3 class="text-lg font-semibold mb-4">Quick Insights</h3>
					<div class="space-y-4">
						<div class="flex items-center justify-between p-3 rounded-lg bg-muted/20">
							<div class="flex items-center gap-3">
								<div class="p-2 rounded-full bg-primary/10">
									<MapPin class="h-4 w-4 text-primary" />
								</div>
								<span class="text-sm">Counties Covered</span>
							</div>
							<span class="font-semibold">{{ geographicDistribution.length }}</span>
						</div>

						<div class="flex items-center justify-between p-3 rounded-lg bg-muted/20">
							<div class="flex items-center gap-3">
								<div class="p-2 rounded-full bg-green-100 dark:bg-green-900/20">
									<Activity class="h-4 w-4 text-green-600 dark:text-green-400" />
								</div>
								<span class="text-sm">Avg Customers per Route</span>
							</div>
							<span class="font-semibold">
								{{ Math.round((stats.total_customers / Math.max(stats.total_routes, 1)) * 10) / 10 }}
							</span>
						</div>

						<div class="flex items-center justify-between p-3 rounded-lg bg-muted/20">
							<div class="flex items-center gap-3">
								<div class="p-2 rounded-full bg-blue-100 dark:bg-blue-900/20">
									<TrendingUp class="h-4 w-4 text-blue-600 dark:text-blue-400" />
								</div>
								<span class="text-sm">Avg Customers per Sales Person</span>
							</div>
							<span class="font-semibold">
								{{ Math.round((stats.total_customers / Math.max(stats.total_sales_people, 1)) * 10) / 10 }}
							</span>
						</div>
					</div>
				</div>
			</div>

			<!-- Customer Registration Trends -->
			<div v-if="customerTrends.length > 0" class="grid grid-cols-1 gap-6">
				<SimpleChart
					title="Customer Registration Trends (Last 30 Days)"
					:data="customerTrends.map(item => ({
						label: new Date(item.date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' }),
						value: item.count
					}))"
					type="line"
					:height="300"
				/>
			</div>
		</div>
	</AppLayout>
</template>
