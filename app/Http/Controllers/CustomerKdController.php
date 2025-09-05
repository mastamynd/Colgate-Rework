<?php

namespace App\Http\Controllers;

use App\Models\CustomerKd;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CustomerKdController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = CustomerKd::withCount('customers');

        // Apply search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%");
            });
        }

        // Get per page value from request, default to 15
        $perPage = $request->get('per_page', 15);
        $perPage = in_array($perPage, [10, 15, 25, 50, 100]) ? $perPage : 15;

        return Inertia::render('CustomerKds/Index', [
            "customerKds" => Inertia::merge(
                $query->orderBy('code')->paginate($perPage)->withQueryString()
            ),
            "filters" => $request->only(['search', 'per_page'])
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('CustomerKds/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|string|max:255|unique:customer_kds,code',
            'name' => 'required|string|max:255',
            'color' => 'required|string|max:7|regex:/^#[0-9A-Fa-f]{6}$/',
        ]);

        CustomerKd::create($request->all());

        return redirect()->route('customer-kds.index')->with('success', 'Customer KD created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show($code)
    {
        $customerKd = CustomerKd::where('code', $code)->firstOrFail();
        
        $customerKd->load(['customers' => function ($query) {
            $query->with(['salesPerson', 'route', 'county', 'constituency', 'ward']);
        }]);

        return Inertia::render('CustomerKds/Show', [
            'customerKd' => $customerKd
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($code)
    {
        $customerKd = CustomerKd::where('code', $code)->firstOrFail();
        
        return Inertia::render('CustomerKds/Edit', [
            'customerKd' => $customerKd
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $code)
    {
        $customerKd = CustomerKd::where('code', $code)->firstOrFail();
        
        $request->validate([
            'code' => 'required|string|max:255|unique:customer_kds,code,' . $customerKd->code . ',code',
            'name' => 'required|string|max:255',
            'color' => 'required|string|max:7|regex:/^#[0-9A-Fa-f]{6}$/',
        ]);

        $customerKd->update($request->all());

        return redirect()->route('customer-kds.index')->with('success', 'Customer KD updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($code)
    {
        $customerKd = CustomerKd::where('code', $code)->firstOrFail();
        
        // Check if there are any customers using this KD
        if ($customerKd->customers()->count() > 0) {
            return redirect()->route('customer-kds.index')
                ->with('error', 'Cannot delete Customer KD because it is being used by customers.');
        }

        $customerKd->delete();

        return redirect()->route('customer-kds.index')->with('success', 'Customer KD deleted successfully.');
    }
}
