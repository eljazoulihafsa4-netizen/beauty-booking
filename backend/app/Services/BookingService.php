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
                    'end_time' => 'End time must be later than start time.',
                ]);
            }

            $duration = $start->diffInMinutes($end);

            if ($duration !== $service->duration_minutes) {
                throw ValidationException::withMessages([
                    'end_time' => 'Appointment duration must match the selected service duration.',
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
                    'appointment_date' => 'The business is closed on this day.',
                ]);
            }

            if (
                $data['start_time'] < $businessHours->open_time ||
                $data['end_time'] > $businessHours->close_time
            ) {
                throw ValidationException::withMessages([
                    'start_time' => 'The appointment is outside business working hours.',
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
                    'appointment_date' => 'The staff member is not working on this day.',
                ]);
            }

            if (
                $data['start_time'] < $staffHours->open_time ||
                $data['end_time'] > $staffHours->close_time
            ) {
                throw ValidationException::withMessages([
                    'start_time' => 'The appointment is outside staff working hours.',
                ]);
            }

            if (
                !$staff->services()
                    ->where('services.id', $service->id)
                    ->exists()
            ) {
                throw ValidationException::withMessages([
                    'service_id' => 'This staff member does not provide the selected service.',
                ]);
            }

            $hasConflict = Appointment::query()
                ->where('staff_id', $staff->id)
                ->where('appointment_date', $date->toDateString())
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
                    'start_time' => 'This time slot is already booked.',
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