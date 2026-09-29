<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import Tata from '@/components/Tata.vue';
import Kepala from '@/components/Kepala.vue';
import { rute } from '@/lib/rute';

const props = defineProps<{
    proyek: any;
    awal: any;
    opsi: any;
}>();

const baru = computed(() => props.proyek === null);

const form = useForm({
    klien_id: props.proyek?.klien_id ?? props.awal?.klien_id ?? '',
    nama: props.proyek?.nama ?? '',
    jenis: props.proyek?.jenis ?? 'website',
    deskripsi: props.proyek?.deskripsi ?? '',
    status: props.proyek?.status ?? 'penawaran',
    nilai_kontrak: props.proyek?.nilai_kontrak ?? 0,
    dp_nominal: props.proyek?.dp_nominal ?? 0,
    tgl_mulai: props.proyek?.tgl_mulai ?? '',
    tgl_target: props.proyek?.tgl_target ?? '',
    pemilik_id: props.proyek?.pemilik_id ?? '',
    catatan: props.proyek?.catatan ?? '',
});

const dpDisarankan = computed(() => {
    const nilai = Number(form.nilai_kontrak) || 0;

    return Math.round(nilai * 0.5);
});

function pakaiDpDisarankan() {
    form.dp_nominal = dpDisarankan.value;
}

function kirim() {
    if (baru.value) {
        form.post(rute.proyek, { preserveScroll: true });
    } else {
        form.put(`/proyek/${props.proyek.id}`, { preserveScroll: true });
    }
}

const template = computed(() => props.opsi.template?.[form.jenis] || []);
</script>

<template>
    <Head :title="baru ? 'Proyek baru' : `Ubah ${proyek.nama}`" />
    <Tata>
        <Kepala
            :judul="baru ? 'Proyek baru' : 'Ubah proyek'"
            :sub="
                baru
                    ? 'Tahapan bawaan otomatis disalin sesuai jenis proyek.'
                    : proyek.nama
            "
            :kembali="baru ? rute.proyek : rute.proyekLihat(proyek.id)"
            :kembali-teks="baru ? 'Daftar proyek' : 'Halaman proyek'"
        />

        <form class="max-w-3xl space-y-4" @submit.prevent="kirim">
            <div class="dk-kartu dk-kartu-p grid gap-3 sm:grid-cols-2">
                <label class="block sm:col-span-2">
                    <span class="dk-label">Nama proyek<span class="text-red-500"> *</span></span>
                    <input
                        v-model="form.nama"
                        class="dk-isian"
                        placeholder="Website company profile PT Maju"
                        autofocus
                    />
                    <span v-if="form.errors.nama" class="dk-galat">{{
                        form.errors.nama
                    }}</span>
                </label>

                <label class="block">
                    <span class="dk-label">Klien<span class="text-red-500"> *</span></span>
                    <select v-model="form.klien_id" class="dk-isian">
                        <option value="">— pilih klien —</option>
                        <option v-for="k in opsi.klien" :key="k.id" :value="k.id">
                            {{ k.nama }}
                        </option>
                    </select>
                    <span v-if="form.errors.klien_id" class="dk-galat">{{
                        form.errors.klien_id
                    }}</span>
                </label>

                <label class="block">
                    <span class="dk-label">Jenis<span class="text-red-500"> *</span></span>
                    <select v-model="form.jenis" class="dk-isian">
                        <option v-for="(v, k) in opsi.jenis" :key="k" :value="k">
                            {{ v }}
                        </option>
                    </select>
                </label>

                <label class="block">
                    <span class="dk-label">Nilai kontrak<span class="text-red-500"> *</span></span>
                    <input
                        v-model="form.nilai_kontrak"
                        type="number"
                        min="0"
                        step="1000"
                        class="dk-isian"
                    />
                    <span v-if="form.errors.nilai_kontrak" class="dk-galat">{{
                        form.errors.nilai_kontrak
                    }}</span>
                </label>

                <label class="block">
                    <span class="dk-label">DP</span>
                    <input
                        v-model="form.dp_nominal"
                        type="number"
                        min="0"
                        step="1000"
                        class="dk-isian"
                    />
                    <button
                        v-if="baru && form.nilai_kontrak > 0"
                        type="button"
                        class="dk-sub mt-1 hover:text-blue-600"
                        @click="pakaiDpDisarankan"
                    >
                        Pakai 50% ({{ dpDisarankan.toLocaleString('id-ID') }})
                    </button>
                </label>

                <label class="block">
                    <span class="dk-label">Status</span>
                    <select v-model="form.status" class="dk-isian">
                        <option v-for="(v, k) in opsi.status" :key="k" :value="k">
                            {{ v }}
                        </option>
                    </select>
                </label>

                <label class="block">
                    <span class="dk-label">Penanggung jawab</span>
                    <select v-model="form.pemilik_id" class="dk-isian">
                        <option value="">— belum ditentukan —</option>
                        <option v-for="s in opsi.staf" :key="s.id" :value="s.id">
                            {{ s.name }}
                        </option>
                    </select>
                </label>

                <label class="block">
                    <span class="dk-label">Tanggal mulai</span>
                    <input v-model="form.tgl_mulai" type="date" class="dk-isian" />
                </label>

                <label class="block">
                    <span class="dk-label">Target selesai</span>
                    <input v-model="form.tgl_target" type="date" class="dk-isian" />
                </label>

                <label class="block sm:col-span-2">
                    <span class="dk-label">Deskripsi singkat</span>
                    <textarea v-model="form.deskripsi" rows="2" class="dk-isian" />
                </label>

                <label class="block sm:col-span-2">
                    <span class="dk-label">Catatan internal</span>
                    <textarea v-model="form.catatan" rows="2" class="dk-isian" />
                </label>
            </div>

            <div v-if="baru && template.length" class="dk-kartu dk-kartu-p">
                <p class="dk-label">
                    Tahapan yang akan dibuat ({{ template.length }})
                </p>
                <ol class="mt-1 space-y-0.5 text-sm text-gray-600">
                    <li v-for="t in template" :key="t.id">
                        {{ t.urutan }}. {{ t.nama }}
                    </li>
                </ol>
            </div>

            <div class="flex justify-end gap-2">
                <Link
                    :href="baru ? rute.proyek : rute.proyekLihat(proyek.id)"
                    class="dk-tbl dk-tbl-kosong"
                    >Batal</Link
                >
                <button
                    type="submit"
                    class="dk-tbl dk-tbl-utama"
                    :disabled="form.processing"
                >
                    {{ form.processing ? 'Menyimpan…' : 'Simpan' }}
                </button>
            </div>
        </form>
    </Tata>
</template>
