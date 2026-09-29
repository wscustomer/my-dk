<script setup lang="ts">
defineProps<{
    judul: string;
    nilai: string | number;
    sub?: string;
    warna?: string;
    ikon?: string;
}>();
</script>

<template>
    <div class="dk-kartu dk-kartu-p">
        <div class="flex items-start justify-between gap-2">
            <p class="dk-sub">{{ judul }}</p>
            <span v-if="ikon" class="opacity-40">{{ ikon }}</span>
        </div>
        <p
            class="mt-1 text-xl font-semibold tabular-nums"
            :style="{ color: warna || '#111827' }"
        >
            {{ nilai }}
        </p>
        <p v-if="sub" class="dk-sub mt-0.5">{{ sub }}</p>
    </div>
</template>
