<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Business;
use Illuminate\Http\Request;

class StaffController extends Controller
{
    public function index(Request $request, Business $business)
    {
        $membership = $request->user()
            ->businessMemberships()
            ->where('business_id', $business->id)
            ->first();

        if (!$membership || !in_array($membership->role, ['owner', 'manager'])) {
            return response()->json([
                'message' => 'You are not authorized to view staff for this business.',
            ], 403);
        }

        return response()->json([
            'staff' => $business->staff()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
        ]);
    }
    public function store(Request $request, Business $business)
    {
        $membership = $request->user()
            ->businessMemberships()
            ->where('business_id', $business->id)
            ->first();

        if (!$membership || !in_array($membership->role, ['owner', 'manager'])) {
            return response()->json([
                'message' => 'You are not authorized to manage staff for this business.',
            ], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'job_title' => 'nullable|string|max:100',
            'bio' => 'nullable|string',
            'avatar' => 'nullable|string|max:500',
            'is_active' => 'sometimes|boolean',
        ]);

        $staff = $business->staff()->create($validated);

        return response()->json([
            'message' => 'Staff member created successfully.',
            'staff' => $staff,
        ], 201);
    }
    public function update(Request $request, Business $business, $staff)
    {
        $membership = $request->user()
            ->businessMemberships()
            ->where('business_id', $business->id)
            ->first();

        if (!$membership || !in_array($membership->role, ['owner', 'manager'])) {
            return response()->json([
                'message' => 'You are not authorized to manage staff for this business.',
            ], 403);
        }

        $staffMember = $business->staff()->findOrFail($staff);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'job_title' => 'nullable|string|max:100',
            'bio' => 'nullable|string',
            'avatar' => 'nullable|string|max:500',
            'is_active' => 'sometimes|boolean',
        ]);

        $staffMember->update($validated);

        return response()->json([
            'message' => 'Staff member updated successfully.',
            'staff' => $staffMember->fresh(),
        ]);
    }
    public function destroy(Request $request, Business $business, $staff)
    {
        $membership = $request->user()
            ->businessMemberships()
            ->where('business_id', $business->id)
            ->first();

        if (!$membership || !in_array($membership->role, ['owner', 'manager'])) {
            return response()->json([
                'message' => 'You are not authorized to manage staff for this business.',
            ], 403);
        }

        $staffMember = $business->staff()->findOrFail($staff);

        $staffMember->update([
            'is_active' => false,
        ]);

        return response()->json([
            'message' => 'Staff member deactivated successfully.',
        ]);
    }
}