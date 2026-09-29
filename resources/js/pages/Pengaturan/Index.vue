<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import Tata from '@/components/Tata.vue';
import Kepala from '@/components/Kepala.vue';
import { rute } from '@/lib/rute';

const props = defineProps<{
    nilai: any[];
    persen_dp: number;
    persen_dp_teks: string;
    contoh_harga: string;
}>();

/** Satu form untuk semua kunci pengaturan. */
const awal: Record<string, any> = {};
for (const n of props.nilai) awal[n.kunci] = n.nilai;

const form = useForm({
    nama_perusahaan: awal.nama_perusahaan ?? '',
    email_perusahaan: awal.email_perusahaan ?? '',
    telepon_perusahaan: awal.telepon_perusahaan ?? '',
    warna_utama: awal.warna_utama ?? '#2196f3',
    dp_persen_default: awal.dp_persen_default ?? 50,
    peran_boleh_lihat_nilai: awal.peran_boleh_lihat_nilai ?? 'admin',
});

function kirim() {
    form.put(rute.pengaturan, { preserveScroll: true });
}

const grup = [
    { kunci: 'umum', label: 'Umum' },
    { kunci: 'penagihan', label: 'Penagihan' },
    { kunci: 'akses', label: 'Akses' },
];
</script>

<template>
    <Head title="Pengaturan" />
    <Tata>
        <Kepala
            judul="Pengaturan"
            sub="Identitas usaha, penagihan, dan hak akses"
        >
            <template #aksi>
                <Link :href="rute.pengguna" class="dk-tbl dk-tbl-kosong"
                    >Pengguna</Link
                >
                <Link
                    :href="rute.pengaturanTahapan"
                    class="dk-tbl dk-tbl-kosong"
                    >Template tahapan</Link
                >
            </template>
        </Kepala>

        <form class="max-w-2xl space-y-4" @submit.prevent="kirim">
            <div class="dk-kartu dk-kartu-p space-y-3">
                <h2 class="text-sm font-semibold">Identitas usaha</h2>

                <label class="block">
                    <span class="dk-label"
                        >Nama perusahaan<span class="text-red-500">
                            *</span
                        ></span
                    >
                    <input v-model="form.nama_perusahaan" class="dk-isian" />
                </label>

                <div class="grid gap-3 sm:grid-cols-2">
                    <label class="block">
                        <span class="dk-label">Email</span>
                        <input
                            v-model="form.email_perusahaan"
                            type="email"
                            class="dk-isian"
                        />
                        <span
                            v-if="form.errors.email_perusahaan"
                            class="dk-galat"
                            >{{ form.errors.email_perusahaan }}</span
                        >
                    </label>
                    <label class="block">
                        <span class="dk-label">Telepon</span>
                        <input
                            v-model="form.telepon_perusahaan"
                            class="dk-isian"
                        />
                    </label>
                </div>

                <label class="block">
                    <span class="dk-label">Warna utama</span>
                    <div class="flex items-center gap-2">
                        <input
                            v-model="form.warna_utama"
                            type="color"
                            class="h-9 w-14 cursor-pointer rounded border border-gray-300"
                        />
                        <input
                            v-model="form.warna_utama"
                            class="dk-isian max-w-[9rem]"
                        />
                    </div>
                    <span v-if="form.errors.warna_utama" class="dk-galat">{{
                        form.errors.warna_utama
                    }}</span>
                </label>
            </div>

            <div class="dk-kartu dk-kartu-p space-y-3">
                <h2 class="text-sm font-semibold">Penagihan</h2>

                <label class="block">
                    <span class="dk-label">DP default (%)</span>
                    <input
                        v-model="form.dp_persen_default"
                        type="number"
                        min="0"
                        max="100"
                        class="dk-isian max-w-[8rem]"
                    />
                    <span class="mt-1 block text-xs text-gray-400">
                        Nilai kontrak {{ contoh_harga }} → DP
                        {{
                            (
                                (Number(contoh_harga.replace(/[^0-9]/g, '')) *
                                    Number(form.dp_persen_default)) /
                                100
                            ).toLocaleString('id-ID')
                        }}
                    </span>
                </label>

                <label class="block">
                    <span class="dk-label"
                        >Catatan rekening (tampil di tagihan)</span
                    >
                    <input
                        :value="
                            nilai.find((n) => n.kunci === 'rekening_bank')
                                ?.nilai
                        "
                        class="dk-isian"
                        readonly
                    />
                    <span class="mt-1 block text-xs text-gray-400"
                        >Diisi langsung di tabel pengaturan; kolom formulir
                        rekening menyusul bersama ekspor tagihan.</span
                    >
                </label>
            </div>

            <div class="dk-kartu dk-kartu-p space-y-3">
                <h2 class="text-sm font-semibold">Akses</h2>
                <label class="block">
                    <span class="dk-label"
                        >Siapa yang boleh melihat nilai kontrak</span
                    >
                    <select
                        v-model="form.peran_boleh_lihat_nilai"
                        class="dk-isian max-w-xs"
                    >
                        <option value="admin">Hanya admin</option>
                        <option value="staf">Admin & staf</option>
                        <option value="semua">Semua pengguna</option>
                    </select>
                </label>
            </div>

            <div class="flex justify-end">
                <button
                    type="submit"
                    class="dk-tbl dk-tbl-utama"
                    :disabled="form.processing"
                >
                    {{ form.processing ? 'Menyimpan…' : 'Simpan pengaturan' }}
                </button>
            </div>
        </form>
    </Tata>
</template>
