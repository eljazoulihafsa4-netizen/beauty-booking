<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Business;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    private function authorizeManager(Request $request, Business $business)
    {
        $membership = $request->user()
            ->businessMemberships()
            ->where('business_id', $business->id)
            ->first();

        if (!$membership || !in_array($membership->role, ['owner', 'manager'])) {
            abort(403, 'You are not authorized to manage services for this business.');
        }

        return $membership;
    }

    public function index(Request $request, Business $business)
    {
        $this->authorizeManager($request, $business);

        return response()->json([
            'services' => $business->services()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(Request $request, Business $business)
    {
        $this->authorizeManager($request, $business);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'duration_minutes' => 'required|integer|min:5|max:1440',
            'is_active' => 'sometimes|boolean',
        ]);

        $service = $business->services()->create($validated);

        return response()->json([
            'message' => 'Service created successfully.',
            'service' => $service,
        ], 201);
    }

    public function update(Request $request, Business $business, $service)
    {
        $this->authorizeManager($request, $business);

        $service = $business->services()->findOrFail($service);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'sometimes|required|numeric|min:0',
            'duration_minutes' => 'sometimes|required|integer|min:5|max:1440',
            'is_active' => 'sometimes|boolean',
        ]);

        $service->update($validated);

        return response()->json([
            'message' => 'Service updated successfully.',
            'service' => $service->fresh(),
        ]);
    }

    public function destroy(Request $request, Business $business, $service)
    {
        $this->authorizeManager($request, $business);

        $service = $business->services()->findOrFail($service);

        $service->update([
            'is_active' => false,
        ]);

        return response()->json([
            'message' => 'Service deactivated successfully.',
        ]);
    }
}