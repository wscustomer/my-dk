<script setup lang="ts">
import { ref } from 'vue';

const props = withDefaults(
    defineProps<{
        label: string;
        galat?: string;
        petunjuk?: string;
        wajib?: boolean;
    }>(),
    {},
);
</script>

<template>
    <label class="block">
        <span class="dk-label">
            {{ label }}<span v-if="wajib" class="text-red-500"> *</span>
        </span>
        <slot />
        <span v-if="galat" class="dk-galat">{{ galat }}</span>
        <span v-else-if="petunjuk" class="mt-1 block text-xs text-gray-400">{{
            petunjuk
        }}</span>
    </label>
</template>
