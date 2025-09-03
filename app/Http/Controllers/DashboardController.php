<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Route;
use App\Models\SalesPerson;
use App\Models\Boundary;
use App\Models\Demographic;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
	public $visibleCounties = [47,22,16];
	public function __construct(){
	}

	public function index()
	{
			// Get overview statistics
			$stats = [
					'total_customers' => Customer::count(),
					'active_customers' => Customer::active()->count(),
					'inactive_customers' => Customer::inactive()->count(),
					'total_sales_people' => SalesPerson::count(),
					'active_sales_people' => SalesPerson::active()->count(),
					'total_routes' => Route::count(),
					'active_routes' => Route::active()->count(),
			];

			// Customer activity trends (last 30 days)
			$customerTrends = Customer::select(
					DB::raw('DATE(created_at) as date'),
					DB::raw('COUNT(*) as count')
			)
			->where('created_at', '>=', Carbon::now()->subDays(30))
			->groupBy('date')
			->orderBy('date')
			->get();

			// Sales person performance
			$salesPersonPerformance = SalesPerson::withCount(['customers' => function ($query) {
					$query->where('is_active', true);
			}])
			->with('customers:id,sales_person_id,name')
			->get()
			->map(function ($person) {
					return [
							'id' => $person->id,
							'name' => $person->name,
							'code' => $person->code,
							'active_customers' => $person->customers_count,
							'is_active' => $person->is_active,
					];
			});

			// Geographic distribution (county-wise customer count)
			$geographicDistribution = Customer::select('boundaries.name as county_name')
					->selectRaw('COUNT(customers.id) as customer_count')
					->leftJoin('boundaries', 'customers.county_id', '=', 'boundaries.id')
					->where('boundaries.type', 'county')
					->groupBy('boundaries.name')
					->orderBy('customer_count', 'desc')
					->limit(10)
					->get();

			// Route utilization
			$routeUtilization = Route::withCount(['customers' => function ($query) {
					$query->where('is_active', true);
			}])
			->get()
			->map(function ($route) {
					return [
							'id' => $route->id,
							'name' => $route->name,
							'customer_count' => $route->customers_count,
							'is_active' => $route->is_active,
					];
			});

			// Recent customers (last 10)
			$recentCustomers = Customer::with(['salesPerson', 'route', 'county'])
					->latest()
					->limit(10)
					->get()
					->map(function ($customer) {
							return [
									'id' => $customer->id,
									'name' => $customer->name,
									'phone' => $customer->phone,
									'sales_person' => $customer->salesPerson?->name,
									'route' => $customer->route?->name,
									'county' => $customer->county?->name,
									'is_active' => $customer->is_active,
									'created_at' => $customer->created_at?->format('M d, Y'),
							];
					});

			// Customer status distribution
			$customerStatusDistribution = [
					['status' => 'Active', 'count' => $stats['active_customers'], 'color' => 'hsl(142, 71%, 45%)'],
					['status' => 'Inactive', 'count' => $stats['inactive_customers'], 'color' => 'hsl(var(--muted-foreground))']
			];

			// Sales people activity distribution
			$salesPeopleActivity = [
					['status' => 'Active', 'count' => $stats['active_sales_people'], 'color' => 'hsl(var(--primary))'],
					['status' => 'Inactive', 'count' => $stats['total_sales_people'] - $stats['active_sales_people'], 'color' => 'hsl(var(--muted-foreground))']
			];

			return Inertia::render('Dashboard', [
					'stats' => $stats,
					'customerTrends' => $customerTrends,
					'salesPersonPerformance' => $salesPersonPerformance,
					'geographicDistribution' => $geographicDistribution,
					'routeUtilization' => $routeUtilization,
					'recentCustomers' => $recentCustomers,
					'customerStatusDistribution' => $customerStatusDistribution,
					'salesPeopleActivity' => $salesPeopleActivity,
			]);
	}
}
