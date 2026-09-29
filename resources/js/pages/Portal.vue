<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    perusahaan: string;
    warna: string;
    proyek: any;
}>();

const p = computed(() => props.proyek);
</script>

<template>
    <Head :title="`Progres ${proyek.nama}`" />
    <div class="min-h-screen bg-gray-50 py-8">
        <div class="mx-auto max-w-2xl px-4">
            <header class="mb-5 text-center">
                <p
                    class="mx-auto mb-3 flex h-9 w-9 items-center justify-center rounded-[4px] text-xs font-bold text-white"
                    :style="{ background: warna }"
                >
                    {{ perusahaan.slice(0, 2).toUpperCase() }}
                </p>
                <p class="dk-sub">{{ perusahaan }}</p>
                <h1 class="mt-0.5 text-xl font-semibold text-gray-900">
                    {{ proyek.nama }}
                </h1>
                <p class="dk-sub mt-1">
                    {{ proyek.jenis }} · untuk {{ proyek.klien }}
                </p>
            </header>

            <div class="dk-kartu dk-kartu-p mb-4">
                <div class="mb-2 flex items-center justify-between text-sm">
                    <span class="text-gray-600">{{ proyek.status }}</span>
                    <span class="font-medium tabular-nums"
                        >{{ proyek.persen }}%</span
                    >
                </div>
                <div class="dk-progress">
                    <span
                        :style="{
                            width: proyek.persen + '%',
                            background: warna,
                        }"
                    />
                </div>
                <p v-if="proyek.tahap" class="dk-sub mt-2">
                    Tahap sekarang: <strong>{{ proyek.tahap }}</strong>
                </p>
                <p v-if="proyek.tgl_serah" class="dk-sub">
                    Diserahkan {{ proyek.tgl_serah }}
                </p>
                <p v-if="proyek.revisi > 0" class="dk-sub">
                    Sudah {{ proyek.revisi }}× revisi
                </p>
            </div>

            <div class="dk-kartu">
                <header class="border-b border-gray-100 px-4 py-2.5">
                    <h2 class="text-sm font-semibold">Tahapan pengerjaan</h2>
                </header>
                <ol class="divide-y divide-gray-50">
                    <li
                        v-for="t in proyek.tahapan"
                        :key="t.urutan"
                        class="flex items-start gap-3 px-4 py-3"
                    >
                        <span
                            class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full border text-[10px]"
                            :style="{
                                borderColor:
                                    t.status === 'selesai' ? warna : '#d1d5db',
                                color:
                                    t.status === 'selesai' ? warna : '#9ca3af',
                                background:
                                    t.status === 'selesai'
                                        ? warna + '14'
                                        : 'transparent',
                            }"
                        >
                            {{ t.status === 'selesai' ? '✓' : t.urutan }}
                        </span>
                        <div class="min-w-0 flex-1">
                            <p
                                class="text-sm"
                                :class="
                                    t.status === 'selesai'
                                        ? 'text-gray-400'
                                        : t.status === 'jalan'
                                          ? 'font-medium text-gray-900'
                                          : 'text-gray-600'
                                "
                            >
                                {{ t.nama }}
                            </p>
                            <p class="dk-sub">
                                {{ t.label_status
                                }}<span v-if="t.tgl_teks">
                                    · {{ t.tgl_teks }}</span
                                >
                            </p>
                            <p v-if="t.catatan" class="dk-sub mt-0.5 italic">
                                {{ t.catatan }}
                            </p>
                        </div>
                    </li>
                </ol>
            </div>

            <p
                v-if="proyek.tautan"
                class="dk-kartu dk-kartu-p mt-4 text-center text-sm"
            >
                Hasil bisa dilihat di
                <a
                    :href="proyek.tautan"
                    target="_blank"
                    rel="noopener"
                    class="font-medium underline"
                    :style="{ color: warna }"
                    >{{ proyek.tautan }}</a
                >
            </p>

            <footer class="dk-sub mt-6 text-center">
                <p>
                    Halaman ini hanya menampilkan progres. Untuk pertanyaan,
                    hubungi kami langsung.
                </p>
            </footer>
        </div>
    </div>
</template>
