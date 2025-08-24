<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PermissionController extends Controller
{
	public function __construct()
	{
		$permissions = ['add permission', 'edit permission', 'delete permission'];
		$permissionsList = Permission::select("name")->where("guard_name", "web")->whereIn("name", $permissions)->pluck("name")->toArray();
		// dd(array_values($permissionsList));
		foreach ($permissions as $perm) {
			if (!in_array($perm, $permissionsList)) {
				Permission::create(['name' => $perm]);
			}
		}
	}
	/**
	 * Display a listing of the resource.
	 */
	public function index()
	{
		return Inertia::render('Permissions/Index', [
			"permissions" => Inertia::merge(Permission::paginate())
		]);
	}

	/**
	 * Store a newly created resource in storage.
	 */
	public function store(Request $request)
	{
		Permission::create($request->all());
		return redirect()->route('permissions.index');
	}

	/**
	 * Update the specified resource in storage.
	 */
	public function update(Request $request, Permission $permission)
	{
		$permission->update($request->all());
		return redirect()->route('permissions.index');
	}

	/**
	 * Remove the specified resource from storage.
	 */
	public function destroy(Permission $permission)
	{
		$permission->delete();
		return redirect()->route('permissions.index');
	}
}
