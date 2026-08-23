<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Services\Sms\SriLankanPhone;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    /**
     * Reject a duplicate phone with a friendly validation error instead of a
     * 500 from the customers.phone unique index. Compares on the canonical
     * (stored) form so 0771234567 / 94771234567 / +94771234567 all match.
     * Returns null when there is no conflict.
     */
    private function duplicatePhoneError(?string $rawPhone, ?int $ignoreId = null): ?string
    {
        if (empty($rawPhone)) {
            return null;
        }

        $canonical = SriLankanPhone::tryNormalizeLocal($rawPhone) ?? trim($rawPhone);

        $exists = Customer::where('phone', $canonical)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists();

        return $exists ? 'A customer with this phone number already exists.' : null;
    }

    public function index(Request $request)
    {
        $query = Customer::withCount('sales')
            ->withSum('sales', 'total')
            ->withMax('sales', 'created_at')
            ->with(['sales' => fn($q) => $q->latest()->take(5)->with('items.product')]);

        if ($request->filled('search')) {
            $s = trim($request->search);
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('phone', 'like', "%{$s}%")
                  ->orWhere('email', 'like', "%{$s}%");
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Sorting
        $sort = $request->get('sort', 'spent_desc');
        switch ($sort) {
            case 'name_asc':
                $query->orderBy('name', 'asc');
                break;
            case 'orders_desc':
                $query->orderByDesc('sales_count');
                break;
            case 'recent_visit':
                $query->orderByRaw('sales_max_created_at IS NULL, sales_max_created_at DESC');
                break;
            case 'newest':
                $query->latest('id');
                break;
            case 'spent_asc':
                $query->orderBy('sales_sum_total', 'asc');
                break;
            case 'spent_desc':
            default:
                $query->orderByRaw('sales_sum_total IS NULL, sales_sum_total DESC');
                break;
        }

        $customers = $query->paginate(20)->withQueryString();
        
        // KPI Stats
        $totalCustomers = Customer::count();
        $newThisMonth   = Customer::whereMonth('created_at', now()->month)
                                  ->whereYear('created_at', now()->year)
                                  ->count();
        
        $lastMonthDate  = now()->subMonth();
        $newLastMonth   = Customer::whereMonth('created_at', $lastMonthDate->month)
                                  ->whereYear('created_at', $lastMonthDate->year)
                                  ->count();
        
        $monthDelta = $newLastMonth > 0 ? round((($newThisMonth - $newLastMonth) / $newLastMonth) * 100, 1) : null;

        $totalStoreRevenue = (float) \App\Models\Sale::sum('total');
        $linkedRevenue     = (float) \App\Models\Sale::whereNotNull('customer_id')->sum('total');
        $linkedPercent     = $totalStoreRevenue > 0 ? round(($linkedRevenue / $totalStoreRevenue) * 100, 1) : 0.0;
        $avgCustomerValue  = $totalCustomers > 0 ? round($linkedRevenue / $totalCustomers, 2) : 0.0;

        $totalAllCustomers = $totalCustomers;

        return view('customers.index', compact(
            'customers',
            'totalCustomers',
            'newThisMonth',
            'monthDelta',
            'linkedRevenue',
            'totalStoreRevenue',
            'linkedPercent',
            'avgCustomerValue',
            'totalAllCustomers'
        ));
    }

    public function show(Customer $customer)
    {
        $customer->load(['sales.items.product', 'sales.user']);
        
        $totalSpent = $customer->sales->sum('total');
        $totalSales = $customer->sales->count();
        
        return view('customers.show', compact('customer', 'totalSpent', 'totalSales'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            // Name is optional — the phone identifies the row.
            'name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
        ]);

        if ($error = $this->duplicatePhoneError($validated['phone'] ?? null)) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $error], 422);
            }

            return back()->withErrors(['phone' => $error])->withInput();
        }

        // Phone is canonicalised by the Customer model's mutator.
        $customer = Customer::create($validated);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'customer' => $customer]);
        }

        return redirect()->route('customers.index')->with('success', 'Customer created successfully.');
    }

    public function update(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            // Name is optional — the phone identifies the row.
            'name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
        ]);

        if ($error = $this->duplicatePhoneError($validated['phone'] ?? null, $customer->id)) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $error], 422);
            }

            return back()->withErrors(['phone' => $error])->withInput();
        }

        $customer->update($validated);

        return redirect()->route('customers.index')->with('success', 'Customer updated successfully.');
    }

    public function destroy(Customer $customer)
    {
        $customer->delete();
        return redirect()->route('customers.index')->with('success', 'Customer deleted successfully.');
    }

    public function search(Request $request)
    {
        $query = $request->get('q', '');
        
        $customers = Customer::where('name', 'like', "%{$query}%")
            ->orWhere('phone', 'like', "%{$query}%")
            ->orderBy('name')
            ->limit(20)
            ->get();

        return response()->json($customers);
    }
}
