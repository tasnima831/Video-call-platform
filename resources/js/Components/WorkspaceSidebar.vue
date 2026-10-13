<script setup>
import { Link } from '@inertiajs/vue3';

const items = [
    { label: 'Overview', icon: '▦', route: 'dashboard' },
    { label: 'Meetings', icon: '◷', route: 'meetings.index' },
    { label: 'My notes', icon: '✎', route: 'notes.index' },
    { label: 'AI summaries', icon: '✧', route: 'summaries.index' },
    { label: 'Plans & usage', icon: '$', route: 'plans.index' },
];
</script>

<template>
    <aside class="lg:sticky lg:top-6 lg:self-start">
        <Link :href="route('dashboard')" class="hidden items-center gap-3 px-3 pb-8 pt-1 lg:flex">
            <span class="grid h-10 w-10 place-items-center rounded-2xl bg-indigo-600 text-lg font-bold text-white shadow-lg shadow-indigo-200">m</span>
            <span class="text-xl font-bold tracking-tight text-slate-900">meetly</span>
        </Link>
        <p class="hidden px-3 pb-3 text-[11px] font-bold uppercase tracking-[.16em] text-slate-400 lg:block">Workspace</p>
        <nav class="flex gap-1 overflow-x-auto rounded-2xl border border-slate-200 bg-white p-2 lg:flex-col lg:gap-1 lg:border-0 lg:bg-transparent lg:p-0">
            <Link v-for="item in items" :key="item.route" :href="route(item.route)"
                class="flex shrink-0 items-center gap-3 rounded-xl px-3 py-3 text-sm transition"
                :class="route().current(item.route) ? 'bg-indigo-50 font-semibold text-indigo-700' : 'font-medium text-slate-500 hover:bg-white'">
                <span class="text-lg">{{ item.icon }}</span>{{ item.label }}
            </Link>
        </nav>
        <div class="mt-6 hidden rounded-2xl border border-slate-200 bg-white p-4 lg:block">
            <div class="flex items-center justify-between"><span class="text-sm font-semibold text-slate-800">Free plan</span><span class="rounded-full bg-emerald-50 px-2 py-1 text-[10px] font-bold text-emerald-700">ACTIVE</span></div>
            <p class="mt-2 text-xs leading-5 text-slate-500">Your workspace is ready for calls.</p>
            <div class="mt-4 h-1.5 overflow-hidden rounded-full bg-slate-100"><div class="h-full w-[34%] rounded-full bg-indigo-500"></div></div>
            <p class="mt-2 text-[11px] text-slate-400">Usage details coming soon</p>
        </div>
    </aside>
</template>

