<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import Tata from '@/components/Tata.vue';
import Kepala from '@/components/Kepala.vue';
import Kosong from '@/components/Kosong.vue';
import { rute } from '@/lib/rute';

const props = defineProps<{
    prospek: any[];
    sumber: any;
}>();

const q = ref('');
const pilih = ref<Set<number>>(new Set());
const kirim = ref(false);

const tersaring = computed(() => {
    const kunci = q.value.toLowerCase().trim();
    if (!kunci) return props.prospek;

    return props.prospek.filter(
        (p) =>
            (p.nama || '').toLowerCase().includes(kunci) ||
            (p.perusahaan || '').toLowerCase().includes(kunci) ||
            (p.kota || '').toLowerCase().includes(kunci) ||
            (p.email || '').toLowerCase().includes(kunci),
    );
});

const bisaDipilih = computed(() => tersaring.value.filter((p) => !p.sudah));

function toggle(p: any) {
    if (p.sudah) return;
    const s = new Set(pilih.value);
    if (s.has(p.id)) {
        s.delete(p.id);
    } else {
        s.add(p.id);
    }
    pilih.value = s;
}

function pilihSemua() {
    pilih.value = new Set(bisaDipilih.value.map((p) => p.id));
}

function kosongkan() {
    pilih.value = new Set();
}

async function impor() {
    if (pilih.value.size === 0) return;
    kirim.value = true;
    router.post(
        rute.klienImpor,
        { id: Array.from(pilih.value) },
        {
            onFinish: () => {
                kirim.value = false;
            },
        },
    );
}

watch(q, kosongkan);
</script>

<template>
    <Head title="Impor prospek" />
    <Tata>
        <Kepala
            judul="Impor prospek"
            sub="Ambil prospek dari Marketing Tools jadi klien berstatus prospek"
            :kembali="rute.klien"
            kembali-teks="Daftar klien"
        />

        <div v-if="!prospek.length" class="dk-kartu">
            <Kosong
                teks="Tidak ada prospek terbaca dari Marketing Tools. Cek koneksi DB dkmarketing."
                ikon="⇄"
            />
        </div>

        <template v-else>
            <div
                class="dk-kartu dk-kartu-p mb-3 flex flex-wrap items-center gap-2"
            >
                <input
                    v-model="q"
                    type="search"
                    class="dk-isian max-w-xs flex-1"
                    placeholder="Cari prospek…"
                />
                <button
                    type="button"
                    class="dk-tbl dk-tbl-kosong"
                    @click="pilihSemua"
                >
                    Pilih semua ({{ bisaDipilih.length }})
                </button>
                <button
                    v-if="pilih.size"
                    type="button"
                    class="dk-tbl dk-tbl-halus"
                    @click="kosongkan"
                >
                    Kosongkan
                </button>
                <span class="dk-sub ml-auto"
                    >{{ pilih.size }} dipilih ·
                    {{ prospek.length }} prospek</span
                >
                <button
                    type="button"
                    class="dk-tbl dk-tbl-utama"
                    :disabled="!pilih.size || kirim"
                    @click="impor"
                >
                    {{ kirim ? 'Mengimpor…' : `Impor ${pilih.size || ''}` }}
                </button>
            </div>

            <div class="dk-kartu overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="dk-tabel">
                        <thead>
                            <tr>
                                <th class="w-10"></th>
                                <th>Nama</th>
                                <th>Kategori</th>
                                <th>Wilayah</th>
                                <th>Kontak</th>
                                <th>Sumber</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="p in tersaring"
                                :key="p.id"
                                :class="
                                    p.sudah ? 'opacity-50' : 'cursor-pointer'
                                "
                                @click="toggle(p)"
                            >
                                <td>
                                    <input
                                        type="checkbox"
                                        :checked="pilih.has(p.id) || p.sudah"
                                        :disabled="p.sudah"
                                        @click.stop="toggle(p)"
                                    />
                                </td>
                                <td class="font-medium text-gray-900">
                                    {{ p.nama }}
                                </td>
                                <td class="text-gray-600">
                                    {{ p.perusahaan || '—' }}
                                </td>
                                <td class="text-gray-600">
                                    {{ p.kota || '—' }}
                                    <a
                                        v-if="p.situs"
                                        :href="p.situs"
                                        target="_blank"
                                        rel="noopener"
                                        class="block text-xs text-blue-600 hover:underline"
                                        @click.stop
                                        >{{ p.situs }}</a
                                    >
                                </td>
                                <td class="text-gray-600">
                                    <span
                                        v-if="p.email"
                                        class="block text-xs"
                                        >{{ p.email }}</span
                                    >
                                    <span
                                        v-if="p.telepon"
                                        class="block text-xs"
                                        >{{ p.telepon }}</span
                                    >
                                    <span v-if="!p.email && !p.telepon">—</span>
                                </td>
                                <td>
                                    <span
                                        v-if="p.sudah"
                                        class="dk-badge bg-gray-100 text-gray-500"
                                        >sudah ada</span
                                    >
                                    <span v-else class="dk-sub">{{
                                        p.sumber || '—'
                                    }}</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <Kosong
                    v-if="!tersaring.length"
                    teks="Tidak ada prospek cocok."
                    ikon="○"
                />
            </div>
        </template>
    </Tata>
</template>
