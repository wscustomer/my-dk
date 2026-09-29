<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { rute } from '@/lib/rute';

const page = usePage<any>();
const pengguna = computed(() => page.props.auth?.user);
const merek = computed(() => page.props.merek || 'Digital Konsultan');
const menu = computed(() =>
    (page.props.menu || []).filter(
        (m: any) => !m.admin || page.props.auth?.admin,
    ),
);
const flash = computed(() => page.props.flash || {});

const halamanKini = computed(() => page.url.split('?')[0]);
const terbuka = ref(false);
const pesanTutup = ref(true);

watch(
    () => [flash.value.ok, flash.value.galat],
    () => {
        pesanTutup.value = false;
    },
);

function aktif(cocok: string): boolean {
    const dasar = cocok.replace('*', '');
    if (cocok === 'dasbor') return halamanKini.value === '/';

    return halamanKini.value.startsWith('/' + dasar);
}

function keluarkan() {
    router.post('/keluar');
}

const inisial = computed(() => {
    const nama: string = pengguna.value?.name || '?';

    return nama
        .split(/\s+/)
        .slice(0, 2)
        .map((k: string) => k[0]?.toUpperCase() || '')
        .join('');
});
</script>

<template>
    <div class="min-h-screen bg-gray-50">
        <div class="mx-auto flex max-w-[110rem] gap-0">
            <!-- Sidebar -->
            <aside
                class="sticky top-0 hidden h-screen w-56 shrink-0 flex-col border-r border-gray-200 bg-white px-3 py-4 md:flex"
            >
                <Link href="/" class="mb-5 flex items-center gap-2 px-2">
                    <span
                        class="flex h-7 w-7 items-center justify-center rounded-[4px] bg-blue-600 text-xs font-bold text-white"
                        >DK</span
                    >
                    <span class="text-sm font-semibold text-gray-900">{{
                        merek
                    }}</span>
                </Link>

                <nav class="flex flex-1 flex-col gap-0.5">
                    <Link
                        v-for="m in menu"
                        :key="m.rute"
                        :href="
                            m.rute === 'dasbor'
                                ? '/'
                                : '/' + m.cocok.replace('*', '')
                        "
                        class="flex items-center gap-2.5 rounded-[4px] px-2.5 py-2 text-sm transition"
                        :class="
                            aktif(m.cocok)
                                ? 'bg-blue-50 font-medium text-blue-700'
                                : 'text-gray-600 hover:bg-gray-50'
                        "
                    >
                        <span class="w-4 text-center opacity-70">{{
                            m.ikon
                        }}</span>
                        {{ m.label }}
                    </Link>
                </nav>

                <div class="mt-3 border-t border-gray-100 pt-3">
                    <div class="flex items-center gap-2 px-1">
                        <span
                            class="flex h-7 w-7 items-center justify-center rounded-full bg-gray-200 text-xs font-medium text-gray-700"
                            >{{ inisial }}</span
                        >
                        <div class="min-w-0 flex-1">
                            <p
                                class="truncate text-xs font-medium text-gray-800"
                            >
                                {{ pengguna?.name }}
                            </p>
                            <p class="truncate text-[11px] text-gray-500">
                                {{ pengguna?.label_peran }}
                            </p>
                        </div>
                        <button
                            type="button"
                            class="dk-tbl dk-tbl-halus"
                            title="Keluar"
                            @click="keluarkan"
                        >
                            ⏻
                        </button>
                    </div>
                </div>
            </aside>

            <!-- Isi -->
            <div class="min-w-0 flex-1">
                <!-- Bar atas (mobile) -->
                <header
                    class="sticky top-0 z-30 flex items-center justify-between border-b border-gray-200 bg-white px-4 py-2.5 md:hidden"
                >
                    <button
                        type="button"
                        class="dk-tbl dk-tbl-halus"
                        @click="terbuka = !terbuka"
                    >
                        ☰
                    </button>
                    <span class="text-sm font-semibold">{{ merek }}</span>
                    <button
                        type="button"
                        class="dk-tbl dk-tbl-halus"
                        @click="keluarkan"
                    >
                        ⏻
                    </button>
                </header>

                <nav
                    v-if="terbuka"
                    class="flex flex-wrap gap-1 border-b border-gray-200 bg-white px-3 py-2 md:hidden"
                >
                    <Link
                        v-for="m in menu"
                        :key="m.rute"
                        :href="
                            m.rute === 'dasbor'
                                ? '/'
                                : '/' + m.cocok.replace('*', '')
                        "
                        class="dk-tbl"
                        :class="
                            aktif(m.cocok) ? 'dk-tbl-utama' : 'dk-tbl-kosong'
                        "
                        @click="terbuka = false"
                        >{{ m.label }}</Link
                    >
                </nav>

                <!-- Pesan -->
                <div v-if="flash.ok || flash.galat" class="px-4 pt-4 sm:px-6">
                    <div
                        class="dk-tutup flex items-center justify-between gap-3 rounded-[4px] px-3 py-2 text-sm"
                        :class="
                            flash.galat
                                ? 'bg-red-50 text-red-800'
                                : 'bg-green-50 text-green-800'
                        "
                    >
                        <span>{{ flash.galat || flash.ok }}</span>
                        <button
                            type="button"
                            class="dk-tbl dk-tbl-halus"
                            @click="pesanTutup = true"
                            v-show="!pesanTutup"
                        >
                            ✕
                        </button>
                    </div>
                </div>

                <main class="px-4 py-4 sm:px-6 sm:py-5">
                    <slot />
                </main>
            </div>
        </div>
    </div>
</template>
