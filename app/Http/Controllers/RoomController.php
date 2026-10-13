<?php

namespace App\Http\Controllers;

use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RoomController extends Controller
{
    public function store(Request $request)
    {
        $room = Room::create([
            'room_name' => Str::uuid()->toString(),
            'created_by' => $request->user()?->name,
        ]);

        return response()->json([
            'message' => 'Room created successfully',
            'room' => $room,
        ], 201);
    }

    public function index()
    {
        return response()->json(
            Room::latest()->get()
        );
    }
}
