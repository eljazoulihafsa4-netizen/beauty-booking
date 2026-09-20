<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Business;
use App\Models\Service;
use App\Models\Staff;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AvailabilityController extends Controller
{
    public function index(
        Request $request,
        Business $business
    ) {
        $validated = $request->validate([
            'staff_id' => 'required|integer|exists:staff,id',
            'service_id' => 'required|integer|exists:services,id',
            'date' => 'required|date_format:Y-m-d',
        ]);

        $staff = Staff::query()
            ->where('id', $validated['staff_id'])
            ->where('business_id', $business->id)
            ->where('is_active', true)
            ->firstOrFail();

        $service = Service::query()
            ->where('id', $validated['service_id'])
            ->where('business_id', $business->id)
            ->where('is_active', true)
            ->firstOrFail();

        if (!$staff->services()->where('services.id', $service->id)->exists()) {
            return response()->json([
                'message' => 'This staff member does not provide the selected service.',
            ], 422);
        }

        $date = Carbon::createFromFormat(
            'Y-m-d',
            $validated['date']
        );

        $dayOfWeek = $date->dayOfWeek;

        $businessHours = $business->workingHours()
            ->where('day_of_week', $dayOfWeek)
            ->first();

        $staffHours = $staff->workingHours()
            ->where('day_of_week', $dayOfWeek)
            ->first();

        if (
            !$businessHours ||
            $businessHours->is_closed ||
            !$staffHours ||
            $staffHours->is_closed
        ) {
            return response()->json([
                'date' => $validated['date'],
                'available_slots' => [],
            ]);
        }

        $openTime = max(
            $businessHours->open_time,
            $staffHours->open_time
        );

        $closeTime = min(
            $businessHours->close_time,
            $staffHours->close_time
        );

        if ($openTime >= $closeTime) {
            return response()->json([
                'date' => $validated['date'],
                'available_slots' => [],
            ]);
        }

        $appointments = Appointment::query()
            ->where('staff_id', $staff->id)
            ->whereDate('appointment_date', $date->toDateString())
            ->whereNotIn('status', ['cancelled'])
            ->get([
                'start_time',
                'end_time',
            ]);

        $slots = [];
        $current = Carbon::parse($openTime);
        $closing = Carbon::parse($closeTime);
        $duration = $service->duration_minutes;

        while ($current->copy()->addMinutes($duration)->lte($closing)) {
            $slotStart = $current->format('H:i');
            $slotEnd = $current->copy()
                ->addMinutes($duration)
                ->format('H:i');

            $hasConflict = $appointments->contains(function ($appointment) use (
                $slotStart,
                $slotEnd
            ) {
                return substr($appointment->start_time, 0, 5) < $slotEnd
                    && substr($appointment->end_time, 0, 5) > $slotStart;
            });

            if (!$hasConflict) {
                $slots[] = [
                    'start_time' => $slotStart,
                    'end_time' => $slotEnd,
                ];
            }

            $current->addMinutes($duration);
        }

        return response()->json([
            'date' => $validated['date'],
            'staff_id' => $staff->id,
            'service_id' => $service->id,
            'service_duration_minutes' => $duration,
            'available_slots' => $slots,
        ]);
    }
}
