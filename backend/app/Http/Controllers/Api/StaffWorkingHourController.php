<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StaffWorkingHourController extends Controller
{
    private function authorizeManager(Request $request, Business $business): void
    {
        $membership = $request->user()
            ->businessMemberships()
            ->where('business_id', $business->id)
            ->first();

        if (!$membership || !in_array($membership->role, ['owner', 'manager'])) {
            abort(403, 'You are not authorized to manage working hours for this business.');
        }
    }

    public function index(
        Request $request,
        Business $business,
        Staff $staff
    ) {
        $this->authorizeManager($request, $business);

        abort_unless($staff->business_id === $business->id, 404);

        return response()->json([
            'working_hours' => $staff->workingHours()
                ->orderBy('day_of_week')
                ->get(),
        ]);
    }

    public function update(
        Request $request,
        Business $business,
        Staff $staff
    ) {
        $this->authorizeManager($request, $business);

        abort_unless($staff->business_id === $business->id, 404);

        $validated = $request->validate([
            'working_hours' => 'required|array|size:7',
            'working_hours.*.day_of_week' => 'required|integer|between:0,6',
            'working_hours.*.open_time' => 'nullable|date_format:H:i',
            'working_hours.*.close_time' => 'nullable|date_format:H:i',
            'working_hours.*.is_closed' => 'required|boolean',
        ]);

        $days = collect($validated['working_hours'])
            ->pluck('day_of_week');

        if ($days->duplicates()->isNotEmpty() || $days->unique()->count() !== 7) {
            return response()->json([
                'message' => 'Working hours must contain exactly one entry for each day of the week.',
            ], 422);
        }

        foreach ($validated['working_hours'] as $day) {
            if (!$day['is_closed']) {
                if (!$day['open_time'] || !$day['close_time']) {
                    return response()->json([
                        'message' => 'Open and close times are required for an open day.',
                    ], 422);
                }

                if ($day['open_time'] >= $day['close_time']) {
                    return response()->json([
                        'message' => 'Close time must be later than open time.',
                    ], 422);
                }
            }
        }

        DB::transaction(function () use ($staff, $validated) {
            foreach ($validated['working_hours'] as $day) {
                $staff->workingHours()->updateOrCreate(
                    [
                        'day_of_week' => $day['day_of_week'],
                    ],
                    [
                        'open_time' => $day['open_time'] ?? null,
                        'close_time' => $day['close_time'] ?? null,
                        'is_closed' => $day['is_closed'],
                    ]
                );
            }
        });

        return response()->json([
            'message' => 'Staff working hours updated successfully.',
            'working_hours' => $staff->workingHours()
                ->orderBy('day_of_week')
                ->get(),
        ]);
    }
}