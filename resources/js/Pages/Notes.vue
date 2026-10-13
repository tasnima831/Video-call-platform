<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import WorkspaceSidebar from '@/Components/WorkspaceSidebar.vue';
import { Head } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props=defineProps({initialNote:{type:String,default:''}});
const notes=ref(props.initialNote);
const saved=ref(notes.value?'Saved to your account':'Nothing saved yet');
const error=ref('');
const words=computed(()=>notes.value.trim()?notes.value.trim().split(/\s+/).length:0);
let timer;
watch(notes,value=>{saved.value='Saving…';error.value='';clearTimeout(timer);timer=setTimeout(async()=>{try{await window.axios.put(route('notes.save'),{content:value});saved.value='Saved to your account';}catch(e){saved.value='Could not save';error.value=e.response?.data?.message||'Check your connection and try again.';}},500);});
const clearNotes=()=>{notes.value='';};
</script>
<template>
<Head title="My notes"/><AuthenticatedLayout><div class="min-h-screen bg-[#f6f7fb]"><div class="mx-auto grid max-w-[1440px] gap-6 px-4 py-6 sm:px-6 lg:grid-cols-[230px_minmax(0,1fr)] lg:gap-8 lg:px-10 lg:py-9"><WorkspaceSidebar/><main class="min-w-0 space-y-6">
<header class="flex flex-wrap items-end justify-between gap-4"><div><p class="text-sm text-slate-400">Personal workspace</p><h1 class="mt-1 text-3xl font-bold text-slate-900">My notes</h1><p class="mt-2 text-sm text-slate-500">Private notes, autosaved to your account.</p></div><button @click="clearNotes" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-600 hover:text-rose-600">Clear notes</button></header>
<div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_300px]"><section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"><div class="flex items-center justify-between border-b border-slate-100 px-5 py-4"><div><h2 class="font-bold text-slate-900">Personal notes</h2><p class="mt-1 text-xs text-slate-500">Saved securely in MySQL</p></div><span class="rounded-full bg-emerald-50 px-3 py-1 text-xs text-emerald-700">Private</span></div><textarea v-model="notes" rows="18" placeholder="Write notes and action items here…" class="w-full resize-y border-0 px-5 py-5 text-sm leading-7 text-slate-700 placeholder:text-slate-300 focus:ring-0"></textarea><div class="flex justify-between border-t border-slate-100 bg-slate-50 px-5 py-3 text-xs text-slate-400"><span>{{saved}}</span><span>{{words}} words</span></div></section>
<aside class="space-y-4"><section class="rounded-2xl border border-indigo-100 bg-indigo-50 p-5"><span class="text-xl text-indigo-600">✧</span><h3 class="mt-3 font-bold text-indigo-950">Meeting notes</h3><p class="mt-2 text-sm leading-6 text-indigo-800/80">These notes belong to your account and follow you across devices.</p></section><section class="rounded-2xl border border-slate-200 bg-white p-5"><h3 class="font-semibold text-slate-800">Notes history</h3><p class="mt-2 text-sm leading-6 text-slate-500">Shared meeting notes can be linked to rooms as collaboration is enabled.</p></section></aside></div><p v-if="error" class="text-sm text-rose-600">{{error}}</p>
</main></div></div></AuthenticatedLayout></template>
