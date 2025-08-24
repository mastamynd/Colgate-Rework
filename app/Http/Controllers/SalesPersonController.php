<?php

namespace App\Http\Controllers;

use App\Models\SalesPerson;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SalesPersonController extends Controller
{
	/**
	 * Display a listing of the resource.
	 */
	public function index()
	{
		return Inertia::render('SalesPersonnel/Index', [
			"salesPersonnel" => Inertia::merge(
				SalesPerson::orderBy('name')->paginate()
			),
		]);
	}

	/**
	 * Store a newly created resource in storage.
	 */
	public function store(Request $request)
	{
		$request->validate([
			'name' => 'required|string|max:255',
			'code' => 'required|string|max:255|unique:sales_people,code',
			'email' => 'nullable|email|max:255',
			'phone' => 'nullable|string|max:255',
			'type' => 'nullable|in:Sales Representative,Distributor',
		]);

		SalesPerson::create($request->all());

		return redirect()->route('sales-personnel.index')->with('success', 'Sales person created successfully.');
	}

	/**
	 * Update the specified resource in storage.
	 */
	public function update(Request $request, SalesPerson $sales_personnel)
	{
		$request->validate([
			'name' => 'required|string|max:255',
			'code' => 'required|string|max:255|unique:sales_people,code,' . $sales_personnel->id,
			'email' => 'nullable|email|max:255',
			'phone' => 'nullable|string|max:255',
			'type' => 'nullable|in:Sales Representative,Distributor',
		]);

		$sales_personnel->update($request->all());

		return redirect()->route('sales-personnel.index')->with('success', 'Sales person updated successfully.');
	}

	/**
	 * Deactivate the specified sales person.
	 */
	public function deactivate(SalesPerson $sales_personnel)
	{
		$sales_personnel->deactivate();

		return redirect()->route('sales-personnel.index')->with('success', 'Sales person deactivated successfully.');
	}

	/**
	 * Activate the specified sales person.
	 */
	public function activate(SalesPerson $sales_personnel)
	{
		$sales_personnel->activate();

		return redirect()->route('sales-personnel.index')->with('success', 'Sales person activated successfully.');
	}

	/**
	 * Remove the specified resource from storage.
	 */
	public function destroy(SalesPerson $sales_personnel)
	{
		$sales_personnel->delete();

		return redirect()->route('sales-personnel.index')->with('success', 'Sales person deleted successfully.');
	}
}
