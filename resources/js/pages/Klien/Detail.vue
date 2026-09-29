<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import Tata from '@/components/Tata.vue';
import Kepala from '@/components/Kepala.vue';
import Badge from '@/components/Badge.vue';
import Kosong from '@/components/Kosong.vue';
import Modal from '@/components/Modal.vue';
import { rute, hariIni } from '@/lib/rute';

const props = defineProps<{
    klien: any;
    opsi: any;
}>();

const k = computed(() => props.klien);
const tab = ref('proyek');
const modalAktivitas = ref(false);
const modalLampiran = ref(false);

const tabs = [
    { kunci: 'proyek', label: 'Proyek' },
    { kunci: 'tagihan', label: 'Tagihan' },
    { kunci: 'aktivitas', label: 'Aktivitas & catatan' },
    { kunci: 'lampiran', label: 'Lampiran' },
];

const formA = useForm({
    judul: '',
    jenis: 'catatan',
    tgl: hariIni(),
    catatan: '',
    proyek_id: '' as string | number,
});

function simpanAktivitas() {
    formA.post(rute.klienAktivitas(k.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            modalAktivitas.value = false;
            formA.reset('judul', 'catatan');
        },
    });
}

const formL = useForm<{ berkas: File | null }>({ berkas: null });

function unggahLampiran() {
    formL.post(rute.klienLampiran(k.value.id), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            modalLampiran.value = false;
            formL.reset('berkas');
        },
    });
}

function hapusLampiran(l: any) {
    if (!confirm(`Hapus lampiran "${l.nama}"?`)) return;
    router.delete(rute.lampiranUnduh(l.id).replace('/unduh', ''), { preserveScroll: true });
}

function hapusKlien() {
    if (!confirm(`Hapus klien "${k.value.nama}"? Tindakan ini permanen.`)) return;
    router.delete(`/klien/${k.value.id}`);
}
</script>

