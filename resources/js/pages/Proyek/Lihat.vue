<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import Tata from '@/components/Tata.vue';
import Kepala from '@/components/Kepala.vue';
import Badge from '@/components/Badge.vue';
import Kosong from '@/components/Kosong.vue';
import Modal from '@/components/Modal.vue';
import { rute, bulan, hariIni } from '@/lib/rute';

const props = defineProps<{
    proyek: any;
    opsi: any;
}>();

const p = computed(() => props.proyek);
const tab = ref('tahapan');
const modalAktivitas = ref(false);
const modalLampiran = ref(false);
const modalTahapan = ref<any>(null);
const modalStatus = ref(false);

const tabs = [
    { kunci: 'tahapan', label: 'Tahapan' },
    { kunci: 'tagihan', label: 'Tagihan' },
    { kunci: 'aktivitas', label: 'Aktivitas' },
    { kunci: 'lampiran', label: 'Lampiran' },
];

const formTahapan = useForm({
    status: 'belum',
    tgl_mulai: '',
    tgl_selesai: '',
    catatan: '',
});

function bukaTahapan(t: any) {
    modalTahapan.value = t;
    formTahapan.status = t.status;
    formTahapan.tgl_mulai = t.tgl_mulai || '';
    formTahapan.tgl_selesai = t.tgl_selesai || '';
    formTahapan.catatan = t.catatan || '';
}

function simpanTahapan() {
    formTahapan.patch(rute.proyekTahapan(p.value.id, modalTahapan.value.id), {
        preserveScroll: true,
        onSuccess: () => (modalTahapan.value = null),
    });
}

function majukan() {
    const berikut = p.value.tahapan.find((t: any) => t.status === 'belum');
    if (berikut) bukaTahapan({ ...berikut, status: 'jalan' });
}

const formA = useForm({
    judul: '',
    jenis: 'catatan',
    tgl: hariIni(),
    catatan: '',
});

function simpanAktivitas() {
    formA.post(rute.proyekAktivitas(p.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            modalAktivitas.value = false;
            formA.reset('judul', 'catatan');
        },
    });
}

const formL = useForm<{ berkas: File | null }>({ berkas: null });

function unggahLampiran() {
    formL.post(rute.proyekLampiran(p.value.id), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            modalLampiran.value = false;
            formL.reset('berkas');
        },
    });
}

const formS = useForm({ status: p.value.status, alasan_batal: '' });

function simpanStatus() {
    formS.patch(rute.proyekStatus(p.value.id), {
        preserveScroll: true,
        onSuccess: () => (modalStatus.value = false),
    });
}

function terbitPortal() {
    router.post(rute.proyekPortal(p.value.id), {}, { preserveScroll: true });
}

function cabutPortal() {
    if (!confirm('Cabut akses portal klien untuk proyek ini?')) return;
    router.delete(rute.proyekPortal(p.value.id), { preserveScroll: true });
}

const tautanPortal = computed(() =>
    p.value.portal_token ? `${window.location.origin}/p/${p.value.portal_token}` : '',
);

async function salinPortal() {
    try {
        await navigator.clipboard.writeText(tautanPortal.value);
    } catch {
        /* peramban menolak — tautan tetap tampil untuk disalin manual */
    }
}
</script>

