
<template>
  <div>
    <h2>Video Call</h2>

    <video
      id="local-video"
      autoplay
      muted
      playsinline
    ></video>

    <div id="remote-videos"></div>

    <button @click="leaveCall">Leave Call</button>
  </div>
</template>

<script setup>
import { leaveCall } from '@/services/videoService'
</script>
