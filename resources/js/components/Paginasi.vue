<script setup lang="ts">
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        halaman: {
            data: any[];
            links: { url: string | null; label: string; active: boolean }[];
            total: number;
            from: number | null;
            to: number | null;
        };
    }>(),
    {},
);

/** Rapikan label bawaan Laravel ("&laquo; Previous" → "‹"). */
const tautan = computed(() =>
    (props.halaman.links || []).map((l) => ({
        url: l.url,
        aktif: l.active,
        label: l.label
            .replace('&laquo; Previous', '‹')
            .replace('Next &raquo;', '›')
            .replace('&laquo;', '‹')
            .replace('&raquo;', '›'),
    })),
);
</script>

<template>
    <div
        v-if="halaman.total > 0"
        class="flex flex-wrap items-center justify-between gap-3 px-1 py-3"
    >
        <p class="dk-sub">
            {{ halaman.from }}–{{ halaman.to }} dari {{ halaman.total }}
        </p>
        <nav class="flex flex-wrap items-center gap-1">
            <template v-for="(t, i) in tautan" :key="i">
                <a
                    v-if="t.url"
                    :href="t.url"
                    class="dk-tbl dk-tbl-kosong"
                    :class="
                        t.aktif
                            ? '!border-blue-600 !bg-blue-600 !text-white'
                            : ''
                    "
                    >{{ t.label }}</a
                >
                <span v-else class="px-2 text-xs text-gray-400">{{
                    t.label
                }}</span>
            </template>
        </nav>
    </div>
</template>
