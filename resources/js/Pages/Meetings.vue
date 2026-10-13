<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import WorkspaceSidebar from '@/Components/WorkspaceSidebar.vue';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({ meetings: { type: Array, default: () => [] } });
const startMeeting = () => router.post(route('meetings.store'), {});
const dateLabel = value => new Date(value).toLocaleString([], { dateStyle: 'medium', timeStyle: 'short' });
</script>
<template>
<Head title="Meetings"/><AuthenticatedLayout><div class="min-h-screen bg-[#f6f7fb]"><div class="mx-auto grid max-w-[1440px] gap-6 px-4 py-6 sm:px-6 lg:grid-cols-[230px_minmax(0,1fr)] lg:gap-8 lg:px-10 lg:py-9"><WorkspaceSidebar/><main class="min-w-0 space-y-6">
<header class="flex flex-wrap items-end justify-between gap-4"><div><p class="text-sm text-slate-400">Your workspace</p><h1 class="mt-1 text-3xl font-bold text-slate-900">Meetings</h1><p class="mt-2 text-sm text-slate-500">Your saved calls and meeting history.</p></div><button @click="startMeeting" class="rounded-xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white">＋ New meeting</button></header>
<section class="grid gap-4 sm:grid-cols-3"><div v-for="stat in [['Total meetings', meetings.length], ['Active now', meetings.filter(m=>m.status==='active').length], ['Ended', meetings.filter(m=>m.status==='ended').length]]" :key="stat[0]" class="rounded-2xl border border-slate-200 bg-white p-5"><p class="text-sm text-slate-500">{{stat[0]}}</p><p class="mt-2 text-2xl font-bold text-slate-900">{{stat[1]}}</p></div></section>
<section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"><div class="border-b border-slate-100 px-5 py-4"><h2 class="font-bold text-slate-900">Meeting history</h2><p class="mt-1 text-xs text-slate-500">Stored in your Meetly database</p></div><div v-if="!meetings.length" class="px-5 py-14 text-center"><p class="font-semibold text-slate-700">No meetings yet</p><p class="mt-1 text-sm text-slate-500">Create your first room and share its invitation link.</p></div><div v-for="(meeting,i) in meetings" :key="meeting.code" class="flex items-center gap-4 px-5 py-4" :class="{'border-t border-slate-100':i}"><span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-indigo-50 text-lg text-indigo-600">◷</span><div class="min-w-0 flex-1"><h3 class="truncate text-sm font-semibold text-slate-800">{{meeting.title}}</h3><p class="mt-1 text-xs text-slate-400">{{dateLabel(meeting.date)}} · {{meeting.participants}} participants · {{meeting.code}}</p></div><span class="rounded-full px-3 py-1 text-xs" :class="meeting.status==='active'?'bg-emerald-50 text-emerald-700':'bg-slate-100 text-slate-500'">{{meeting.status}}</span><Link v-if="meeting.status!=='ended'" :href="meeting.url" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-600">Open</Link></div></section>
</main></div></div></AuthenticatedLayout></template>
