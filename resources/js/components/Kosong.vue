<script setup lang="ts">
defineProps<{
    teks?: string;
    ikon?: string;
    aksi?: string;
}>();

const emit = defineEmits<{ klik: [] }>();
</script>

<template>
    <div class="dk-kosong">
        <div class="mb-1 text-2xl opacity-40">{{ ikon || '○' }}</div>
        <p>{{ teks || 'Belum ada data.' }}</p>
        <button
            v-if="aksi"
            type="button"
            class="dk-tbl dk-tbl-kosong mt-3"
            @click="emit('klik')"
        >
            {{ aksi }}
        </button>
    </div>
</template>
