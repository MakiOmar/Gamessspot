<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReportsController extends Controller
{
    /**
     * Store a new report in the database.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        // Validate the incoming request
        $validatedData = $request->validate([
            'order_id' => 'required|exists:orders,id',
            'status' => 'required|in:has_problem,needs_return',
            'note' => 'required|string|max:1000'
        ]);

        // Check if the authenticated user is allowed to report on this order
        $order = Order::find($validatedData['order_id']);
        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        // Create a new report
        $report = Report::create([
            'order_id' => $validatedData['order_id'],
            'seller_id' => Auth::id(), // Current authenticated seller
            'status' => $validatedData['status'],
            'note' => $validatedData['note'],
        ]);

        // Return a success response
        return response()->json(['message' => 'Report created successfully!'], 201);
    }

    /**
     * Optionally: Fetch all reports for a specific order.
     *
     * @param  int  $order_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getReportsForOrder($order_id)
    {
        // Validate if the order exists
        $order = Order::findOrFail($order_id);

        // Fetch all reports related to the order
        $reports = Report::where('order_id', $order_id)->get();

        return response()->json($reports);
    }

    public function solveProblem(Request $request)
    {
        // Find the report by its ID
        $report = Report::find($request->report_id);

        if ($report && $report->status === 'has_problem') {
            // Update the status to 'solved'
            $report->update(['status' => 'solved']);
            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false]);
    }
}
