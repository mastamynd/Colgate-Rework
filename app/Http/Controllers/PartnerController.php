<?php

namespace App\Http\Controllers;

use App\Models\Partner;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PartnerController extends Controller
{
	/**
	 * Display a listing of the resource.
	 */
	public function index()
	{
		return Inertia::render('Partners/Index', [
			"partners" => Inertia::merge(
				Partner::orderBy('name')->paginate()
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
			'link' => 'nullable|url|max:255',
			'photo' => 'nullable|string|max:255',
		]);

		$partner = Partner::create($request->all());

		// if ($request->hasFile('photo')) {
		// 	$partner->photo = $request->file('photo')->store('partners', 'public');
		// 	$partner->save();
		// }


		return redirect()->route('partners.index')->with('success', 'Partner created successfully.');
	}

	/**
	 * Update the specified resource in storage.
	 */
	public function update(Request $request, Partner $partner)
	{
		$request->validate([
			'name' => 'required|string|max:255',
			'link' => 'nullable|url|max:255',
			'photo' => 'nullable|string|max:255',
		]);

		$partner = $partner->update($request->all());

		// if ($request->hasFile('photo')) {
		// 	$partner->photo = $request->file('photo')->store('partners', 'public');
		// 	$partner->save();
		// }


		return redirect()->route('partners.index')->with('success', 'Partner updated successfully.');
	}

	/**
	 * Deactivate the specified partner.
	 */
	public function deactivate(Partner $partner)
	{
		$partner->deactivate();

		return redirect()->route('partners.index')->with('success', 'Partner deactivated successfully.');
	}

	/**
	 * Activate the specified partner.
	 */
	public function activate(Partner $partner)
	{
		$partner->activate();

		return redirect()->route('partners.index')->with('success', 'Partner activated successfully.');
	}

	/**
	 * Remove the specified resource from storage.
	 */
	public function destroy(Partner $partner)
	{
		$partner->delete();

		return redirect()->route('partners.index')->with('success', 'Partner deleted successfully.');
	}
}
