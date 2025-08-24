<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RoleController extends Controller
{
	public function __construct()
	{
		$roles = ['add role', 'edit role', 'delete role'];
		$rolesList = Permission::select("name")->where("guard_name", "web")->whereIn("name", $roles)->pluck("name")->toArray();
		// dd(array_values($rolesList));
		foreach ($roles as $perm) {
			if (!in_array($perm, $rolesList)) {
				Permission::create(['name' => $perm]);
			}
		}
	}
	/**
	 * Display a listing of the resource.
	 */
	public function index()
	{
		return Inertia::render('Roles/Index', [
			"roles" => Inertia::merge(Role::with('permissions')->paginate()),
			"permissions" => Permission::all(),
		]);
	}

	/**
	 * Store a newly created resource in storage.
	 */
	public function store(Request $request)
	{
		Role::create($request->all());
		return redirect()->route('roles.index');
	}

	/**
	 * Update the specified resource in storage.
	 */
	public function update(Request $request, Role $role)
	{
		// dd($request->all());
		$role->update($request->all());
		return redirect()->route('roles.index');
	}

	/**
	 * Remove the specified resource from storage.
	 */
	public function destroy(Role $role)
	{
		$role->delete();
		return redirect()->route('roles.index');
	}
}
