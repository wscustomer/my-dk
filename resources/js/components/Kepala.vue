<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

defineProps<{
    judul: string;
    sub?: string;
    kembali?: string;
    kembaliTeks?: string;
}>();
</script>

<template>
    <header class="mb-4 flex flex-wrap items-end justify-between gap-3">
        <div class="min-w-0">
            <Link
                v-if="kembali"
                :href="kembali"
                class="dk-sub mb-1 inline-flex items-center gap-1 hover:text-blue-600"
                >‹ {{ kembaliTeks || 'Kembali' }}</Link
            >
            <h1 class="dk-judul truncate">{{ judul }}</h1>
            <p v-if="sub" class="dk-sub mt-0.5">{{ sub }}</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <slot name="aksi" />
        </div>
    </header>
</template>
