<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Business;
use App\Models\Service;
use App\Models\Staff;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BookingService
{
    public function create(
        Business $business,
        Staff $staff,
        Service $service,
        int $userId,
        array $data
    ): Appointment {
        return DB::transaction(function () use (
            $business,
            $staff,
            $service,
            $userId,
            $data
        ) {
            $date = Carbon::parse($data['appointment_date']);

            $start = Carbon::createFromFormat(
                'H:i',
                $data['start_time']
            );

            $end = Carbon::createFromFormat(
                'H:i',
                $data['end_time']
            );

            if ($start->gte($end)) {
                throw ValidationException::withMessages([
                    'end_time' => __('booking.end_time_must_be_later'),
                ]);
            }

            $duration = (int) $start->diffInMinutes($end);

            if ($duration !== $service->duration_minutes) {
                throw ValidationException::withMessages([
                    'end_time' => __('booking.duration_must_match'),
                ]);
            }

            $dayOfWeek = $date->dayOfWeek;

            $businessHours = $business->workingHours()
                ->where('day_of_week', $dayOfWeek)
                ->first();

            if (
                !$businessHours ||
                $businessHours->is_closed
            ) {
                throw ValidationException::withMessages([
                    'appointment_date' => __('booking.business_closed'),
                ]);
            }

            if (
                $data['start_time'] < $businessHours->open_time ||
                $data['end_time'] > $businessHours->close_time
            ) {
                throw ValidationException::withMessages([
                    'start_time' => __('booking.outside_business_hours'),
                ]);
            }

            $staffHours = $staff->workingHours()
                ->where('day_of_week', $dayOfWeek)
                ->first();

            if (
                !$staffHours ||
                $staffHours->is_closed
            ) {
                throw ValidationException::withMessages([
                    'appointment_date' => __('booking.staff_not_working'),
                ]);
            }

            if (
                $data['start_time'] < $staffHours->open_time ||
                $data['end_time'] > $staffHours->close_time
            ) {
                throw ValidationException::withMessages([
                    'start_time' => __('booking.outside_staff_hours'),
                ]);
            }

            if (
                !$staff->services()
                    ->where('services.id', $service->id)
                    ->exists()
            ) {
                throw ValidationException::withMessages([
                    'service_id' => __('booking.service_not_provided'),
                ]);
            }

            $hasConflict = Appointment::query()
                ->where('staff_id', $staff->id)
                ->whereDate('appointment_date', $date->toDateString())
                ->whereNotIn('status', ['cancelled'])
                ->where(function ($query) use ($data) {
                    $query
                        ->where('start_time', '<', $data['end_time'])
                        ->where('end_time', '>', $data['start_time']);
                })
                ->lockForUpdate()
                ->exists();

            if ($hasConflict) {
                throw ValidationException::withMessages([
                    'start_time' => __('booking.slot_already_booked'),
                ]);
            }

            return Appointment::create([
                'business_id' => $business->id,
                'staff_id' => $staff->id,
                'service_id' => $service->id,
                'user_id' => $userId,
                'appointment_date' => $date->toDateString(),
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'],
                'status' => 'pending',
                'payment_status' => 'pending',
                'price' => $service->price,
                'notes' => $data['notes'] ?? null,
            ]);
        });
    }
}