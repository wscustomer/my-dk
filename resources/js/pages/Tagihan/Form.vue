<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import Tata from '@/components/Tata.vue';
import Kepala from '@/components/Kepala.vue';
import { rute } from '@/lib/rute';

const props = defineProps<{
    tagihan: any;
    awal: any;
    opsi: any;
}>();

const baru = computed(() => props.tagihan === null);

const form = useForm({
    klien_id: props.tagihan?.klien_id ?? props.awal?.klien_id ?? '',
    proyek_id: props.tagihan?.proyek_id ?? props.awal?.proyek_id ?? '',
    judul: props.tagihan?.judul ?? '',
    total: props.tagihan?.total ?? 0,
    tgl_terbit: props.tagihan?.tgl_terbit ?? '',
    tgl_jatuh_tempo: props.tagihan?.tgl_jatuh_tempo ?? '',
    status: props.tagihan?.status ?? 'draft',
    catatan: props.tagihan?.catatan ?? '',
});

/** Proyek disaring sesuai klien terpilih. */
const proyekKlien = computed(() =>
    props.opsi.proyek.filter(
        (p: any) => !form.klien_id || p.klien_id === Number(form.klien_id),
    ),
);

function pilihProyek() {
    const p = props.opsi.proyek.find((x: any) => x.id === Number(form.proyek_id));
    if (p && p.nilai_kontrak > 0 && !form.total) {
        form.total = Number(p.nilai_kontrak) / 2;
    }
}

function kirim() {
    if (baru.value) {
        form.post(rute.tagihan, { preserveScroll: true });
    } else {
        form.put(`/tagihan/${props.tagihan.id}`, { preserveScroll: true });
    }
}
</script>

<template>
    <Head :title="baru ? 'Tagihan baru' : `Ubah ${tagihan.judul}`" />
    <Tata>
        <Kepala
            :judul="baru ? 'Tagihan baru' : 'Ubah tagihan'"
            :sub="
                baru
                    ? 'Tagihan CRM bersifat internal. Penagihan resmi tetap di WSCRM.'
                    : tagihan.judul
            "
            :kembali="rute.tagihan"
            kembali-teks="Daftar tagihan"
        />

        <form class="max-w-2xl space-y-4" @submit.prevent="kirim">
            <div class="dk-kartu dk-kartu-p grid gap-3 sm:grid-cols-2">
                <label class="block sm:col-span-2">
                    <span class="dk-label">Judul<span class="text-red-500"> *</span></span>
                    <input
                        v-model="form.judul"
                        class="dk-isian"
                        placeholder="DP 50% website company profile"
                        autofocus
                    />
                    <span v-if="form.errors.judul" class="dk-galat">{{
                        form.errors.judul
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
                    <span class="dk-label">Proyek</span>
                    <select v-model="form.proyek_id" class="dk-isian" @change="pilihProyek">
                        <option value="">— tanpa proyek —</option>
                        <option v-for="p in proyekKlien" :key="p.id" :value="p.id">
                            {{ p.nama }}
                        </option>
                    </select>
                </label>

                <label class="block">
                    <span class="dk-label">Total<span class="text-red-500"> *</span></span>
                    <input
                        v-model="form.total"
                        type="number"
                        min="0"
                        step="1000"
                        class="dk-isian"
                    />
                    <span v-if="form.errors.total" class="dk-galat">{{
                        form.errors.total
                    }}</span>
                </label>

                <label class="block">
                    <span class="dk-label">Status</span>
                    <select v-model="form.status" class="dk-isian">
                        <option value="draft">Draft (belum ditagih)</option>
                        <option value="terkirim">Terkirim (mulai dihitung piutang)</option>
                        <option value="batal">Batal</option>
                    </select>
                </label>

                <label class="block">
                    <span class="dk-label">Tanggal terbit</span>
                    <input v-model="form.tgl_terbit" type="date" class="dk-isian" />
                </label>

                <label class="block">
                    <span class="dk-label">Jatuh tempo</span>
                    <input v-model="form.tgl_jatuh_tempo" type="date" class="dk-isian" />
                </label>

                <label class="block sm:col-span-2">
                    <span class="dk-label">Catatan</span>
                    <textarea v-model="form.catatan" rows="2" class="dk-isian" />
                </label>
            </div>

            <div class="flex justify-end gap-2">
                <Link :href="rute.tagihan" class="dk-tbl dk-tbl-kosong">Batal</Link>
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
