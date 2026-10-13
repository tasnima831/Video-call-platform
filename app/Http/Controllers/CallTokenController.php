<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Agence104\LiveKit\AccessToken;
use Agence104\LiveKit\AccessTokenOptions;
use Agence104\LiveKit\VideoGrant;

class CallTokenController extends Controller
{
    public function generate(Request $request)
    {
        $data = $request->validate([
            'room_name' => 'required|string|max:100',
            'participant_name' => 'required|string|max:100',
        ]);

        $options = (new AccessTokenOptions())
            ->setIdentity($data['participant_name']);

        $grant = (new VideoGrant())
            ->setRoomJoin()
            ->setRoomName($data['room_name']);

        $token = (new AccessToken(
            config('services.livekit.api_key'),
            config('services.livekit.api_secret')
        ))
            ->init($options)
            ->setGrant($grant)
            ->toJwt();

        return response()->json([
            'server_url' => config('services.livekit.url'),
            'token' => $token,
        ]);
    }
}
