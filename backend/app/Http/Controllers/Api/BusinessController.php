<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Business;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class BusinessController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'country' => 'nullable|string|size:2',
            'timezone' => 'nullable|string|max:100',
            'currency' => 'nullable|string|size:3',
        ]);

        $business = DB::transaction(function () use ($validated, $request) {
            $business = Business::create([
                'name' => $validated['name'],
                'slug' => Str::slug($validated['name']) . '-' . Str::lower(Str::random(6)),
                'email' => $validated['email'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'address' => $validated['address'] ?? null,
                'city' => $validated['city'] ?? null,
                'country' => $validated['country'] ?? 'DE',
                'timezone' => $validated['timezone'] ?? 'Europe/Berlin',
                'currency' => $validated['currency'] ?? 'EUR',
            ]);

            $business->members()->create([
                'user_id' => $request->user()->id,
                'role' => 'owner',
            ]);

            return $business;
        });

        return response()->json([
            'message' => 'Business created successfully.',
            'business' => $business,
        ], 201);
    }
}