<template>
    <Head :title="klien.nama" />
    <Tata>
        <Kepala
            :judul="klien.nama"
            :sub="[klien.kode, klien.perusahaan, klien.kota].filter(Boolean).join(' · ')"
            :kembali="rute.klien"
            kembali-teks="Daftar klien"
        >
            <template #aksi>
                <Badge
                    :teks="klien.label_status"
                    :warna="(opsi.status[klien.status] as string[])?.[1]"
                />
                <a
                    v-if="klien.whatsapp"
                    :href="klien.whatsapp"
                    target="_blank"
                    rel="noopener"
                    class="dk-tbl dk-tbl-kosong"
                    >WhatsApp</a
                >
                <Link :href="rute.klienUbah(klien.id)" class="dk-tbl dk-tbl-kosong"
                    >Ubah</Link
                >
            </template>
        </Kepala>

        <div class="grid gap-4 lg:grid-cols-4">
            <!-- Kiri: ringkas + kontak -->
            <aside class="space-y-4 lg:col-span-1">
                <div class="dk-kartu dk-kartu-p space-y-2.5 text-sm">
                    <div>
                        <p class="dk-label">Nilai kontrak seumur</p>
                        <p class="text-lg font-semibold tabular-nums text-gray-900">
                            {{ klien.nilai_lifetime }}
                        </p>
                    </div>
                    <div>
                        <p class="dk-label">Email</p>
                        <p class="break-all">{{ klien.email || '—' }}</p>
                    </div>
                    <div>
                        <p class="dk-label">Telepon</p>
                        <p>{{ klien.telepon || '—' }}</p>
                    </div>
                    <div>
                        <p class="dk-label">Alamat</p>
                        <p>{{ klien.alamat || '—' }}</p>
                    </div>
                    <div>
                        <p class="dk-label">Sumber</p>
                        <p>
                            {{ (opsi.sumber[klien.sumber] as string) || klien.sumber }}
                            <span v-if="klien.sumber_lain" class="dk-sub"
                                >({{ klien.sumber_lain }})</span
                            >
                        </p>
                    </div>
                    <div>
                        <p class="dk-label">Klien sejak</p>
                        <p>{{ klien.dibuat }}</p>
                    </div>
                    <div v-if="klien.catatan">
                        <p class="dk-label">Catatan</p>
                        <p class="whitespace-pre-line text-gray-700">
                            {{ klien.catatan }}
                        </p>
                    </div>
                </div>

                <div class="dk-kartu dk-kartu-p">
                    <p class="dk-label mb-2">Tindakan</p>
                    <div class="flex flex-col gap-1.5">
                        <Link
                            :href="rute.proyekBaru + '?klien=' + klien.id"
                            class="dk-tbl dk-tbl-kosong justify-center"
                            >+ Proyek untuk klien ini</Link
                        >
                        <Link
                            :href="rute.tagihanBaru + '?klien=' + klien.id"
                            class="dk-tbl dk-tbl-kosong justify-center"
                            >+ Tagihan</Link
                        >
                        <button
                            type="button"
                            class="dk-tbl dk-tbl-bahaya justify-center"
                            @click="hapusKlien"
                        >
                            Hapus klien
                        </button>
                    </div>
                </div>
            </aside>

            <!-- Kanan: tab -->
            <section class="lg:col-span-3">
                <div
                    class="mb-3 flex flex-wrap items-center gap-1 border-b border-gray-200"
                >
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
                                t.kunci === 'proyek'
                                    ? klien.proyek.length
                                    : t.kunci === 'tagihan'
                                      ? klien.tagihan.length
                                      : t.kunci === 'aktivitas'
                                        ? klien.aktivitas.length
                                        : klien.lampiran.length
                            }}
                        </span>
                    </button>
                </div>

                <!-- Proyek -->
                <div v-show="tab === 'proyek'" class="dk-kartu">
                    <div v-if="klien.proyek.length" class="divide-y divide-gray-50">
                        <Link
                            v-for="p in klien.proyek"
                            :key="p.id"
                            :href="rute.proyekLihat(p.id)"
                            class="block px-4 py-3 hover:bg-gray-50"
                        >
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <span class="text-sm font-medium text-gray-900">{{
                                    p.nama
                                }}</span>
                                <Badge :teks="p.label_status" warna="#6b7280" />
                            </div>
                            <p class="dk-sub mt-0.5">
                                {{ p.label_jenis }} · {{ p.nilai_teks }} ·
                                {{ p.persen }}%
                            </p>
                            <div class="dk-progress mt-1.5">
                                <span :style="{ width: p.persen + '%' }" />
                            </div>
                        </Link>
                    </div>
                    <Kosong v-else teks="Klien ini belum punya proyek." ikon="▣" />
                </div>

                <!-- Tagihan -->
                <div v-show="tab === 'tagihan'" class="dk-kartu overflow-x-auto">
                    <table v-if="klien.tagihan.length" class="dk-tabel">
                        <thead>
                            <tr>
                                <th>Tagihan</th>
                                <th>Status</th>
                                <th class="text-right">Total</th>
                                <th class="text-right">Sisa</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="t in klien.tagihan" :key="t.id">
                                <td>
                                    <span class="text-gray-900">{{ t.judul }}</span>
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
                                    {{ t.sisa_teks }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <Kosong v-else teks="Belum ada tagihan." ikon="▤" />
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
                    <div v-if="klien.aktivitas.length" class="divide-y divide-gray-50">
                        <div
                            v-for="a in klien.aktivitas"
                            :key="a.id"
                            class="flex gap-3 px-4 py-2.5"
                        >
                            <span class="w-4 shrink-0 text-center text-gray-400">{{
                                '</span>'
                            }}</span>
                            <div class="min-w-0 flex-1">
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
                    <div v-if="klien.lampiran.length" class="divide-y divide-gray-50">
                        <div
                            v-for="l in klien.lampiran"
                            :key="l.id"
                            class="flex items-center justify-between gap-3 px-4 py-2.5"
                        >
                            <div class="min-w-0">
                                <p class="truncate text-sm text-gray-800">
                                    {{ l.nama }}
                                </p>
                                <p class="dk-sub">
                                    {{ l.jenis }} · {{ l.ukuran }} ·
                                    {{ l.diunggah }}
                                </p>
                            </div>
                            <div class="flex shrink-0 gap-1">
                                <a
                                    :href="rute.lampiranUnduh(l.id)"
                                    class="dk-tbl dk-tbl-halus"
                                    >↓</a
                                >
                                <button
                                    type="button"
                                    class="dk-tbl dk-tbl-halus text-red-600"
                                    @click="hapusLampiran(l)"
                                >
                                    ✕
                                </button>
                            </div>
                        </div>
                    </div>
                    <Kosong v-else teks="Belum ada lampiran." ikon="⇧" />
                </div>
            </section>
        </div>

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
                    <span v-if="formA.errors.judul" class="dk-galat">{{
                        formA.errors.judul
                    }}</span>
                </label>
                <div class="grid gap-3 sm:grid-cols-2">
                    <label class="block">
                        <span class="dk-label">Jenis</span>
                        <select v-model="formA.jenis" class="dk-isian">
                            <option v-for="(v, kk) in opsi.jenis_aktivitas" :key="kk" :value="kk">
                                {{ v }}
                            </option>
                        </select>
                    </label>
                    <label class="block">
                        <span class="dk-label">Tanggal</span>
                        <input v-model="formA.tgl" type="date" class="dk-isian" />
                    </label>
                </div>
                <label v-if="opsi.proyek.length" class="block">
                    <span class="dk-label">Kaitkan ke proyek</span>
                    <select v-model="formA.proyek_id" class="dk-isian">
                        <option value="">— tidak dikaitkan —</option>
                        <option v-for="p in opsi.proyek" :key="p.id" :value="p.id">
                            {{ p.nama }}
                        </option>
                    </select>
                </label>
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
                <label class="block">
                    <span class="dk-label">Berkas<span class="text-red-500"> *</span></span>
                    <input
                        type="file"
                        class="dk-isian"
                        accept=".pdf,.doc,.docx,.xls,.xlsx,.png,.jpg,.jpeg,.webp,.zip"
                        @change="
                            (e: any) => (formL.berkas = e.target.files?.[0] || null)
                        "
                    />
                    <span class="mt-1 block text-xs text-gray-400"
                        >PDF, dokumen, gambar, atau zip. Maks 10 MB.</span
                    >
                    <span v-if="formL.errors.berkas" class="dk-galat">{{
                        formL.errors.berkas
                    }}</span>
                </label>
                <div class="flex justify-end gap-2 pt-1">
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
