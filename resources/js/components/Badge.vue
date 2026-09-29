<script setup lang="ts">
defineProps<{
    teks: string;
    warna?: string;
    latar?: boolean;
}>();
</script>

<template>
    <span
        class="dk-badge"
        :style="
            latar === false
                ? { color: warna || '#6b7280', padding: '0 .25rem' }
                : {
                      color: warna || '#6b7280',
                      background: (warna || '#6b7280') + '1a',
                  }
        "
        >{{ teks }}</span
    >
</template>
