<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import Tata from '@/components/Tata.vue';
import Kepala from '@/components/Kepala.vue';
import { rute } from '@/lib/rute';

const props = defineProps<{
    klien: any;
    opsi: any;
    kembali: string | null;
}>();

const baru = computed(() => props.klien === null);

const form = useForm({
    nama: props.klien?.nama ?? '',
    perusahaan: props.klien?.perusahaan ?? '',
    email: props.klien?.email ?? '',
    telepon: props.klien?.telepon ?? '',
    alamat: props.klien?.alamat ?? '',
    kota: props.klien?.kota ?? '',
    catatan: props.klien?.catatan ?? '',
    status: props.klien?.status ?? 'prospek',
    sumber: props.klien?.sumber ?? 'manual',
    sumber_lain: props.klien?.sumber_lain ?? '',
    aktif: props.klien?.aktif ?? true,
});

function kirim() {
    if (baru.value) {
        form.post(rute.klien, { preserveScroll: true });
    } else {
        form.put(`/klien/${props.klien.id}`, { preserveScroll: true });
    }
}
</script>

<template>
    <Head :title="baru ? 'Klien baru' : `Ubah ${klien.nama}`" />
    <Tata>
        <Kepala
            :judul="baru ? 'Klien baru' : 'Ubah klien'"
            :sub="baru ? 'Isi data pokok. Detail bisa ditambah nanti.' : klien.nama"
            :kembali="kembali || (baru ? rute.klien : rute.klienDetail(klien.id))"
            :kembali-teks="baru ? 'Daftar klien' : 'Halaman klien'"
        />

        <form class="max-w-3xl space-y-4" @submit.prevent="kirim">
            <div class="dk-kartu dk-kartu-p grid gap-3 sm:grid-cols-2">
                <label class="block sm:col-span-2">
                    <span class="dk-label">Nama<span class="text-red-500"> *</span></span>
                    <input v-model="form.nama" class="dk-isian" autofocus />
                    <span v-if="form.errors.nama" class="dk-galat">{{
                        form.errors.nama
                    }}</span>
                </label>

                <label class="block">
                    <span class="dk-label">Perusahaan</span>
                    <input v-model="form.perusahaan" class="dk-isian" />
                </label>

                <label class="block">
                    <span class="dk-label">Kota</span>
                    <input v-model="form.kota" class="dk-isian" />
                </label>

                <label class="block">
                    <span class="dk-label">Email</span>
                    <input v-model="form.email" type="email" class="dk-isian" />
                    <span v-if="form.errors.email" class="dk-galat">{{
                        form.errors.email
                    }}</span>
                </label>

                <label class="block">
                    <span class="dk-label">Telepon / WhatsApp</span>
                    <input
                        v-model="form.telepon"
                        class="dk-isian"
                        placeholder="0812…"
                    />
                </label>

                <label class="block sm:col-span-2">
                    <span class="dk-label">Alamat</span>
                    <input v-model="form.alamat" class="dk-isian" />
                </label>

                <label class="block">
                    <span class="dk-label">Status</span>
                    <select v-model="form.status" class="dk-isian">
                        <option v-for="(v, k) in opsi.status" :key="k" :value="k">
                            {{ (v as string[])[0] }}
                        </option>
                    </select>
                </label>

                <label class="block">
                    <span class="dk-label">Sumber</span>
                    <select v-model="form.sumber" class="dk-isian">
                        <option v-for="(v, k) in opsi.sumber" :key="k" :value="k">
                            {{ v }}
                        </option>
                    </select>
                </label>

                <label v-if="form.sumber === 'lain'" class="block sm:col-span-2">
                    <span class="dk-label">Sumber lain-lain</span>
                    <input v-model="form.sumber_lain" class="dk-isian" />
                </label>

                <label class="block sm:col-span-2">
                    <span class="dk-label">Catatan</span>
                    <textarea v-model="form.catatan" rows="3" class="dk-isian" />
                </label>

                <label class="flex items-center gap-2 text-sm text-gray-700 sm:col-span-2">
                    <input v-model="form.aktif" type="checkbox" />
                    Klien aktif (tampil di pemilih proyek)
                </label>
            </div>

            <div class="flex justify-end gap-2">
                <Link
                    :href="
                        kembali || (baru ? rute.klien : rute.klienDetail(klien.id))
                    "
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
