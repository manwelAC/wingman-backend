<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $customers = Customer::where('pilot_id', $request->user()->id)
            ->orderBy('display_name')
            ->get();

        return response()->json($customers);
    }

    public function store(Request $request)
    {
        $request->validate([
            'display_name' => 'required|string|max:255',
            'email'        => 'nullable|email|max:255',
            'phone'        => 'nullable|string|max:20',
            'notes'        => 'nullable|string',
        ]);

        $customer = Customer::create([
            'pilot_id'     => $request->user()->id,
            'display_name' => $request->display_name,
            'email'        => $request->email,
            'phone'        => $request->phone,
            'notes'        => $request->notes,
        ]);

        return response()->json($customer, 201);
    }

    public function show(Request $request, $id)
    {
        $customer = Customer::where('pilot_id', $request->user()->id)
            ->findOrFail($id);

        $customer->load('grinds');

        return response()->json($customer);
    }

    public function update(Request $request, $id)
    {
        $customer = Customer::where('pilot_id', $request->user()->id)
            ->findOrFail($id);

        $request->validate([
            'display_name' => 'sometimes|string|max:255',
            'email'        => 'nullable|email|max:255',
            'phone'        => 'nullable|string|max:20',
            'notes'        => 'nullable|string',
        ]);

        $customer->update($request->only([
            'display_name',
            'email',
            'phone',
            'notes',
        ]));

        return response()->json($customer);
    }

    public function destroy(Request $request, $id)
    {
        $customer = Customer::where('pilot_id', $request->user()->id)
            ->findOrFail($id);

        $customer->delete();

        return response()->json([
            'message' => 'Customer deleted successfully.',
        ]);
    }
}