<template>
    <Head :title="proyek.nama" />
    <Tata>
        <Kepala
            :judul="proyek.nama"
            :sub="[proyek.kode, proyek.label_jenis, proyek.pemilik].filter(Boolean).join(' · ')"
            :kembali="rute.proyek"
            kembali-teks="Daftar proyek"
        >
            <template #aksi>
                <Badge :teks="proyek.label_status" warna="#6b7280" />
                <button
                    type="button"
                    class="dk-tbl dk-tbl-kosong"
                    @click="((formS.status = proyek.status), (modalStatus = true))"
                >
                    Ubah status
                </button>
                <Link :href="rute.proyekUbah(proyek.id)" class="dk-tbl dk-tbl-kosong"
                    >Ubah</Link
                >
            </template>
        </Kepala>

        <div class="grid gap-4 lg:grid-cols-4">
            <aside class="space-y-4 lg:col-span-1">
                <div class="dk-kartu dk-kartu-p space-y-2.5 text-sm">
                    <div>
                        <p class="dk-label">Klien</p>
                        <Link
                            :href="rute.klienDetail(proyek.klien_id)"
                            class="font-medium text-gray-900 hover:text-blue-700"
                            >{{ proyek.klien }}</Link
                        >
                    </div>
                    <div>
                        <p class="dk-label">Progres</p>
                        <div class="dk-progress">
                            <span :style="{ width: proyek.persen + '%' }" />
                        </div>
                        <p class="dk-sub mt-1">
                            {{ proyek.persen }}% · {{ proyek.tahap || 'tanpa tahapan' }}
                        </p>
                    </div>
                    <div>
                        <p class="dk-label">Nilai kontrak</p>
                        <p class="font-medium tabular-nums">{{ proyek.nilai_teks }}</p>
                        <p v-if="proyek.dp_nominal > 0" class="dk-sub">
                            DP {{ proyek.dp_teks }}
                        </p>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <p class="dk-label">Mulai</p>
                            <p>{{ proyek.tgl_mulai ? bulan(proyek.tgl_mulai) : '—' }}</p>
                        </div>
                        <div>
                            <p class="dk-label">Target</p>
                            <p>{{ proyek.tgl_target ? bulan(proyek.tgl_target) : '—' }}</p>
                        </div>
                    </div>
                    <div v-if="proyek.tgl_serah">
                        <p class="dk-label">Diserahkan</p>
                        <p>{{ bulan(proyek.tgl_serah) }}</p>
                    </div>
                    <div>
                        <p class="dk-label">Keuangan</p>
                        <p class="dk-sub">
                            tertagih {{ proyek.nilai_tertagih }} · terbayar
                            {{ proyek.terbayar }}
                        </p>
                        <p class="text-sm font-medium text-gray-900">
                            sisa {{ proyek.sisa_tagihan }}
                        </p>
                    </div>
                    <div v-if="proyek.deskripsi">
                        <p class="dk-label">Ringkas</p>
                        <p class="whitespace-pre-line text-gray-700">
                            {{ proyek.deskripsi }}
                        </p>
                    </div>
                    <div v-if="proyek.catatan">
                        <p class="dk-label">Catatan</p>
                        <p class="whitespace-pre-line text-gray-700">
                            {{ proyek.catatan }}
                        </p>
                    </div>
                </div>

                <!-- Portal klien -->
                <div class="dk-kartu dk-kartu-p">
                    <p class="dk-label mb-2">Portal klien</p>
                    <template v-if="proyek.portal_aktif">
                        <input
                            :value="tautanPortal"
                            readonly
                            class="dk-isian mb-2 text-xs"
                            @focus="(e: any) => e.target.select()"
                        />
                        <div class="flex gap-1.5">
                            <button
                                type="button"
                                class="dk-tbl dk-tbl-kosong flex-1 justify-center"
                                @click="salinPortal"
                            >
                                Salin tautan
                            </button>
                            <button
                                type="button"
                                class="dk-tbl dk-tbl-bahaya"
                                @click="cabutPortal"
                            >
                                Cabut
                            </button>
                        </div>
                    </template>
                    <template v-else>
                        <p class="dk-sub mb-2">
                            Beri klien tautan baca-saja untuk melihat progres.
                        </p>
                        <button
                            type="button"
                            class="dk-tbl dk-tbl-kosong w-full justify-center"
                            @click="terbitPortal"
                        >
                            Terbitkan tautan
                        </button>
                    </template>
                </div>

                <div class="dk-kartu dk-kartu-p">
                    <p class="dk-label mb-2">Tindakan</p>
                    <div class="flex flex-col gap-1.5">
                        <Link
                            :href="rute.tagihanBaru + '?klien=' + proyek.klien_id + '&proyek=' + proyek.id"
                            class="dk-tbl dk-tbl-kosong justify-center"
                            >+ Tagihan</Link
                        >
                        <button
                            type="button"
                            class="dk-tbl dk-tbl-kosong justify-center"
                            @click="majukan"
                        >
                            → Tahap berikutnya
                        </button>
                    </div>
                </div>
            </aside>

            <section class="lg:col-span-3">
                <div class="mb-3 flex flex-wrap items-center gap-1 border-b border-gray-200">
                    <button
                        v-for="t in tabs"
                        :key="t.kunci"
                        type="button"
                        class="dk-tab"
                        :data-aktif="tab === t.kunci"
                        @click="tab = t.kunci"
                    >
                        {{ t.label }}
                        <span class="ml-1 text-xs text-gray-400">
                            {{
                                t.kunci === 'tahapan'
                                    ? proyek.tahapan.length
                                    : t.kunci === 'tagihan'
                                      ? proyek.tagihan.length
                                      : t.kunci === 'aktivitas'
                                        ? proyek.aktivitas.length
                                        : proyek.lampiran.length
                            }}
                        </span>
                    </button>
                </div>

                <!-- Tahapan -->
                <div v-show="tab === 'tahapan'" class="dk-kartu">
                    <div v-if="proyek.tahapan.length" class="divide-y divide-gray-50">
                        <button
                            v-for="t in proyek.tahapan"
                            :key="t.id"
                            type="button"
                            class="flex w-full items-start gap-3 px-4 py-2.5 text-left hover:bg-gray-50"
                            @click="bukaTahapan(t)"
                        >
                            <span
                                class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full border text-[10px]"
                                :style="{
                                    borderColor: t.status === 'selesai' ? '#16a34a' : '#d1d5db',
                                    color: t.status === 'selesai' ? '#16a34a' : '#9ca3af',
                                }"
                            >
                                {{ t.status === 'selesai' ? '✓' : t.urutan }}
                            </span>
                            <div class="min-w-0 flex-1">
                                <p
                                    class="text-sm"
                                    :class="
                                        t.status === 'selesai'
                                            ? 'text-gray-400 line-through'
                                            : 'text-gray-800'
                                    "
                                >
                                    {{ t.nama }}
                                </p>
                                <p class="dk-sub">
                                    {{ t.label_status
                                    }}<span v-if="t.tgl_teks"> · {{ t.tgl_teks }}</span>
                                </p>
                            </div>
                            <Badge
                                :teks="t.label_status"
                                :warna="
                                    (opsi.status as any)?.[t.status] || '#6b7280'
                                "
                            />
                        </button>
                    </div>
                    <Kosong v-else teks="Proyek tanpa tahapan." ikon="▣" />
                </div>

                <!-- Tagihan -->
                <div v-show="tab === 'tagihan'" class="dk-kartu overflow-x-auto">
                    <table v-if="proyek.tagihan.length" class="dk-tabel">
                        <thead>
                            <tr>
                                <th>Tagihan</th>
                                <th>Status</th>
                                <th class="text-right">Total</th>
                                <th class="text-right">Terbayar</th>
                                <th class="text-right">Sisa</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="t in proyek.tagihan" :key="t.id">
                                <td>
                                    {{ t.judul }}
                                    <span class="dk-sub block">{{
                                        t.tgl_teks || '—'
                                    }}</span>
                                </td>
                                <td>
                                    <Badge :teks="t.label_state" :warna="t.warna_state || '#6b7280'" />
                                </td>
                                <td class="text-right tabular-nums">
                                    {{ t.total_teks }}
                                </td>
                                <td class="text-right tabular-nums">
                                    {{ t.terbayar_teks }}
                                </td>
                                <td class="text-right tabular-nums">
                                    {{ t.sisa_teks }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <Kosong v-else teks="Belum ada tagihan untuk proyek ini." ikon="▤" />
                </div>

                <!-- Aktivitas -->
                <div v-show="tab === 'aktivitas'" class="dk-kartu">
                    <header
                        class="flex items-center justify-between border-b border-gray-100 px-4 py-2.5"
                    >
                        <h2 class="text-sm font-semibold">Catatan & jejak</h2>
                        <button
                            type="button"
                            class="dk-tbl dk-tbl-kosong"
                            @click="modalAktivitas = true"
                        >
                            + Catat
                        </button>
                    </header>
                    <div v-if="proyek.aktivitas.length" class="divide-y divide-gray-50">
                        <div
                            v-for="a in proyek.aktivitas"
                            :key="a.id"
                            class="px-4 py-2.5"
                        >
                            <p class="text-sm text-gray-800">{{ a.judul }}</p>
                            <p v-if="a.catatan" class="dk-sub whitespace-pre-line">
                                {{ a.catatan }}
                            </p>
                            <p class="dk-sub mt-0.5">
                                {{ a.tgl_teks }} · {{ a.label_jenis }}
                                <span v-if="a.user"> · {{ a.user }}</span>
                            </p>
                        </div>
                    </div>
                    <Kosong v-else teks="Belum ada catatan." ikon="•" />
                </div>

                <!-- Lampiran -->
                <div v-show="tab === 'lampiran'" class="dk-kartu">
                    <header
                        class="flex items-center justify-between border-b border-gray-100 px-4 py-2.5"
                    >
                        <h2 class="text-sm font-semibold">Berkas</h2>
                        <button
                            type="button"
                            class="dk-tbl dk-tbl-kosong"
                            @click="modalLampiran = true"
                        >
                            + Unggah
                        </button>
                    </header>
                    <div v-if="proyek.lampiran.length" class="divide-y divide-gray-50">
                        <div
                            v-for="l in proyek.lampiran"
                            :key="l.id"
                            class="flex items-center justify-between gap-3 px-4 py-2.5"
                        >
                            <div class="min-w-0">
                                <p class="truncate text-sm text-gray-800">
                                    {{ l.nama }}
                                </p>
                                <p class="dk-sub">
                                    {{ l.jenis }} · {{ l.ukuran }} · {{ l.diunggah }}
                                </p>
                            </div>
                            <a
                                :href="rute.lampiranUnduh(l.id)"
                                class="dk-tbl dk-tbl-halus"
                                >↓</a
                            >
                        </div>
                    </div>
                    <Kosong v-else teks="Belum ada lampiran." ikon="⇧" />
                </div>
            </section>
        </div>

        <!-- Modal tahapan -->
        <Modal
            :buka="modalTahapan !== null"
            :judul="modalTahapan?.nama || 'Tahapan'"
            lebar="sm"
            @tutup="modalTahapan = null"
        >
            <form class="space-y-3" @submit.prevent="simpanTahapan">
                <label class="block">
                    <span class="dk-label">Status</span>
                    <select v-model="formTahapan.status" class="dk-isian">
                        <option value="belum">Belum</option>
                        <option value="jalan">Jalan</option>
                        <option value="selesai">Selesai</option>
                        <option value="dilewati">Dilewati</option>
                    </select>
                </label>
                <div class="grid grid-cols-2 gap-3">
                    <label class="block">
                        <span class="dk-label">Mulai</span>
                        <input v-model="formTahapan.tgl_mulai" type="date" class="dk-isian" />
                    </label>
                    <label class="block">
                        <span class="dk-label">Selesai</span>
                        <input
                            v-model="formTahapan.tgl_selesai"
                            type="date"
                            class="dk-isian"
                        />
                    </label>
                </div>
                <label class="block">
                    <span class="dk-label">Catatan</span>
                    <textarea v-model="formTahapan.catatan" rows="2" class="dk-isian" />
                </label>
                <div class="flex justify-end gap-2 pt-1">
                    <button
                        type="button"
                        class="dk-tbl dk-tbl-kosong"
                        @click="modalTahapan = null"
                    >
                        Batal
                    </button>
                    <button
                        type="submit"
                        class="dk-tbl dk-tbl-utama"
                        :disabled="formTahapan.processing"
                    >
                        Simpan
                    </button>
                </div>
            </form>
        </Modal>

        <!-- Modal status -->
        <Modal :buka="modalStatus" judul="Ubah status proyek" lebar="sm" @tutup="modalStatus = false">
            <form class="space-y-3" @submit.prevent="simpanStatus">
                <label class="block">
                    <span class="dk-label">Status baru</span>
                    <select v-model="formS.status" class="dk-isian">
                        <option v-for="(v, k) in opsi.status" :key="k" :value="k">
                            {{ v }}
                        </option>
                    </select>
                </label>
                <label v-if="formS.status === 'ditahan'" class="block">
                    <span class="dk-label">Alasan ditahan</span>
                    <input v-model="formS.alasan_batal" class="dk-isian" />
                </label>
                <div class="flex justify-end gap-2 pt-1">
                    <button
                        type="button"
                        class="dk-tbl dk-tbl-kosong"
                        @click="modalStatus = false"
                    >
                        Batal
                    </button>
                    <button
                        type="submit"
                        class="dk-tbl dk-tbl-utama"
                        :disabled="formS.processing"
                    >
                        Simpan
                    </button>
                </div>
            </form>
        </Modal>

        <!-- Modal aktivitas -->
        <Modal
            :buka="modalAktivitas"
            judul="Catat aktivitas"
            @tutup="modalAktivitas = false"
        >
            <form class="space-y-3" @submit.prevent="simpanAktivitas">
                <label class="block">
                    <span class="dk-label">Judul<span class="text-red-500"> *</span></span>
                    <input v-model="formA.judul" class="dk-isian" autofocus />
                </label>
                <div class="grid gap-3 sm:grid-cols-2">
                    <label class="block">
                        <span class="dk-label">Jenis</span>
                        <select v-model="formA.jenis" class="dk-isian">
                            <option
                                v-for="(v, kk) in opsi.jenis_aktivitas"
                                :key="kk"
                                :value="kk"
                            >
                                {{ v }}
                            </option>
                        </select>
                    </label>
                    <label class="block">
                        <span class="dk-label">Tanggal</span>
                        <input v-model="formA.tgl" type="date" class="dk-isian" />
                    </label>
                </div>
                <label class="block">
                    <span class="dk-label">Catatan</span>
                    <textarea v-model="formA.catatan" rows="3" class="dk-isian" />
                </label>
                <div class="flex justify-end gap-2 pt-1">
                    <button
                        type="button"
                        class="dk-tbl dk-tbl-kosong"
                        @click="modalAktivitas = false"
                    >
                        Batal
                    </button>
                    <button
                        type="submit"
                        class="dk-tbl dk-tbl-utama"
                        :disabled="formA.processing"
                    >
                        Simpan
                    </button>
                </div>
            </form>
        </Modal>

        <!-- Modal lampiran -->
        <Modal
            :buka="modalLampiran"
            judul="Unggah lampiran"
            @tutup="modalLampiran = false"
        >
            <form class="space-y-3" @submit.prevent="unggahLampiran">
                <input
                    type="file"
                    class="dk-isian"
                    accept=".pdf,.doc,.docx,.xls,.xlsx,.png,.jpg,.jpeg,.webp,.zip"
                    @change="(e: any) => (formL.berkas = e.target.files?.[0] || null)"
                />
                <span v-if="formL.errors.berkas" class="dk-galat">{{
                    formL.errors.berkas
                }}</span>
                <div class="flex justify-end gap-2">
                    <button
                        type="button"
                        class="dk-tbl dk-tbl-kosong"
                        @click="modalLampiran = false"
                    >
                        Batal
                    </button>
                    <button
                        type="submit"
                        class="dk-tbl dk-tbl-utama"
                        :disabled="formL.processing || !formL.berkas"
                    >
                        Unggah
                    </button>
                </div>
            </form>
        </Modal>
    </Tata>
</template>
