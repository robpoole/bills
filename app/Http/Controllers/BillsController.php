<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use App\Models\Bill;
use App\Models\BillStage;
use App\Models\User;

class BillsController extends Controller
{
    public function summary()
    {
        $bills = Bill::leftJoin('bill_user', 'bill_user.bill_id', '=', 'bills.id')
            ->leftJoin('bill_stages', 'bills.bill_stage_id', '=', 'bill_stages.id')
            ->leftJoin('users', 'bill_user.user_id', '=', 'users.id')
            ->select('bills.bill_reference', 'users.name', 'bill_stages.label', 'bill_stages.color_name')
            ->get();

        $summary = $bills->groupBy('name')
            ->map(function ($userBills) {
                $counts = $userBills->groupBy('label')
                    ->map(function ($stageBills) {
                        return count($stageBills);
                    });

                return [
                    'submitted' => $counts->get('Submitted', 0),
                    'approved' => $counts->get('Approved', 0),
                    'onhold' => $counts->get('On Hold', 0),
                ];
            })
            ->toArray();

        return array_merge([
            'Submitted' => $bills->where('label', 'Submitted')->count(),
            'Approved' => $bills->where('label', 'Approved')->count(),
            'On Hold' => $bills->where('label', 'On Hold')->count(),
        ], ['users' => $summary]);
    }

    function create(Request $request)
    {
        // Validate request
        $validator = Validator::make($request->all(), [
            'bill_reference' => 'required',
            'bill_date' => 'required|date',
        ]);
      
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Invalid request data - please supply bill reference and date',
                'errors' => $validator->errors()
            ], 422);
        }
      
        $billReference = $request->input('bill_reference');
        $billDate = $request->input('bill_date');

        // Create and store new bill
        $bill = new Bill([
            'bill_reference' => $billReference,
            'bill_date' => $billDate,
            'bill_stage_id' => 2,
            'submitted_at' => now(),
        ]);
        $bill->save();
      
        // Return a success response
        return response()->json([
            'message' => 'Bill successfully created',
            'data' => $bill->toArray()
        ]);
    }

}
