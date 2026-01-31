<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ReportsController extends Controller
{
    /**
     * Store a new report in the database.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        // Validate the incoming request
        $validatedData = $request->validate([
        'order_id' => 'required|exists:orders,id',
        'status'   => 'required|in:has_problem,needs_return',
        'note'     => ['nullable', 'string', 'max:1000'], // Note is optional by default
        ]);

        // Add conditional validation for the note field
        if ($request->status === 'has_problem') {
            $request->validate([
            'note' => 'required|string|max:1000',
            ]);
        }

        // Check if the order ID already exists in the reports table
        $existingReport = Report::where('order_id', $validatedData['order_id'])->exists();

        if ($existingReport) {
            return response()->json([
            'message' => 'A report already exists for the given order ID',
            ], 409);
        }

        // Create a new report
        $report = Report::create([
        'order_id'  => $validatedData['order_id'],
        'seller_id' => Auth::id(), // Current authenticated seller
        'status'    => $validatedData['status'],
        'note'      => $validatedData['note'],
        ]);

        // Return a success response
        return response()->json([
        'message' => 'Report created successfully!',
        'report'  => $report,
        ], 201);
    }



    /**
     * Optionally: Fetch all reports for a specific order.
     *
     * @param  int $order_id
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
            $report->update(array( 'status' => 'solved' ));
            return response()->json(array( 'success' => true ));
        }

        return response()->json(array( 'success' => false ));
    }

    /**
     * Archive a report (set status to 'archived').
     * Allowed from 'has_problem' or 'solved' status.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function archiveReport(Request $request)
    {
        $request->validate([
            'report_id' => 'required|exists:reports,id',
        ]);

        $report = Report::find($request->report_id);
        Log::info('Archiving report', ['report_id' => $request->report_id, 'current_status' => $report->status]);
        if ($report && in_array($report->status, ['has_problem', 'archived'], true)) {
            $report->update(['status' => 'archived']);
            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false]);
    }
}
