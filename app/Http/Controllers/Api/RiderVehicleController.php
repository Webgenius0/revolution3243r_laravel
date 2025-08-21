<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RiderVehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RiderVehicleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $bike = auth('api')->user()->bike;

        if ($bike) {
            return response()->json([
                'status'  => true,
                'message' => 'Bike details retrieved successfully.',
                'code'    => 200,
                'data'    => $bike
            ], 200);
        }

        return response()->json([
            'status'  => false,
            'message' => 'No bike found for the authenticated user.',
            'code'    => 200,
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'engine_type'       => 'required|string',
            'engine_size'       => 'required|string',
            'tire_type'         => 'required|string',
            'model'             => 'required|string',
            'front_suspension'  => 'nullable|string',
            'rear_suspension'   => 'nullable|string',
            'front_sprocket'    => 'nullable|string',
            'rear_sprocket'     => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => $validator->errors()->first(),
                'code'    => 422,
            ], 422);
        }

        $vehicle = RiderVehicle::updateOrCreate(
            ['user_id' => auth('api')->id()], // Condition to check
            [
                'engine_type'       => $request->engine_type,
                'engine_size'       => $request->engine_size,
                'tire_type'         => $request->tire_type,
                'model'             => $request->model,
                'front_suspension'  => $request->front_suspension,
                'rear_suspension'   => $request->rear_suspension,
                'front_sprocket'    => $request->front_sprocket,
                'rear_sprocket'     => $request->rear_sprocket,
            ]
        );
        $vehicle = [
            'id'                => $vehicle->id,
            'user_id'           => $vehicle->user_id,
            'engine_type'       => $vehicle->engine_type,
            'engine_type'       => $vehicle->engine_type,
            'engine_size'       => $vehicle->engine_size,
            'tire_type'         => $vehicle->tire_type,
            'model'             => $vehicle->model,
            'front_suspension'  => $vehicle->front_suspension,
            'rear_suspension'   => $vehicle->rear_suspension,
            'front_sprocket'    => $vehicle->front_sprocket,
            'rear_sprocket'     => $vehicle->rear_sprocket,
        ];


        return response()->json([
            'status'  => true,
            'message' => 'Vehicle details saved successfully.',
            'code'    => 201,
            'data'    => $vehicle,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        $vehicle = RiderVehicle::where('user_id', auth('api')->id())
            ->first();

        if (!$vehicle) {
            return response()->json([
                'status'  => false,
                'message' => 'Vehicle not found.',
                'code'    => 404,
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'engine_type'       => 'nullable|string',
            'engine_size'       => 'nullable|string',
            'tire_type'         => 'nullable|string',
            'model'             => 'nullable|string',
            'front_suspension'  => 'nullable|string',
            'rear_suspension'   => 'nullable|string',
            'front_sprocket'    => 'nullable|string',
            'rear_sprocket'     => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => $validator->errors()->first(),
                'code'    => 422,
            ], 422);
        }

        $vehicle->engine_type      = $request->engine_type ?? $vehicle->engine_type;
        $vehicle->engine_size      = $request->engine_size ?? $vehicle->engine_size;
        $vehicle->tire_type        = $request->tire_type ?? $vehicle->tire_type;
        $vehicle->model            = $request->model ?? $vehicle->model;
        $vehicle->front_suspension = $request->front_suspension ?? $vehicle->front_suspension;
        $vehicle->rear_suspension  = $request->rear_suspension ?? $vehicle->rear_suspension;
        $vehicle->front_sprocket   = $request->front_sprocket ?? $vehicle->front_sprocket;
        $vehicle->rear_sprocket    = $request->rear_sprocket ?? $vehicle->rear_sprocket;

        $vehicle->save();

        return response()->json([
            'status'  => true,
            'message' => 'Vehicle details updated successfully.',
            'code'    => 200,
            'data'    => $vehicle,
        ], 200);
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
