<?php

namespace App\Services;

use Agence104\LiveKit\AccessToken;
use Agence104\LiveKit\AccessTokenOptions;
use Agence104\LiveKit\VideoGrant;
use App\Models\Meeting;
use App\Models\MeetingParticipant;
use RuntimeException;

class LiveKitTokenService
{
    public function create(Meeting $meeting, MeetingParticipant $participant, int $ttlSeconds): array
    {
        $apiKey = config('services.livekit.api_key');
        $apiSecret = config('services.livekit.api_secret');
        $serverUrl = config('services.livekit.url');

        if (!$apiKey || !$apiSecret || !$serverUrl) {
            throw new RuntimeException('LiveKit is not configured. Add LIVEKIT_URL, LIVEKIT_API_KEY and LIVEKIT_API_SECRET.');
        }

        $options = (new AccessTokenOptions())
            ->setIdentity($participant->identity)
            ->setName($participant->display_name)
            ->setTtl(max(1, min($ttlSeconds, 7200)));

        $grant = (new VideoGrant())
            ->setRoomJoin()
            ->setRoomName($meeting->livekit_room)
            ->setCanPublish()
            ->setCanSubscribe()
            ->setCanPublishData();

        if ($participant->role === 'host') {
            $grant->setRoomAdmin();
        }

        $token = (new AccessToken($apiKey, $apiSecret))
            ->init($options)
            ->setGrant($grant)
            ->toJwt();

        return ['server_url' => $serverUrl, 'token' => $token];
    }
}

