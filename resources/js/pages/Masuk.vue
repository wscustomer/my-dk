<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({ email: '', password: '', ingat: false });

function kirim() {
    form.post('/masuk', { onFinish: () => form.reset('password') });
}
</script>

<template>
    <Head title="Masuk" />
    <div class="flex min-h-screen items-center justify-center bg-gray-50 px-4">
        <div class="w-full max-w-sm">
            <div class="mb-6 text-center">
                <span
                    class="mx-auto mb-3 flex h-10 w-10 items-center justify-center rounded-lg bg-blue-600 text-sm font-bold text-white"
                    >DK</span
                >
                <h1 class="text-lg font-semibold text-gray-900">
                    CRM Digital Konsultan
                </h1>
                <p class="dk-sub mt-0.5">
                    Masuk untuk mengelola klien & proyek
                </p>
            </div>

            <form class="dk-kartu dk-kartu-p space-y-3" @submit.prevent="kirim">
                <label class="block">
                    <span class="dk-label">Email</span>
                    <input
                        v-model="form.email"
                        type="email"
                        class="dk-isian"
                        autocomplete="username"
                        :aria-invalid="!!form.errors.email"
                        autofocus
                    />
                    <span v-if="form.errors.email" class="dk-galat">{{
                        form.errors.email
                    }}</span>
                </label>

                <label class="block">
                    <span class="dk-label">Kata sandi</span>
                    <input
                        v-model="form.password"
                        type="password"
                        class="dk-isian"
                        autocomplete="current-password"
                    />
                    <span v-if="form.errors.password" class="dk-galat">{{
                        form.errors.password
                    }}</span>
                </label>

                <label class="flex items-center gap-2 text-sm text-gray-600">
                    <input v-model="form.ingat" type="checkbox" />
                    Ingat saya
                </label>

                <button
                    type="submit"
                    class="dk-tbl dk-tbl-utama w-full justify-center"
                    :disabled="form.processing"
                >
                    {{ form.processing ? 'Memproses…' : 'Masuk' }}
                </button>
            </form>
        </div>
    </div>
</template>
