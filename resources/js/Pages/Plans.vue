<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import WorkspaceSidebar from '@/Components/WorkspaceSidebar.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props=defineProps({plans:{type:Array,default:()=>[]},currentPlan:{type:String,default:'free'}});
const form=useForm({plan:props.currentPlan});
const choose=plan=>{form.plan=plan;form.post(route('plans.choose'));};
</script>
<template>
<Head title="Plans"/><AuthenticatedLayout><div class="min-h-screen bg-[#f6f7fb]"><div class="mx-auto grid max-w-[1440px] gap-6 px-4 py-6 sm:px-6 lg:grid-cols-[230px_minmax(0,1fr)] lg:gap-8 lg:px-10 lg:py-9"><WorkspaceSidebar/><main class="min-w-0 space-y-7">
<header><p class="text-sm text-slate-400">Meetly for teams</p><h1 class="mt-1 text-3xl font-bold text-slate-900">Plans & usage</h1><p class="mt-2 text-sm text-slate-500">Your plan is stored and enforced by Laravel.</p></header>
<p v-if="$page.props.flash?.status" class="rounded-xl bg-emerald-50 p-4 text-sm text-emerald-700">{{$page.props.flash.status}}</p>
<div class="grid gap-5 lg:grid-cols-2"><article v-for="plan in plans" :key="plan.slug" class="rounded-3xl bg-white p-7 shadow-sm" :class="plan.slug===currentPlan?'border-2 border-indigo-500':'border border-slate-200'"><p class="text-sm font-semibold text-indigo-600">{{plan.slug==='free'?'START HERE':'FOR GROWING TEAMS'}}</p><h2 class="mt-3 text-2xl font-bold text-slate-900">{{plan.name}}</h2><p class="mt-2 text-4xl font-bold text-slate-900">{{plan.monthly_price_cents?'$'+(plan.monthly_price_cents/100).toFixed(2):'Free'}}<span class="text-sm font-medium text-slate-400"> / month</span></p><ul class="mt-6 space-y-3 text-sm text-slate-600"><li>✓ {{plan.max_duration_minutes}} minute meeting limit</li><li>✓ Up to {{plan.max_participants}} participants</li><li>✓ {{plan.monthly_recording_minutes}} recording minutes/month</li><li>✓ {{plan.monthly_ai_summaries}} AI summaries/month</li></ul><button @click="choose(plan.slug)" :disabled="form.processing||plan.slug===currentPlan" class="mt-7 w-full rounded-xl px-4 py-3 text-sm font-semibold" :class="plan.slug===currentPlan?'bg-emerald-50 text-emerald-700':'bg-indigo-600 text-white hover:bg-indigo-700'">{{plan.slug===currentPlan?'Current plan':'Select '+plan.name}}</button></article></div>
<p class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm leading-6 text-amber-900">Premium selection is simulated for development. Payment references must be verified by a payment provider before production subscriptions.</p>
</main></div></div></AuthenticatedLayout></template>
