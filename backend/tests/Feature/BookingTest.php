<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\BusinessMember;
use App\Models\Service;
use App\Models\Staff;
use App\Models\StaffWorkingHour;
use App\Models\BusinessWorkingHour;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use App\Models\Appointment;

class BookingTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_an_appointment(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $business = Business::create([
            'name' => 'Test Beauty Salon',
            'slug' => 'test-beauty-salon',
            'country' => 'DE',
            'timezone' => 'Europe/Berlin',
            'currency' => 'EUR',
        ]);

        BusinessMember::create([
            'business_id' => $business->id,
            'user_id' => $user->id,
            'role' => 'owner',
        ]);

        $staff = Staff::create([
            'business_id' => $business->id,
            'name' => 'Sarah',
            'is_active' => true,
        ]);

        $service = Service::create([
            'business_id' => $business->id,
            'name' => 'Manicure',
            'price' => 20,
            'duration_minutes' => 30,
            'is_active' => true,
        ]);

        $staff->services()->attach($service->id);

        BusinessWorkingHour::create([
            'business_id' => $business->id,
            'day_of_week' => 1,
            'open_time' => '09:00',
            'close_time' => '18:00',
            'is_closed' => false,
        ]);

        StaffWorkingHour::create([
            'staff_id' => $staff->id,
            'day_of_week' => 1,
            'open_time' => '10:00',
            'close_time' => '16:00',
            'is_closed' => false,
        ]);

        $response = $this->postJson(
            "/api/businesses/{$business->id}/appointments",
            [
                'staff_id' => $staff->id,
                'service_id' => $service->id,
                'appointment_date' => '2026-09-21',
                'start_time' => '10:00',
                'end_time' => '10:30',
                'notes' => 'Test appointment',
            ]
        );

        $response
            ->assertCreated()
            ->assertJsonPath('appointment.status', 'pending')
            ->assertJsonPath('appointment.payment_status', 'pending')
            ->assertJsonPath('appointment.price', '20.00');

        $this->assertDatabaseHas('appointments', [
            'business_id' => $business->id,
            'staff_id' => $staff->id,
            'service_id' => $service->id,
            'user_id' => $user->id,
            'appointment_date' => '2026-09-21 00:00:00',
            'start_time' => '10:00',
            'end_time' => '10:30',
        ]);
    }
    public function test_user_cannot_double_book_the_same_staff_slot(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $business = Business::create([
            'name' => 'Test Beauty Salon',
            'slug' => 'test-beauty-salon',
            'country' => 'DE',
            'timezone' => 'Europe/Berlin',
            'currency' => 'EUR',
        ]);

        BusinessMember::create([
            'business_id' => $business->id,
            'user_id' => $user->id,
            'role' => 'owner',
        ]);

        $staff = Staff::create([
            'business_id' => $business->id,
            'name' => 'Sarah',
            'is_active' => true,
        ]);

        $service = Service::create([
            'business_id' => $business->id,
            'name' => 'Manicure',
            'price' => 20,
            'duration_minutes' => 30,
            'is_active' => true,
        ]);

        $staff->services()->attach($service->id);

        BusinessWorkingHour::create([
            'business_id' => $business->id,
            'day_of_week' => 1,
            'open_time' => '09:00',
            'close_time' => '18:00',
            'is_closed' => false,
        ]);

        StaffWorkingHour::create([
            'staff_id' => $staff->id,
            'day_of_week' => 1,
            'open_time' => '10:00',
            'close_time' => '16:00',
            'is_closed' => false,
        ]);

        $firstResponse = $this->postJson(
            "/api/businesses/{$business->id}/appointments",
            [
                'staff_id' => $staff->id,
                'service_id' => $service->id,
                'appointment_date' => '2026-09-21',
                'start_time' => '10:00',
                'end_time' => '10:30',
            ]
        );

        $firstResponse->assertCreated();

        $secondResponse = $this->postJson(
            "/api/businesses/{$business->id}/appointments",
            [
                'staff_id' => $staff->id,
                'service_id' => $service->id,
                'appointment_date' => '2026-09-21',
                'start_time' => '10:00',
                'end_time' => '10:30',
            ]
        );

        $secondResponse
            ->assertStatus(422)
            ->assertJsonValidationErrors('start_time');
    }
    public function test_user_cannot_book_an_overlapping_staff_slot(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $business = Business::create([
            'name' => 'Test Beauty Salon',
            'slug' => 'test-beauty-salon',
            'country' => 'DE',
            'timezone' => 'Europe/Berlin',
            'currency' => 'EUR',
        ]);

        BusinessMember::create([
            'business_id' => $business->id,
            'user_id' => $user->id,
            'role' => 'owner',
        ]);

        $staff = Staff::create([
            'business_id' => $business->id,
            'name' => 'Sarah',
            'is_active' => true,
        ]);

        $service = Service::create([
            'business_id' => $business->id,
            'name' => 'Manicure',
            'price' => 20,
            'duration_minutes' => 30,
            'is_active' => true,
        ]);

        $staff->services()->attach($service->id);

        BusinessWorkingHour::create([
            'business_id' => $business->id,
            'day_of_week' => 1,
            'open_time' => '09:00',
            'close_time' => '18:00',
            'is_closed' => false,
        ]);

        StaffWorkingHour::create([
            'staff_id' => $staff->id,
            'day_of_week' => 1,
            'open_time' => '10:00',
            'close_time' => '16:00',
            'is_closed' => false,
        ]);

        $firstResponse = $this->postJson(
            "/api/businesses/{$business->id}/appointments",
            [
                'staff_id' => $staff->id,
                'service_id' => $service->id,
                'appointment_date' => '2026-09-21',
                'start_time' => '10:00',
                'end_time' => '10:30',
            ]
        );

        $firstResponse->assertCreated();

        $overlappingResponse = $this->postJson(
            "/api/businesses/{$business->id}/appointments",
            [
                'staff_id' => $staff->id,
                'service_id' => $service->id,
                'appointment_date' => '2026-09-21',
                'start_time' => '10:15',
                'end_time' => '10:45',
            ]
        );

        $overlappingResponse
            ->assertStatus(422)
            ->assertJsonValidationErrors('start_time');
    }
    public function test_user_cannot_book_outside_staff_working_hours(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $business = Business::create([
            'name' => 'Test Beauty Salon',
            'slug' => 'test-beauty-salon',
            'country' => 'DE',
            'timezone' => 'Europe/Berlin',
            'currency' => 'EUR',
        ]);

        BusinessMember::create([
            'business_id' => $business->id,
            'user_id' => $user->id,
            'role' => 'owner',
        ]);

        $staff = Staff::create([
            'business_id' => $business->id,
            'name' => 'Sarah',
            'is_active' => true,
        ]);

        $service = Service::create([
            'business_id' => $business->id,
            'name' => 'Manicure',
            'price' => 20,
            'duration_minutes' => 30,
            'is_active' => true,
        ]);

        $staff->services()->attach($service->id);

        BusinessWorkingHour::create([
            'business_id' => $business->id,
            'day_of_week' => 1,
            'open_time' => '09:00',
            'close_time' => '18:00',
            'is_closed' => false,
        ]);

        StaffWorkingHour::create([
            'staff_id' => $staff->id,
            'day_of_week' => 1,
            'open_time' => '10:00',
            'close_time' => '16:00',
            'is_closed' => false,
        ]);

        $response = $this->postJson(
            "/api/businesses/{$business->id}/appointments",
            [
                'staff_id' => $staff->id,
                'service_id' => $service->id,
                'appointment_date' => '2026-09-21',
                'start_time' => '16:00',
                'end_time' => '16:30',
            ]
        );

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors('start_time');
    }

    public function test_user_cannot_book_a_service_not_provided_by_staff(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $business = Business::create([
            'name' => 'Test Beauty Salon',
            'slug' => 'test-beauty-salon',
            'country' => 'DE',
            'timezone' => 'Europe/Berlin',
            'currency' => 'EUR',
        ]);

        BusinessMember::create([
            'business_id' => $business->id,
            'user_id' => $user->id,
            'role' => 'owner',
        ]);

        $staff = Staff::create([
            'business_id' => $business->id,
            'name' => 'Sarah',
            'is_active' => true,
        ]);

        $service = Service::create([
            'business_id' => $business->id,
            'name' => 'Manicure',
            'price' => 20,
            'duration_minutes' => 30,
            'is_active' => true,
        ]);

        BusinessWorkingHour::create([
            'business_id' => $business->id,
            'day_of_week' => 1,
            'open_time' => '09:00',
            'close_time' => '18:00',
            'is_closed' => false,
        ]);

        StaffWorkingHour::create([
            'staff_id' => $staff->id,
            'day_of_week' => 1,
            'open_time' => '10:00',
            'close_time' => '16:00',
            'is_closed' => false,
        ]);

        $response = $this->postJson(
            "/api/businesses/{$business->id}/appointments",
            [
                'staff_id' => $staff->id,
                'service_id' => $service->id,
                'appointment_date' => '2026-09-21',
                'start_time' => '10:00',
                'end_time' => '10:30',
            ]
        );

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors('service_id');
    }
    public function test_user_cannot_book_when_business_is_closed(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $business = Business::create([
            'name' => 'Test Beauty Salon',
            'slug' => 'test-beauty-salon',
            'country' => 'DE',
            'timezone' => 'Europe/Berlin',
            'currency' => 'EUR',
        ]);

        BusinessMember::create([
            'business_id' => $business->id,
            'user_id' => $user->id,
            'role' => 'owner',
        ]);

        $staff = Staff::create([
            'business_id' => $business->id,
            'name' => 'Sarah',
            'is_active' => true,
        ]);

        $service = Service::create([
            'business_id' => $business->id,
            'name' => 'Manicure',
            'price' => 20,
            'duration_minutes' => 30,
            'is_active' => true,
        ]);

        $staff->services()->attach($service->id);

        // Business is closed on Monday.
        BusinessWorkingHour::create([
            'business_id' => $business->id,
            'day_of_week' => 1,
            'is_closed' => true,
        ]);

        // Staff is technically working on Monday.
        StaffWorkingHour::create([
            'staff_id' => $staff->id,
            'day_of_week' => 1,
            'open_time' => '10:00',
            'close_time' => '16:00',
            'is_closed' => false,
        ]);

        $response = $this->postJson(
            "/api/businesses/{$business->id}/appointments",
            [
                'staff_id' => $staff->id,
                'service_id' => $service->id,
                'appointment_date' => '2026-09-21',
                'start_time' => '10:00',
                'end_time' => '10:30',
            ]
        );

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors('appointment_date');
    }
    public function test_user_can_cancel_their_appointment(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $business = Business::create([
            'name' => 'Test Beauty Salon',
            'slug' => 'test-beauty-salon',
            'country' => 'DE',
            'timezone' => 'Europe/Berlin',
            'currency' => 'EUR',
        ]);

        BusinessMember::create([
            'business_id' => $business->id,
            'user_id' => $user->id,
            'role' => 'owner',
        ]);

        $staff = Staff::create([
            'business_id' => $business->id,
            'name' => 'Sarah',
            'is_active' => true,
        ]);

        $service = Service::create([
            'business_id' => $business->id,
            'name' => 'Manicure',
            'price' => 20,
            'duration_minutes' => 30,
            'is_active' => true,
        ]);

        $staff->services()->attach($service->id);

        BusinessWorkingHour::create([
            'business_id' => $business->id,
            'day_of_week' => 1,
            'open_time' => '09:00',
            'close_time' => '18:00',
            'is_closed' => false,
        ]);

        StaffWorkingHour::create([
            'staff_id' => $staff->id,
            'day_of_week' => 1,
            'open_time' => '10:00',
            'close_time' => '16:00',
            'is_closed' => false,
        ]);

        $createResponse = $this->postJson(
            "/api/businesses/{$business->id}/appointments",
            [
                'staff_id' => $staff->id,
                'service_id' => $service->id,
                'appointment_date' => '2026-09-21',
                'start_time' => '10:00',
                'end_time' => '10:30',
            ]
        );

        $createResponse->assertCreated();

        $appointmentId = $createResponse->json('appointment.id');

        $cancelResponse = $this->patchJson(
            "/api/appointments/{$appointmentId}/cancel"
        );

        $cancelResponse
            ->assertOk()
            ->assertJsonPath('appointment.status', 'cancelled');

        $this->assertDatabaseHas('appointments', [
            'id' => $appointmentId,
            'status' => 'cancelled',
        ]);
    }
    public function test_user_cannot_cancel_another_users_appointment(): void
    {
        $owner = User::factory()->create();
        $customer = User::factory()->create();

        Sanctum::actingAs($owner);

        $business = Business::create([
            'name' => 'Test Beauty Salon',
            'slug' => 'test-beauty-salon',
            'country' => 'DE',
            'timezone' => 'Europe/Berlin',
            'currency' => 'EUR',
        ]);

        BusinessMember::create([
            'business_id' => $business->id,
            'user_id' => $owner->id,
            'role' => 'owner',
        ]);

        $staff = Staff::create([
            'business_id' => $business->id,
            'name' => 'Sarah',
            'is_active' => true,
        ]);

        $service = Service::create([
            'business_id' => $business->id,
            'name' => 'Manicure',
            'price' => 20,
            'duration_minutes' => 30,
            'is_active' => true,
        ]);

        $staff->services()->attach($service->id);

        BusinessWorkingHour::create([
            'business_id' => $business->id,
            'day_of_week' => 1,
            'open_time' => '09:00',
            'close_time' => '18:00',
            'is_closed' => false,
        ]);

        StaffWorkingHour::create([
            'staff_id' => $staff->id,
            'day_of_week' => 1,
            'open_time' => '10:00',
            'close_time' => '16:00',
            'is_closed' => false,
        ]);

        Sanctum::actingAs($customer);

        $createResponse = $this->postJson(
            "/api/businesses/{$business->id}/appointments",
            [
                'staff_id' => $staff->id,
                'service_id' => $service->id,
                'appointment_date' => '2026-09-21',
                'start_time' => '10:00',
                'end_time' => '10:30',
            ]
        );

        $createResponse->assertCreated();

        $appointmentId = $createResponse->json('appointment.id');

        Sanctum::actingAs($owner);

        $cancelResponse = $this->patchJson(
            "/api/appointments/{$appointmentId}/cancel"
        );

        $cancelResponse->assertForbidden();

        $this->assertDatabaseHas('appointments', [
            'id' => $appointmentId,
            'user_id' => $customer->id,
            'status' => 'pending',
        ]);
    }
    public function test_booked_slot_is_not_available(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $business = Business::create([
            'name' => 'Test Beauty Salon',
            'slug' => 'test-beauty-salon',
            'country' => 'DE',
            'timezone' => 'Europe/Berlin',
            'currency' => 'EUR',
        ]);

        BusinessMember::create([
            'business_id' => $business->id,
            'user_id' => $user->id,
            'role' => 'owner',
        ]);

        $staff = Staff::create([
            'business_id' => $business->id,
            'name' => 'Sarah',
            'is_active' => true,
        ]);

        $service = Service::create([
            'business_id' => $business->id,
            'name' => 'Manicure',
            'price' => 20,
            'duration_minutes' => 30,
            'is_active' => true,
        ]);

        $staff->services()->attach($service->id);

        BusinessWorkingHour::create([
            'business_id' => $business->id,
            'day_of_week' => 1,
            'open_time' => '09:00',
            'close_time' => '18:00',
            'is_closed' => false,
        ]);

        StaffWorkingHour::create([
            'staff_id' => $staff->id,
            'day_of_week' => 1,
            'open_time' => '10:00',
            'close_time' => '16:00',
            'is_closed' => false,
        ]);

        $this->postJson(
            "/api/businesses/{$business->id}/appointments",
            [
                'staff_id' => $staff->id,
                'service_id' => $service->id,
                'appointment_date' => '2026-09-21',
                'start_time' => '10:00',
                'end_time' => '10:30',
            ]
        )->assertCreated();

        $response = $this->getJson(
            "/api/businesses/{$business->id}/availability"
            . "?staff_id={$staff->id}"
            . "&service_id={$service->id}"
            . "&date=2026-09-21"
        );

        $response->assertOk();

        $slots = $response->json('available_slots');

        $this->assertNotContains('10:00', $slots);
    }
    public function test_cancelled_appointment_slot_becomes_available(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $business = Business::create([
            'name' => 'Test Beauty Salon',
            'slug' => 'test-beauty-salon',
            'country' => 'DE',
            'timezone' => 'Europe/Berlin',
            'currency' => 'EUR',
        ]);

        BusinessMember::create([
            'business_id' => $business->id,
            'user_id' => $user->id,
            'role' => 'owner',
        ]);

        $staff = Staff::create([
            'business_id' => $business->id,
            'name' => 'Sarah',
            'is_active' => true,
        ]);

        $service = Service::create([
            'business_id' => $business->id,
            'name' => 'Manicure',
            'price' => 20,
            'duration_minutes' => 30,
            'is_active' => true,
        ]);

        $staff->services()->attach($service->id);

        BusinessWorkingHour::create([
            'business_id' => $business->id,
            'day_of_week' => 1,
            'open_time' => '09:00',
            'close_time' => '18:00',
            'is_closed' => false,
        ]);

        StaffWorkingHour::create([
            'staff_id' => $staff->id,
            'day_of_week' => 1,
            'open_time' => '10:00',
            'close_time' => '16:00',
            'is_closed' => false,
        ]);

        $createResponse = $this->postJson(
            "/api/businesses/{$business->id}/appointments",
            [
                'staff_id' => $staff->id,
                'service_id' => $service->id,
                'appointment_date' => '2026-09-21',
                'start_time' => '10:00',
                'end_time' => '10:30',
            ]
        );

        $createResponse->assertCreated();

        $appointmentId = $createResponse->json('appointment.id');

        $this->patchJson(
            "/api/appointments/{$appointmentId}/cancel"
        )->assertOk();

        $response = $this->getJson(
            "/api/businesses/{$business->id}/availability"
            . "?staff_id={$staff->id}"
            . "&service_id={$service->id}"
            . "&date=2026-09-21"
        );

        $response->assertOk();

        $slots = $response->json('available_slots');

        $this->assertTrue(collect($slots)->contains(fn ($slot) => $slot['start_time'] === '10:00'));
    }
    public function test_user_cannot_create_appointment_with_wrong_duration(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $business = Business::create([
            'name' => 'Test Beauty Salon',
            'slug' => 'test-beauty-salon',
            'country' => 'DE',
            'timezone' => 'Europe/Berlin',
            'currency' => 'EUR',
        ]);

        BusinessMember::create([
            'business_id' => $business->id,
            'user_id' => $user->id,
            'role' => 'owner',
        ]);

        $staff = Staff::create([
            'business_id' => $business->id,
            'name' => 'Sarah',
            'is_active' => true,
        ]);

        $service = Service::create([
            'business_id' => $business->id,
            'name' => 'Manicure',
            'price' => 20,
            'duration_minutes' => 30,
            'is_active' => true,
        ]);

        $staff->services()->attach($service->id);

        BusinessWorkingHour::create([
            'business_id' => $business->id,
            'day_of_week' => 1,
            'open_time' => '09:00',
            'close_time' => '18:00',
            'is_closed' => false,
        ]);

        StaffWorkingHour::create([
            'staff_id' => $staff->id,
            'day_of_week' => 1,
            'open_time' => '10:00',
            'close_time' => '16:00',
            'is_closed' => false,
        ]);

        $response = $this->postJson(
            "/api/businesses/{$business->id}/appointments",
            [
                'staff_id' => $staff->id,
                'service_id' => $service->id,
                'appointment_date' => '2026-09-21',
                'start_time' => '10:00',
                'end_time' => '10:45',
            ]
        );

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors('end_time');
    }
    public function test_user_can_list_their_appointments(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $business = Business::create([
            'name' => 'Test Beauty Salon',
            'slug' => 'test-beauty-salon',
            'country' => 'DE',
            'timezone' => 'Europe/Berlin',
            'currency' => 'EUR',
        ]);

        $staff = Staff::create([
            'business_id' => $business->id,
            'name' => 'Sarah',
            'is_active' => true,
        ]);

        $service = Service::create([
            'business_id' => $business->id,
            'name' => 'Manicure',
            'price' => 20,
            'duration_minutes' => 30,
            'is_active' => true,
        ]);

        $appointment = Appointment::create([
            'business_id' => $business->id,
            'staff_id' => $staff->id,
            'service_id' => $service->id,
            'user_id' => $user->id,
            'appointment_date' => '2026-09-21',
            'start_time' => '10:00',
            'end_time' => '10:30',
            'status' => 'pending',
            'payment_status' => 'pending',
            'price' => 20,
        ]);

        $response = $this->getJson('/api/appointments');

        $response
            ->assertOk()
            ->assertJsonPath('appointments.0.id', $appointment->id);
    }
    public function test_user_cannot_cancel_an_already_cancelled_appointment(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $business = Business::create([
            'name' => 'Test Beauty Salon',
            'slug' => 'test-beauty-salon',
            'country' => 'DE',
            'timezone' => 'Europe/Berlin',
            'currency' => 'EUR',
        ]);

        $staff = Staff::create([
            'business_id' => $business->id,
            'name' => 'Sarah',
            'is_active' => true,
        ]);

        $service = Service::create([
            'business_id' => $business->id,
            'name' => 'Manicure',
            'price' => 20,
            'duration_minutes' => 30,
            'is_active' => true,
        ]);

        $appointment = Appointment::create([
            'business_id' => $business->id,
            'staff_id' => $staff->id,
            'service_id' => $service->id,
            'user_id' => $user->id,
            'appointment_date' => '2026-09-21',
            'start_time' => '10:00',
            'end_time' => '10:30',
            'status' => 'cancelled',
            'payment_status' => 'pending',
            'price' => 20,
        ]);

        $response = $this->patchJson(
            "/api/appointments/{$appointment->id}/cancel"
        );

        $response->assertStatus(422);

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => 'cancelled',
        ]);
    }
    public function test_user_cannot_book_staff_or_service_from_another_business(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $businessA = Business::create([
            'name' => 'Salon A',
            'slug' => 'salon-a',
            'country' => 'DE',
            'timezone' => 'Europe/Berlin',
            'currency' => 'EUR',
        ]);

        $businessB = Business::create([
            'name' => 'Salon B',
            'slug' => 'salon-b',
            'country' => 'DE',
            'timezone' => 'Europe/Berlin',
            'currency' => 'EUR',
        ]);

        $staffB = Staff::create([
            'business_id' => $businessB->id,
            'name' => 'Sarah',
            'is_active' => true,
        ]);

        $serviceB = Service::create([
            'business_id' => $businessB->id,
            'name' => 'Manicure',
            'price' => 20,
            'duration_minutes' => 30,
            'is_active' => true,
        ]);

        $staffB->services()->attach($serviceB->id);

        $response = $this->postJson(
            "/api/businesses/{$businessA->id}/appointments",
            [
                'staff_id' => $staffB->id,
                'service_id' => $serviceB->id,
                'appointment_date' => '2026-09-21',
                'start_time' => '10:00',
                'end_time' => '10:30',
            ]
        );

        $response->assertStatus(404);
    }
        }

