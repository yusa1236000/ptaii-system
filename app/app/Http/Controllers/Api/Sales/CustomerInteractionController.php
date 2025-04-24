<?php

namespace App\Http\Controllers\Api\Sales;

use App\Models\Sales\CustomerInteraction;
use App\Models\Sales\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class CustomerInteractionController extends Controller
{
    /**
     * Display a listing of customer interactions.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $interactions = CustomerInteraction::with(['customer', 'user'])->get();
        return response()->json(['data' => $interactions], 200);
    }

    /**
     * Store a newly created customer interaction in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|exists:Customer,customer_id',
            'interaction_date' => 'required|date',
            'interaction_type' => 'required|string|max:50',
            'notes' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Get the authenticated user ID
        $userId = Auth::id();

        $interaction = CustomerInteraction::create([
            'customer_id' => $request->customer_id,
            'interaction_date' => $request->interaction_date,
            'interaction_type' => $request->interaction_type,
            'notes' => $request->notes,
            'user_id' => $userId
        ]);

        return response()->json(['data' => $interaction, 'message' => 'Customer interaction created successfully'], 201);
    }

    /**
     * Display the specified customer interaction.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $interaction = CustomerInteraction::with(['customer', 'user'])->find($id);
        
        if (!$interaction) {
            return response()->json(['message' => 'Customer interaction not found'], 404);
        }
        
        return response()->json(['data' => $interaction], 200);
    }

    /**
     * Update the specified customer interaction in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $interaction = CustomerInteraction::find($id);
        
        if (!$interaction) {
            return response()->json(['message' => 'Customer interaction not found'], 404);
        }
        
        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|exists:Customer,customer_id',
            'interaction_date' => 'required|date',
            'interaction_type' => 'required|string|max:50',
            'notes' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $interaction->update($request->all());
        return response()->json(['data' => $interaction, 'message' => 'Customer interaction updated successfully'], 200);
    }

    /**
     * Remove the specified customer interaction from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $interaction = CustomerInteraction::find($id);
        
        if (!$interaction) {
            return response()->json(['message' => 'Customer interaction not found'], 404);
        }
        
        $interaction->delete();
        return response()->json(['message' => 'Customer interaction deleted successfully'], 200);
    }

    /**
     * Get all interactions for a specific customer.
     *
     * @param  int  $customerId
     * @return \Illuminate\Http\Response
     */
    public function getCustomerInteractions($customerId)
    {
        $customer = Customer::find($customerId);
        
        if (!$customer) {
            return response()->json(['message' => 'Customer not found'], 404);
        }
        
        $interactions = CustomerInteraction::with('user')
            ->where('customer_id', $customerId)
            ->orderBy('interaction_date', 'desc')
            ->get();
        
        return response()->json(['data' => $interactions], 200);
    }
}