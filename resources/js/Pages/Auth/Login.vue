<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: Boolean,
    status: String,
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Sign in"/>
    <main class="min-h-screen bg-white lg:grid lg:grid-cols-[1.05fr_.95fr]">
        <section class="relative hidden overflow-hidden bg-[#5146e5] px-12 py-10 text-white lg:flex lg:flex-col lg:justify-between xl:px-20">
            <div class="absolute -right-24 -top-20 h-96 w-96 rounded-full border-[60px] border-white/10"></div>
            <div class="absolute -bottom-32 -left-16 h-80 w-80 rounded-full bg-white/5"></div>
            <Link href="/" class="relative flex w-fit items-center gap-3">
                <span class="grid h-11 w-11 place-items-center rounded-2xl bg-white text-xl font-bold text-indigo-600 shadow-lg">m</span>
                <span class="text-xl font-bold tracking-tight">meetly</span>
            </Link>
            <div class="relative max-w-xl py-12">
                <p class="text-sm font-semibold uppercase tracking-[.2em] text-indigo-200">Meet better, together</p>
                <h1 class="mt-5 text-5xl font-bold leading-tight xl:text-6xl">Good conversations start here.</h1>
                <p class="mt-6 max-w-md text-lg leading-8 text-indigo-100">Bring your team together with simple video meetings, helpful notes, and clear next steps.</p>
                <div class="mt-10 flex items-center gap-3 text-sm text-indigo-100"><span class="flex -space-x-2"><i class="h-8 w-8 rounded-full border-2 border-indigo-500 bg-amber-300"></i><i class="h-8 w-8 rounded-full border-2 border-indigo-500 bg-emerald-300"></i><i class="h-8 w-8 rounded-full border-2 border-indigo-500 bg-pink-300"></i></span><span>One link to bring everyone in</span></div>
            </div>
            <p class="relative text-xs text-indigo-200">© 2026 Meetly · Video calls made simple</p>
        </section>

        <section class="flex min-h-screen flex-col justify-center px-5 py-10 sm:px-10 lg:px-12 xl:px-24">
            <Link href="/" class="mx-auto mb-12 flex items-center gap-3 lg:hidden">
                <span class="grid h-10 w-10 place-items-center rounded-xl bg-indigo-600 text-lg font-bold text-white">m</span>
                <span class="text-xl font-bold tracking-tight text-slate-900">meetly</span>
            </Link>
            <div class="mx-auto w-full max-w-md">
                <p class="text-sm font-semibold text-indigo-600">WELCOME BACK</p>
                <h2 class="mt-2 text-3xl font-bold tracking-tight text-slate-900">Sign in to Meetly</h2>
                <p class="mt-2 text-sm text-slate-500">Enter your account details to continue to your workspace.</p>
                <div v-if="status" class="mt-6 rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ status }}</div>
                <form class="mt-8 space-y-5" @submit.prevent="submit">
                    <div><label for="email" class="mb-2 block text-sm font-semibold text-slate-700">Email address</label><input id="email" v-model="form.email" type="email" autocomplete="username" required autofocus placeholder="you@example.com" class="w-full rounded-xl border-slate-200 px-4 py-3.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"/><p v-if="form.errors.email" class="mt-2 text-sm text-rose-600">{{ form.errors.email }}</p></div>
                    <div><div class="mb-2 flex items-center justify-between"><label for="password" class="text-sm font-semibold text-slate-700">Password</label><Link v-if="canResetPassword" :href="route('password.request')" class="text-sm font-semibold text-indigo-600 hover:text-indigo-800">Forgot password?</Link></div><input id="password" v-model="form.password" type="password" autocomplete="current-password" required placeholder="Enter your password" class="w-full rounded-xl border-slate-200 px-4 py-3.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"/><p v-if="form.errors.password" class="mt-2 text-sm text-rose-600">{{ form.errors.password }}</p></div>
                    <label class="flex items-center gap-2.5 text-sm text-slate-600"><input v-model="form.remember" type="checkbox" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"/>Remember me</label>
                    <button :disabled="form.processing" class="flex w-full items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 py-3.5 text-sm font-bold text-white shadow-lg shadow-indigo-200 transition hover:bg-indigo-700 disabled:opacity-60"><span>{{ form.processing ? 'Signing in…' : 'Sign in' }}</span><span aria-hidden="true">→</span></button>
                </form>
                <p class="mt-7 text-center text-sm text-slate-500">New to Meetly? <Link :href="route('register')" class="font-semibold text-indigo-600 hover:text-indigo-800">Create an account</Link></p>
                <p class="mt-10 text-center text-xs leading-5 text-slate-400">By continuing, you agree to use meeting recordings and transcripts with the consent of everyone involved.</p>
            </div>
        </section>
    </main>
</template>

