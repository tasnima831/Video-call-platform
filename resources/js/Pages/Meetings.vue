<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import WorkspaceSidebar from '@/Components/WorkspaceSidebar.vue';
import { Head } from '@inertiajs/vue3';
const rows = [
    ['Product design sync', 'Today · 10:30 AM', '4 participants', 'Upcoming', '✳', 'bg-violet-100 text-violet-700'],
    ['Weekly team check-in', 'Today · 2:00 PM', '8 participants', 'Upcoming', '◷', 'bg-sky-100 text-sky-700'],
    ['Project kickoff', 'Yesterday · 11:00 AM', '6 participants', 'Ended', '↗', 'bg-amber-100 text-amber-700'],
    ['Design review', 'Oct 10 · 3:30 PM', '5 participants', 'Ended', '✎', 'bg-emerald-100 text-emerald-700'],
];
</script>
<template>
<Head title="Meetings"/><AuthenticatedLayout><div class="min-h-screen bg-[#f6f7fb]"><div class="mx-auto grid max-w-[1440px] gap-6 px-4 py-6 sm:px-6 lg:grid-cols-[230px_minmax(0,1fr)] lg:gap-8 lg:px-10 lg:py-9"><WorkspaceSidebar/><main class="min-w-0 space-y-6">
<header class="flex flex-wrap items-end justify-between gap-4"><div><p class="text-sm text-slate-400">Your workspace</p><h1 class="mt-1 text-3xl font-bold text-slate-900">Meetings</h1><p class="mt-2 text-sm text-slate-500">Manage upcoming calls and revisit your meeting history.</p></div><button class="rounded-xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white">＋ New meeting</button></header>
<section class="grid gap-4 sm:grid-cols-3"><div v-for="stat in [['Upcoming', '2'], ['Meetings this month', '12'], ['Total call time', '8h 24m']]" :key="stat[0]" class="rounded-2xl border border-slate-200 bg-white p-5"><p class="text-sm text-slate-500">{{stat[0]}}</p><p class="mt-2 text-2xl font-bold text-slate-900">{{stat[1]}}</p></div></section>
<section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"><div class="border-b border-slate-100 px-5 py-4"><h2 class="font-bold text-slate-900">Recent meetings</h2><p class="mt-1 text-xs text-slate-500">Your upcoming calls and meeting history</p></div><div v-for="(row,i) in rows" :key="row[0]" class="flex items-center gap-4 px-5 py-4" :class="{'border-t border-slate-100':i}"><span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl text-lg" :class="row[5]">{{row[4]}}</span><div class="min-w-0 flex-1"><h3 class="truncate text-sm font-semibold text-slate-800">{{row[0]}}</h3><p class="mt-1 text-xs text-slate-400">{{row[1]}} · {{row[2]}}</p></div><span class="hidden rounded-full px-3 py-1 text-xs sm:inline-flex" :class="row[3]==='Upcoming'?'bg-indigo-50 text-indigo-700':'bg-slate-100 text-slate-500'">{{row[3]}}</span><button class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-600">Details</button></div><p class="border-t border-slate-100 bg-slate-50 px-5 py-3 text-xs text-slate-400">Sample data · Laravel/MySQL integration is still needed for your real history.</p></section>
</main></div></div></AuthenticatedLayout></template>
