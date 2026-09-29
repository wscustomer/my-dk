<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import Tata from '@/components/Tata.vue';
import Kepala from '@/components/Kepala.vue';
import Badge from '@/components/Badge.vue';
import Modal from '@/components/Modal.vue';
import { rute } from '@/lib/rute';

const props = defineProps<{
    daftar: any[];
    peran: any;
}>();

const modal = ref(false);
const form = useForm({
    name: '',
    email: '',
    password: '',
    peran: 'staf',
});

function buka() {
    form.reset();
    modal.value = true;
}

function simpan() {
    form.post(rute.pengguna, {
        preserveScroll: true,
        onSuccess: () => {
            modal.value = false;
            form.reset();
        },
    });
}
</script>

<template>
    <Head title="Pengguna" />
    <Tata>
        <Kepala
            judul="Pengguna"
            sub="Admin bisa semua; staf tanpa pengaturan & tanpa lihat nilai kontrak"
            :kembali="rute.pengaturan"
            kembali-teks="Pengaturan"
        >
            <template #aksi>
                <button type="button" class="dk-tbl dk-tbl-utama" @click="buka">
                    + Pengguna
                </button>
            </template>
        </Kepala>

        <div class="dk-kartu overflow-x-auto">
            <table class="dk-tabel">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Email</th>
                        <th>Peran</th>
                        <th>Status</th>
                        <th>Dibuat</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="u in daftar" :key="u.id">
                        <td>
                            <span class="font-medium text-gray-900">{{ u.name }}</span>
                        </td>
                        <td class="text-gray-600">{{ u.email }}</td>
                        <td>
                            <Badge
                                :teks="u.label_peran"
                                :warna="u.peran === 'admin' ? '#2563eb' : '#6b7280'"
                            />
                        </td>
                        <td>
                            <span v-if="u.aktif" class="text-green-700">aktif</span>
                            <span v-else class="text-gray-400">nonaktif</span>
                        </td>
                        <td class="dk-sub">{{ u.dibuat }}</td>
                        <td class="text-right">
                            <Link
                                :href="rute.penggunaUbah(u.id)"
                                class="dk-tbl dk-tbl-halus"
                                >Ubah</Link
                            >
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <Modal :buka="modal" judul="Pengguna baru" lebar="sm" @tutup="modal = false">
            <form class="space-y-3" @submit.prevent="simpan">
                <label class="block">
                    <span class="dk-label">Nama<span class="text-red-500"> *</span></span>
                    <input v-model="form.name" class="dk-isian" autofocus />
                    <span v-if="form.errors.name" class="dk-galat">{{
                        form.errors.name
                    }}</span>
                </label>
                <label class="block">
                    <span class="dk-label">Email<span class="text-red-500"> *</span></span>
                    <input v-model="form.email" type="email" class="dk-isian" />
                    <span v-if="form.errors.email" class="dk-galat">{{
                        form.errors.email
                    }}</span>
                </label>
                <label class="block">
                    <span class="dk-label"
                        >Kata sandi<span class="text-red-500"> *</span></span
                    >
                    <input v-model="form.password" type="password" class="dk-isian" />
                    <span class="mt-1 block text-xs text-gray-400">Minimal 10 karakter.</span>
                    <span v-if="form.errors.password" class="dk-galat">{{
                        form.errors.password
                    }}</span>
                </label>
                <label class="block">
                    <span class="dk-label">Peran</span>
                    <select v-model="form.peran" class="dk-isian">
                        <option v-for="(v, k) in peran" :key="k" :value="k">{{ v }}</option>
                    </select>
                </label>
                <div class="flex justify-end gap-2 pt-1">
                    <button
                        type="button"
                        class="dk-tbl dk-tbl-kosong"
                        @click="modal = false"
                    >
                        Batal
                    </button>
                    <button
                        type="submit"
                        class="dk-tbl dk-tbl-utama"
                        :disabled="form.processing"
                    >
                        Simpan
                    </button>
                </div>
            </form>
        </Modal>
    </Tata>
</template>
