<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="min-h-screen bg-[#f6f7fb] px-4 py-10 sm:flex sm:items-center sm:justify-center">
        <div class="w-full sm:max-w-md">
            <Link href="/" class="mb-7 flex items-center justify-center gap-3">
                <span class="grid h-11 w-11 place-items-center rounded-2xl bg-indigo-600 text-lg font-bold text-white shadow-md shadow-indigo-200">m</span>
                <span class="text-xl font-bold tracking-tight text-slate-900">meetly</span>
            </Link>
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white px-6 py-6 shadow-sm sm:px-8">
                <slot />
            </div>
        </div>
    </div>
</template>

