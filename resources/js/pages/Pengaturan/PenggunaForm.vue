<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import Tata from '@/components/Tata.vue';
import Kepala from '@/components/Kepala.vue';
import { rute } from '@/lib/rute';

const props = defineProps<{
    user: any;
    peran: any;
}>();

const form = useForm({
    name: props.user.name,
    email: props.user.email,
    password: '',
    peran: props.user.peran,
    aktif: props.user.aktif,
});

function kirim() {
    form.put(`/pengaturan/pengguna/${props.user.id}`, { preserveScroll: true });
}
</script>

<template>
    <Head :title="`Ubah ${user.name}`" />
    <Tata>
        <Kepala
            judul="Ubah pengguna"
            :sub="user.email"
            :kembali="rute.pengguna"
            kembali-teks="Daftar pengguna"
        />

        <form class="max-w-lg space-y-4" @submit.prevent="kirim">
            <div class="dk-kartu dk-kartu-p space-y-3">
                <label class="block">
                    <span class="dk-label"
                        >Nama<span class="text-red-500"> *</span></span
                    >
                    <input v-model="form.name" class="dk-isian" />
                    <span v-if="form.errors.name" class="dk-galat">{{
                        form.errors.name
                    }}</span>
                </label>
                <label class="block">
                    <span class="dk-label"
                        >Email<span class="text-red-500"> *</span></span
                    >
                    <input v-model="form.email" type="email" class="dk-isian" />
                    <span v-if="form.errors.email" class="dk-galat">{{
                        form.errors.email
                    }}</span>
                </label>
                <label class="block">
                    <span class="dk-label">Kata sandi baru</span>
                    <input
                        v-model="form.password"
                        type="password"
                        class="dk-isian"
                        placeholder="Kosongkan bila tidak diganti"
                    />
                    <span v-if="form.errors.password" class="dk-galat">{{
                        form.errors.password
                    }}</span>
                </label>
                <label class="block">
                    <span class="dk-label">Peran</span>
                    <select v-model="form.peran" class="dk-isian">
                        <option v-for="(v, k) in peran" :key="k" :value="k">
                            {{ v }}
                        </option>
                    </select>
                </label>
                <label class="flex items-center gap-2 text-sm text-gray-700">
                    <input v-model="form.aktif" type="checkbox" />
                    Aktif (boleh masuk)
                </label>
            </div>

            <div class="flex justify-end gap-2">
                <Link :href="rute.pengguna" class="dk-tbl dk-tbl-kosong"
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
