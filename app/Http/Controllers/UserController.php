<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class UserController extends Controller
{
	public function __construct()
	{
		$roles = ['add user', 'edit user', 'delete user'];
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
		return Inertia::render('Users/Index', [
			"users" => User::with('roles')->paginate(10),
			"allRoles" => Role::all(['id', 'name'])
		]);
	}

	/**
	 * Show the form for creating a new resource.
	 */
	public function create()
	{
		return Inertia::render('Users/Create', [
			'allRoles' => Role::all(['id', 'name'])
		]);
	}

	/**
	 * Store a newly created resource in storage.
	 */
	public function store(Request $request)
	{
		$validated = $request->validate([
			'name' => 'required|string|max:255',
			'email' => 'required|string|email|max:255|unique:users',
			'phone' => 'nullable|string|max:20',
			'password' => 'required|string|min:8|confirmed',
			'roles' => 'array',
			'roles.*' => 'exists:roles,id'
		]);

		$user = User::create([
			'name' => $validated['name'],
			'email' => $validated['email'],
			'phone' => $validated['phone'],
			'password' => bcrypt($validated['password'])
		]);

		// Assign roles if provided
		if (isset($validated['roles'])) {
			$roleIds = $request->input('roles', []);
			if (!empty($roleIds)) {
				$roles = Role::whereIn('id', $roleIds)->get();
				$user->syncRoles($roles);
			}
		}

		return redirect()->route('users.index')->with('success', 'User created successfully.');
	}

	/**
	 * Display the specified resource.
	 */
	public function show(User $user)
	{
		return Inertia::render('Users/Show', [
			'user' => $user->load('roles')
		]);
	}

	/**
	 * Show the form for editing the specified resource.
	 */
	public function edit(User $user)
	{
		return Inertia::render('Users/Edit', [
			'user' => $user->load('roles'),
			'allRoles' => Role::all(['id', 'name'])
		]);
	}

	/**
	 * Update the specified resource in storage.
	 */
	public function update(Request $request, User $user)
	{
		// Debug: log the incoming request data
		\Log::info('User update request data:', [
			'user_id' => $user->id,
			'request_data' => $request->all(),
			'roles' => $request->input('roles', [])
		]);

		$validated = $request->validate([
			'name' => 'required|string|max:255',
			'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
			'phone' => 'nullable|string|max:20',
			'password' => 'nullable|string|min:8|confirmed',
			'roles' => 'array',
			'roles.*' => 'exists:roles,id'
		]);

		$updateData = [
			'name' => $validated['name'],
			'email' => $validated['email'],
			'phone' => $validated['phone']
		];

		// Only update password if provided
		if (!empty($validated['password'])) {
			$updateData['password'] = bcrypt($validated['password']);
		}

		$user->update($updateData);

		// Update roles
		$roleIds = $request->input('roles', []);
		\Log::info('Role sync data:', [
			'user_id' => $user->id,
			'role_ids' => $roleIds,
			'current_roles' => $user->roles->pluck('id', 'name')->toArray()
		]);
		
		$roles = Role::whereIn('id', $roleIds)->get();
		\Log::info('Found roles:', [
			'found_roles' => $roles->pluck('id', 'name')->toArray()
		]);
		
		$user->syncRoles($roles);
		
		\Log::info('Roles after sync:', [
			'user_id' => $user->id,
			'new_roles' => $user->fresh()->roles->pluck('id', 'name')->toArray()
		]);

		return redirect()->route('users.index')->with('success', 'User updated successfully.');
	}

	/**
	 * Remove the specified resource from storage.
	 */
	public function destroy(User $user)
	{
		// Prevent deleting own account
		if ($user->id === Auth::id()) {
			return redirect()->route('users.index')->with('error', 'You cannot delete your own account.');
		}

		$user->delete();

		return redirect()->route('users.index')->with('success', 'User deleted successfully.');
	}
}
