<?php

namespace App\Http\Controllers;

use App\Models\ReReference;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReReferenceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = ReReference::withCount('customers');

        // Apply search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%");
            });
        }

        // Apply status filter
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->active();
            } elseif ($request->status === 'inactive') {
                $query->inactive();
            }
        }

        // Get per page value from request, default to 15
        $perPage = $request->get('per_page', 15);
        $perPage = in_array($perPage, [10, 15, 25, 50, 100]) ? $perPage : 15;

        return Inertia::render('ReReferences/Index', [
            "reReferences" => Inertia::merge(
                $query->orderBy('code')->paginate($perPage)->withQueryString()
            ),
            "filters" => $request->only(['search', 'status', 'per_page'])
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('ReReferences/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|string|max:255|unique:re_references,code',
            'name' => 'required|string|max:255',
            'color' => 'required|string|max:7|regex:/^#[0-9A-Fa-f]{6}$/',
            'is_active' => 'boolean',
        ]);

        ReReference::create($request->all());

        return redirect()->route('re-references.index')->with('success', 'RE Reference created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(ReReference $reReference)
    {
        $reReference->load(['customers' => function ($query) {
            $query->with(['salesPerson', 'route', 'county', 'constituency', 'ward', 'customerKd']);
        }]);

        return Inertia::render('ReReferences/Show', [
            'reReference' => $reReference
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ReReference $reReference)
    {
        return Inertia::render('ReReferences/Edit', [
            'reReference' => $reReference
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ReReference $reReference)
    {
        $request->validate([
            'code' => 'required|string|max:255|unique:re_references,code,' . $reReference->id,
            'name' => 'required|string|max:255',
            'color' => 'required|string|max:7|regex:/^#[0-9A-Fa-f]{6}$/',
            'is_active' => 'boolean',
        ]);

        $reReference->update($request->all());

        return redirect()->route('re-references.index')->with('success', 'RE Reference updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ReReference $reReference)
    {
        // Check if there are any customers using this RE reference
        if ($reReference->customers()->count() > 0) {
            return redirect()->route('re-references.index')
                ->with('error', 'Cannot delete RE Reference because it is being used by customers.');
        }

        $reReference->delete();

        return redirect()->route('re-references.index')->with('success', 'RE Reference deleted successfully.');
    }

    /**
     * Activate the specified resource.
     */
    public function activate(ReReference $reReference)
    {
        $reReference->activate();

        return redirect()->route('re-references.index')->with('success', 'RE Reference activated successfully.');
    }

    /**
     * Deactivate the specified resource.
     */
    public function deactivate(ReReference $reReference)
    {
        $reReference->deactivate();

        return redirect()->route('re-references.index')->with('success', 'RE Reference deactivated successfully.');
    }
}