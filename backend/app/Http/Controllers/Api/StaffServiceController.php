<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\Staff;
use Illuminate\Http\Request;

class StaffServiceController extends Controller
{
    private function authorizeManager(Request $request, Business $business): void
    {
        $membership = $request->user()
            ->businessMemberships()
            ->where('business_id', $business->id)
            ->first();

        if (!$membership || !in_array($membership->role, ['owner', 'manager'])) {
            abort(403, 'You are not authorized to manage staff services for this business.');
        }
    }

    public function update(Request $request, Business $business, Staff $staff)
    {
        $this->authorizeManager($request, $business);

        abort_unless($staff->business_id === $business->id, 404);

        $validated = $request->validate([
            'service_ids' => 'required|array',
            'service_ids.*' => 'integer|exists:services,id',
        ]);

        $serviceIds = $business->services()
            ->whereIn('id', $validated['service_ids'])
            ->pluck('id')
            ->all();

        if (count($serviceIds) !== count($validated['service_ids'])) {
            return response()->json([
                'message' => 'One or more services do not belong to this business.',
            ], 422);
        }

        $staff->services()->sync($serviceIds);

        return response()->json([
            'message' => 'Staff services updated successfully.',
            'services' => $staff->services()->orderBy('name')->get(),
        ]);
    }
}