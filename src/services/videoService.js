
import axios from 'axios'
import { Room, RoomEvent } from 'livekit-client'

let room

export async function joinCall(roomName, participantName) {
    // Ask Laravel for a participant token
    const response = await axios.post(
        '/api/call-token',
        {
            room_name: roomName,
            participant_name: participantName
        }
    )

    const { server_url, token } = response.data

    // Connect to LiveKit
    room = new Room()

    room.on(RoomEvent.TrackSubscribed, (track, publication, participant) => {
        console.log('Track received from:', participant.identity)
        // Attach the received track to your Vue video/audio elements
    })

    await room.connect(server_url, token)

    // Enable microphone and camera
    await room.localParticipant.setMicrophoneEnabled(true)
    await room.localParticipant.setCameraEnabled(true)

    return room
}
