<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\Service;
use App\Models\Staff;
use App\Services\BookingService;
use Illuminate\Http\Request;

class AppointmentController extends Controller
{
    public function store(
        Request $request,
        Business $business,
        BookingService $bookingService
    ) {
        $validated = $request->validate([
            'staff_id' => 'required|integer|exists:staff,id',
            'service_id' => 'required|integer|exists:services,id',
            'appointment_date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'notes' => 'nullable|string|max:2000',
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

        $appointment = $bookingService->create(
            $business,
            $staff,
            $service,
            $request->user()->id,
            $validated
        );

        return response()->json([
            'message' => 'Appointment created successfully.',
            'appointment' => $appointment,
        ], 201);
    }
}