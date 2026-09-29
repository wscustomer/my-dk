<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import Tata from '@/components/Tata.vue';
import Kepala from '@/components/Kepala.vue';
import Badge from '@/components/Badge.vue';
import Kosong from '@/components/Kosong.vue';
import Paginasi from '@/components/Paginasi.vue';
import { rute } from '@/lib/rute';

const props = defineProps<{
    daftar: any;
    papan: boolean;
    kolom: { kunci: string; label: string }[];
    filter: any;
    opsi: any;
}>();

const q = ref(props.filter.q || '');
const status = ref(props.filter.status || '');
const jenis = ref(props.filter.jenis || '');
const klienId = ref(props.filter.klien || '');
const urutan = ref(props.filter.urutan || 'target');
const seretKe = ref<string | null>(null);
const seretId = ref<number | null>(null);

let jeda: number | undefined;
watch(q, () => {
    window.clearTimeout(jeda);
    jeda = window.setTimeout(terapkan, 300);
});
watch([status, jenis, klienId, urutan], terapkan);

function terapkan() {
    router.get(
        rute.proyek,
        {
            q: q.value || undefined,
            status: status.value || undefined,
            jenis: jenis.value || undefined,
            klien: klienId.value || undefined,
            urutan: urutan.value,
            papan: props.papan ? 1 : undefined,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

function modePapan(nilai: boolean) {
    router.get(
        rute.proyek,
        { ...bersihFilter(), papan: nilai ? 1 : undefined },
        { preserveState: true, replace: true },
    );
}

function bersihFilter() {
    return {
        q: q.value || undefined,
        status: status.value || undefined,
        jenis: jenis.value || undefined,
        klien: klienId.value || undefined,
        urutan: urutan.value,
    };
}

/** Kolom papan dikelompokkan dari daftar datar. */
const papanData = computed(() => {
    const map: Record<string, any[]> = {};
    for (const k of props.kolom) map[k.kunci] = [];
    for (const p of props.daftar as any[]) {
        if (map[p.status]) map[p.status].push(p);
    }

    return map;
});

function mulaiSeret(p: any) {
    seretId.value = p.id;
}

function jatuhDi(kunci: string) {
    seretKe.value = kunci;
}

function lepas(kunci: string) {
    seretKe.value = null;
    const id = seretId.value;
    seretId.value = null;
    if (!id) return;

    const proyek = (props.daftar as any[]).find((p) => p.id === id);
    if (!proyek || proyek.status === kunci) return;

    let alasan: string | null = null;
    if (kunci === 'ditahan') {
        alasan = prompt('Alasan ditahan (opsional):') || null;
    }

    router.patch(
        rute.proyekStatus(id),
        { status: kunci, alasan_batal: alasan },
        { preserveScroll: true },
    );
}

const ada = computed(() => (props.daftar as any[]).length > 0);
</script>

<template>
    <Head title="Proyek" />
    <Tata>
        <Kepala
            :judul="`Proyek (${papan ? daftar.length : daftar.total})`"
            sub="Pembuatan website & aplikasi"
        >
            <template #aksi>
                <a :href="rute.proyekUnduh" class="dk-tbl dk-tbl-kosong">↓ CSV</a>
                <button
                    type="button"
                    class="dk-tbl dk-tbl-kosong"
                    @click="modePapan(!papan)"
                >
                    {{ papan ? '▤ Daftar' : '▦ Papan' }}
                </button>
                <Link :href="rute.proyekBaru" class="dk-tbl dk-tbl-utama"
                    >+ Proyek</Link
                >
            </template>
        </Kepala>

        <div class="dk-kartu dk-kartu-p mb-3 flex flex-wrap items-center gap-2">
            <input
                v-model="q"
                type="search"
                class="dk-isian max-w-xs flex-1"
                placeholder="Cari nama / kode proyek…"
            />
            <select v-if="!papan" v-model="status" class="dk-isian w-auto">
                <option value="">Semua status</option>
                <option v-for="(v, k) in opsi.status" :key="k" :value="k">
                    {{ v }}
                </option>
            </select>
            <select v-model="jenis" class="dk-isian w-auto">
                <option value="">Semua jenis</option>
                <option v-for="(v, k) in opsi.jenis" :key="k" :value="k">
                    {{ v }}
                </option>
            </select>
            <select v-model="klienId" class="dk-isian w-auto max-w-[12rem]">
                <option value="">Semua klien</option>
                <option v-for="k in opsi.klien" :key="k.id" :value="k.id">
                    {{ k.nama }}
                </option>
            </select>
            <select v-if="!papan" v-model="urutan" class="dk-isian w-auto">
                <option value="target">Target terdekat</option>
                <option value="nilai">Nilai terbesar</option>
            </select>
        </div>

        <!-- PAPAN -->
        <div v-if="papan" class="dk-papan">
            <div
                v-for="k in kolom"
                :key="k.kunci"
                class="dk-papan-kolom"
                :data-seret="seretKe === k.kunci"
                @dragover.prevent="jatuhDi(k.kunci)"
                @drop.prevent="lepas(k.kunci)"
            >
                <header
                    class="flex items-center justify-between border-b border-gray-200 px-3 py-2"
                >
                    <span class="text-xs font-semibold text-gray-700">{{
                        k.label
                    }}</span>
                    <span class="dk-sub">{{ papanData[k.kunci]?.length || 0 }}</span>
                </header>
                <div class="space-y-2 p-2">
                    <div
                        v-for="p in papanData[k.kunci]"
                        :key="p.id"
                        draggable="true"
                        class="dk-kartu cursor-grab p-2.5 active:cursor-grabbing"
                        @dragstart="mulaiSeret(p)"
                        @dblclick="router.visit(rute.proyekLihat(p.id))"
                    >
                        <Link
                            :href="rute.proyekLihat(p.id)"
                            class="block text-sm font-medium text-gray-900 hover:text-blue-700"
                            >{{ p.nama }}</Link
                        >
                        <p class="dk-sub mt-0.5 truncate">{{ p.klien }}</p>
                        <div class="mt-1.5 flex items-center gap-2">
                            <div class="dk-progress flex-1">
                                <span :style="{ width: p.persen + '%' }" />
                            </div>
                            <span class="dk-sub tabular-nums"
                                >{{ p.tahapan_selesai }}/{{ p.tahapan_jumlah }}</span
                            >
                        </div>
                        <p v-if="p.tgl_target_teks" class="dk-sub mt-1">
                            <span :class="p.telat ? 'text-red-600' : ''">{{
                                p.tgl_target_teks
                            }}</span>
                        </p>
                    </div>
                    <p v-if="!papanData[k.kunci]?.length" class="dk-sub py-3 text-center">
                        kosong
                    </p>
                </div>
            </div>
        </div>

        <!-- DAFTAR -->
        <template v-else>
            <div class="dk-kartu overflow-hidden">
                <div v-if="ada" class="overflow-x-auto">
                    <table class="dk-tabel">
                        <thead>
                            <tr>
                                <th>Proyek</th>
                                <th>Klien</th>
                                <th>Status</th>
                                <th>Progres</th>
                                <th>Target</th>
                                <th class="text-right">Nilai</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="p in daftar.data" :key="p.id">
                                <td>
                                    <Link
                                        :href="rute.proyekLihat(p.id)"
                                        class="font-medium text-gray-900 hover:text-blue-700"
                                        >{{ p.nama }}</Link
                                    >
                                    <span class="dk-sub block"
                                        >{{ p.kode }} · {{ p.label_jenis }}</span
                                    >
                                </td>
                                <td>
                                    <Link
                                        :href="rute.klienDetail(p.klien_id)"
                                        class="text-gray-600 hover:text-blue-700"
                                        >{{ p.klien }}</Link
                                    >
                                </td>
                                <td>
                                    <Badge :teks="p.label_status" warna="#6b7280" />
                                </td>
                                <td class="min-w-[8rem]">
                                    <div class="flex items-center gap-2">
                                        <div class="dk-progress flex-1">
                                            <span :style="{ width: p.persen + '%' }" />
                                        </div>
                                        <span class="dk-sub tabular-nums"
                                            >{{ p.persen }}%</span
                                        >
                                    </div>
                                    <span class="dk-sub">{{ p.tahap || '—' }}</span>
                                </td>
                                <td>
                                    <span :class="p.telat ? 'text-red-600 font-medium' : ''">{{
                                        p.tgl_target_teks || '—'
                                    }}</span>
                                </td>
                                <td class="text-right tabular-nums">
                                    {{ p.nilai_teks }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <Kosong
                    v-else
                    teks="Belum ada proyek cocok."
                    ikon="▣"
                    aksi="Tambah proyek"
                    @klik="router.visit(rute.proyekBaru)"
                />
            </div>
            <Paginasi :halaman="daftar" />
        </template>
    </Tata>
</template>
