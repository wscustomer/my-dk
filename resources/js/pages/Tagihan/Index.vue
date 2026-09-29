<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import Tata from '@/components/Tata.vue';
import Kepala from '@/components/Kepala.vue';
import Badge from '@/components/Badge.vue';
import Kosong from '@/components/Kosong.vue';
import Paginasi from '@/components/Paginasi.vue';
import Modal from '@/components/Modal.vue';
import { rute, hariIni } from '@/lib/rute';
import { useForm } from '@inertiajs/vue3';

const props = defineProps<{
    daftar: any;
    ringkas: any;
    filter: any;
    opsi: any;
    rekening: string | null;
}>();

const q = ref(props.filter.q || '');
const status = ref(props.filter.status || 'terkirim');
const klienId = ref(props.filter.klien || '');
const urutan = ref(props.filter.urutan || 'tempo');
const bayarUntuk = ref<any>(null);

let jeda: number | undefined;
watch(q, () => {
    window.clearTimeout(jeda);
    jeda = window.setTimeout(terapkan, 300);
});
watch([status, klienId, urutan], terapkan);

function terapkan() {
    router.get(
        rute.tagihan,
        {
            q: q.value || undefined,
            status: status.value || undefined,
            klien: klienId.value || undefined,
            urutan: urutan.value,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

const formB = useForm({
    nominal: 0,
    tgl: hariIni(),
    metode: 'transfer',
    referensi: '',
    catatan: '',
});

function bukaBayar(t: any) {
    bayarUntuk.value = t;
    formB.nominal = t.sisa;
    formB.tgl = hariIni();
    formB.metode = 'transfer';
    formB.referensi = '';
    formB.catatan = '';
}

function simpanBayar() {
    formB.post(rute.tagihanBayar(bayarUntuk.value.id), {
        preserveScroll: true,
        onSuccess: () => (bayarUntuk.value = null),
    });
}

function hapusBayar(t: any, b: any) {
    if (!confirm(`Batalkan pembayaran ${b.nominal_teks}?`)) return;
    router.delete(rute.tagihanBayarHapus(t.id, b.id), { preserveScroll: true });
}

function batalkan(t: any) {
    if (!confirm(`Batalkan tagihan "${t.judul}"?`)) return;
    router.post(rute.tagihanBatal(t.id), {}, { preserveScroll: true });
}

const ada = computed(() => props.daftar.data.length > 0);
</script>

<template>
    <Head title="Tagihan" />
    <Tata>
        <Kepala judul="Tagihan" sub="Piutang, pembayaran, dan status penagihan">
            <template #aksi>
                <a :href="rute.tagihanUnduh" class="dk-tbl dk-tbl-kosong"
                    >↓ CSV</a
                >
                <Link :href="rute.tagihanBaru" class="dk-tbl dk-tbl-utama"
                    >+ Tagihan</Link
                >
            </template>
        </Kepala>

        <!-- Ringkas -->
        <div class="mb-3 grid grid-cols-2 gap-3 lg:grid-cols-4">
            <div class="dk-kartu dk-kartu-p">
                <p class="dk-sub">Total ditagih</p>
                <p class="text-lg font-semibold tabular-nums">
                    {{ ringkas.total_nilai }}
                </p>
            </div>
            <div class="dk-kartu dk-kartu-p">
                <p class="dk-sub">Terbayar</p>
                <p class="text-lg font-semibold text-green-700 tabular-nums">
                    {{ ringkas.terbayar }}
                </p>
            </div>
            <div class="dk-kartu dk-kartu-p">
                <p class="dk-sub">Piutang (belum terbayar)</p>
                <p class="text-lg font-semibold text-red-700 tabular-nums">
                    {{ ringkas.piutang }}
                </p>
            </div>
            <div class="dk-kartu dk-kartu-p">
                <p class="dk-sub">Tagihan lewat tempo</p>
                <p class="text-lg font-semibold text-red-700 tabular-nums">
                    {{ ringkas.lewat }}
                </p>
            </div>
        </div>

        <!-- Saring -->
        <div class="dk-kartu dk-kartu-p mb-3 flex flex-wrap items-center gap-2">
            <input
                v-model="q"
                type="search"
                class="dk-isian max-w-xs flex-1"
                placeholder="Cari judul tagihan…"
            />
            <select v-model="status" class="dk-isian w-auto">
                <option value="">Semua status</option>
                <option value="piutang">Piutang (belum lunas)</option>
                <option value="lewat">Lewat tempo</option>
                <option value="sebagian">Sebagian</option>
                <option value="lunas">Lunas</option>
                <option value="draft">Draft</option>
                <option value="batal">Batal</option>
            </select>
            <select v-model="klienId" class="dk-isian w-auto max-w-[12rem]">
                <option value="">Semua klien</option>
                <option v-for="k in opsi.klien" :key="k.id" :value="k.id">
                    {{ k.nama }}
                </option>
            </select>
            <select v-model="urutan" class="dk-isian w-auto">
                <option value="tempo">Jatuh tempo terdekat</option>
                <option value="nilai">Nilai terbesar</option>
            </select>
        </div>

        <div class="dk-kartu overflow-hidden">
            <div v-if="ada" class="overflow-x-auto">
                <table class="dk-tabel">
                    <thead>
                        <tr>
                            <th>Tagihan</th>
                            <th>Klien</th>
                            <th>Status</th>
                            <th>Jatuh tempo</th>
                            <th class="text-right">Total</th>
                            <th class="text-right">Sisa</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="t in daftar.data" :key="t.id">
                            <td>
                                <button
                                    type="button"
                                    class="text-left font-medium text-gray-900"
                                    @click="bukaBayar(t)"
                                >
                                    {{ t.judul }}
                                </button>
                                <span v-if="t.proyek" class="dk-sub block">{{
                                    t.proyek
                                }}</span>
                            </td>
                            <td>
                                <Link
                                    :href="rute.klienDetail(t.klien_id)"
                                    class="text-gray-600 hover:text-blue-700"
                                    >{{ t.klien }}</Link
                                >
                            </td>
                            <td>
                                <Badge
                                    :teks="t.label_state"
                                    :warna="t.warna_state"
                                />
                                <span
                                    v-if="t.hari_lewat"
                                    class="dk-sub ml-1 text-[11px] text-red-600"
                                    >{{ t.hari_lewat }}h</span
                                >
                            </td>
                            <td>
                                <span
                                    :class="
                                        t.state === 'lewat'
                                            ? 'text-red-600'
                                            : ''
                                    "
                                    >{{ t.tgl_teks || '—' }}</span
                                >
                            </td>
                            <td class="text-right tabular-nums">
                                {{ t.total_teks }}
                            </td>
                            <td
                                class="text-right font-medium tabular-nums"
                                :class="
                                    t.sisa > 0
                                        ? 'text-red-700'
                                        : 'text-green-700'
                                "
                            >
                                {{ t.sisa_teks }}
                            </td>
                            <td class="text-right whitespace-nowrap">
                                <button
                                    v-if="!t.lunas && t.sisa > 0"
                                    type="button"
                                    class="dk-tbl dk-tbl-kosong"
                                    @click="bukaBayar(t)"
                                >
                                    Bayar
                                </button>
                                <Link
                                    :href="rute.tagihanUbah(t.id)"
                                    class="dk-tbl dk-tbl-halus"
                                    >Ubah</Link
                                >
                                <button
                                    v-if="
                                        t.status !== 'batal' && t.terbayar === 0
                                    "
                                    type="button"
                                    class="dk-tbl dk-tbl-halus text-red-600"
                                    @click="batalkan(t)"
                                >
                                    Batal
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <Kosong
                v-else
                teks="Belum ada tagihan cocok."
                ikon="▤"
                aksi="Buat tagihan"
                @klik="router.visit(rute.tagihanBaru)"
            />
        </div>

        <Paginasi :halaman="daftar" />

        <!-- Modal pembayaran + riwayat -->
        <Modal
            :buka="bayarUntuk !== null"
            :judul="bayarUntuk?.judul || 'Tagihan'"
            lebar="md"
            @tutup="bayarUntuk = null"
        >
            <div v-if="bayarUntuk" class="space-y-4 text-sm">
                <div
                    class="grid grid-cols-3 gap-2 rounded-[4px] bg-gray-50 p-3"
                >
                    <div>
                        <p class="dk-label">Total</p>
                        <p class="tabular-nums">{{ bayarUntuk.total_teks }}</p>
                    </div>
                    <div>
                        <p class="dk-label">Terbayar</p>
                        <p class="tabular-nums">
                            {{ bayarUntuk.terbayar_teks }}
                        </p>
                    </div>
                    <div>
                        <p class="dk-label">Sisa</p>
                        <p class="font-medium text-red-700 tabular-nums">
                            {{ bayarUntuk.sisa_teks }}
                        </p>
                    </div>
                </div>

                <div v-if="rekening" class="rounded-[4px] bg-gray-50 p-3">
                    <p class="dk-label">Rekening pembayaran</p>
                    <ul class="mt-1 space-y-0.5">
                        <li
                            v-for="(r, i) in rekening.split('|')"
                            :key="i"
                            class="text-gray-800"
                        >
                            {{ r.trim() }}
                        </li>
                    </ul>
                </div>

                <div v-if="bayarUntuk.pembayaran.length">
                    <p class="dk-label">Riwayat pembayaran</p>
                    <div class="divide-y divide-gray-50">
                        <div
                            v-for="b in bayarUntuk.pembayaran"
                            :key="b.id"
                            class="flex items-center justify-between gap-2 py-1.5"
                        >
                            <div>
                                <p class="tabular-nums">{{ b.nominal_teks }}</p>
                                <p class="dk-sub">
                                    {{ b.tgl_teks }} · {{ b.label_metode }}
                                    <span v-if="b.referensi"
                                        >· {{ b.referensi }}</span
                                    >
                                </p>
                            </div>
                            <button
                                type="button"
                                class="dk-tbl dk-tbl-halus text-red-600"
                                @click="hapusBayar(bayarUntuk, b)"
                            >
                                Batalkan
                            </button>
                        </div>
                    </div>
                </div>

                <form
                    v-if="bayarUntuk.sisa > 0 && bayarUntuk.status !== 'batal'"
                    class="space-y-3 border-t border-gray-100 pt-3"
                    @submit.prevent="simpanBayar"
                >
                    <p class="dk-label">Catat pembayaran baru</p>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <label class="block">
                            <span class="dk-label"
                                >Nominal<span class="text-red-500">
                                    *</span
                                ></span
                            >
                            <input
                                v-model="formB.nominal"
                                type="number"
                                min="0"
                                step="1000"
                                class="dk-isian"
                            />
                            <span
                                v-if="formB.errors.nominal"
                                class="dk-galat"
                                >{{ formB.errors.nominal }}</span
                            >
                        </label>
                        <label class="block">
                            <span class="dk-label">Tanggal</span>
                            <input
                                v-model="formB.tgl"
                                type="date"
                                class="dk-isian"
                            />
                        </label>
                        <label class="block">
                            <span class="dk-label">Metode</span>
                            <select v-model="formB.metode" class="dk-isian">
                                <option
                                    v-for="(v, k) in opsi.metode"
                                    :key="k"
                                    :value="k"
                                >
                                    {{ v }}
                                </option>
                            </select>
                        </label>
                        <label class="block">
                            <span class="dk-label">Referensi</span>
                            <input
                                v-model="formB.referensi"
                                class="dk-isian"
                                placeholder="No. transfer / bukti"
                            />
                        </label>
                    </div>
                    <div class="flex justify-end gap-2">
                        <button
                            type="button"
                            class="dk-tbl dk-tbl-kosong"
                            @click="bayarUntuk = null"
                        >
                            Tutup
                        </button>
                        <button
                            type="submit"
                            class="dk-tbl dk-tbl-utama"
                            :disabled="formB.processing"
                        >
                            Catat pembayaran
                        </button>
                    </div>
                </form>
                <div
                    v-else
                    class="flex justify-end border-t border-gray-100 pt-3"
                >
                    <button
                        type="button"
                        class="dk-tbl dk-tbl-kosong"
                        @click="bayarUntuk = null"
                    >
                        Tutup
                    </button>
                </div>
            </div>
        </Modal>
    </Tata>
</template>
