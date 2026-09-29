<script setup lang="ts">
/**
 * Modal sederhana: tutup lewat Escape / klik latar. Tanpa fokus-terkunci penuh.
 * ponytail: aksesibilitas dasar saja (role=dialog + Escape). Ganti ke headless UI kalau perlu fokus-terkunci.
 */
const props = withDefaults(
    defineProps<{
        buka: boolean;
        judul?: string;
        lebar?: 'sm' | 'md' | 'lg' | 'xl';
        tutupKlikLatar?: boolean;
    }>(),
    { lebar: 'md', tutupKlikLatar: true },
);

const emit = defineEmits<{ tutup: [] }>();

const lebarKelas = {
    sm: 'max-w-md',
    md: 'max-w-xl',
    lg: 'max-w-3xl',
    xl: 'max-w-5xl',
} as const;

function kunci(e: KeyboardEvent) {
    if (e.key === 'Escape' && props.buka) emit('tutup');
}
</script>

<template>
    <Teleport to="body">
        <div
            v-if="buka"
            class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black/40 p-4 sm:p-8"
            role="dialog"
            aria-modal="true"
            @click.self="tutupKlikLatar && emit('tutup')"
            @keydown="kunci"
            tabindex="-1"
            ref="panel"
            v-focus
        >
            <div
                class="dk-tutup w-full rounded-xl bg-white shadow-xl"
                :class="lebarKelas[lebar]"
            >
                <header
                    class="flex items-center justify-between gap-4 border-b border-gray-100 px-5 py-3"
                >
                    <h2 class="text-sm font-semibold text-gray-900">
                        {{ judul }}
                    </h2>
                    <button
                        type="button"
                        class="dk-tbl dk-tbl-halus"
                        aria-label="Tutup"
                        @click="emit('tutup')"
                    >
                        ✕
                    </button>
                </header>
                <div class="px-5 py-4">
                    <slot />
                </div>
                <footer
                    v-if="$slots.kaki"
                    class="flex flex-wrap items-center justify-end gap-2 border-t border-gray-100 px-5 py-3"
                >
                    <slot name="kaki" />
                </footer>
            </div>
        </div>
    </Teleport>
</template>
