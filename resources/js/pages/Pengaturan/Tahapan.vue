<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import Tata from '@/components/Tata.vue';
import Kepala from '@/components/Kepala.vue';
import Modal from '@/components/Modal.vue';
import { rute } from '@/lib/rute';

const props = defineProps<{
    tahapan: any[];
    jenis: any;
}>();

const modal = ref(false);
const sunting = ref<any>(null);

const form = useForm({
    id: null as number | null,
    jenis: 'website',
    urutan: 1,
    nama: '',
    aktif: true,
});

function bukaBaru(jenis: string) {
    sunting.value = null;
    form.id = null;
    form.jenis = jenis;
    form.urutan =
        Math.max(
            0,
            ...props.tahapan
                .filter((t) => t.jenis === jenis)
                .map((t) => t.urutan),
        ) + 1;
    form.nama = '';
    form.aktif = true;
    modal.value = true;
}

function bukaUbah(t: any) {
    sunting.value = t;
    form.id = t.id;
    form.jenis = t.jenis;
    form.urutan = t.urutan;
    form.nama = t.nama;
    form.aktif = t.aktif;
    modal.value = true;
}

function simpan() {
    form.post(rute.pengaturanTahapan, {
        preserveScroll: true,
        onSuccess: () => (modal.value = false),
    });
}

function hapus(t: any) {
    if (!confirm(`Hapus tahapan "${t.nama}" dari template ${t.label_jenis}?`))
        return;
    useForm({ hapus: t.id }).post(rute.pengaturanTahapan, {
        preserveScroll: true,
    });
}

function kelompok(jenis: string) {
    return props.tahapan
        .filter((t) => t.jenis === jenis)
        .sort((a, b) => a.urutan - b.urutan);
}
</script>

<template>
    <Head title="Template tahapan" />
    <Tata>
        <Kepala
            judul="Template tahapan"
            sub="Tahapan ini disalin ke proyek baru sesuai jenisnya"
            :kembali="rute.pengaturan"
            kembali-teks="Pengaturan"
        />

        <div class="grid gap-4 lg:grid-cols-3">
            <section
                v-for="(label, kode) in jenis"
                :key="kode"
                class="dk-kartu"
            >
                <header
                    class="flex items-center justify-between border-b border-gray-100 px-4 py-2.5"
                >
                    <h2 class="text-sm font-semibold">{{ label }}</h2>
                    <button
                        type="button"
                        class="dk-tbl dk-tbl-halus"
                        @click="bukaBaru(String(kode))"
                    >
                        +
                    </button>
                </header>
                <ol class="divide-y divide-gray-50">
                    <li
                        v-for="t in kelompok(String(kode))"
                        :key="t.id"
                        class="flex items-center gap-2 px-4 py-2"
                    >
                        <span class="dk-sub w-5 shrink-0 tabular-nums"
                            >{{ t.urutan }}.</span
                        >
                        <span
                            class="min-w-0 flex-1 truncate text-sm"
                            :class="
                                t.aktif
                                    ? 'text-gray-800'
                                    : 'text-gray-400 line-through'
                            "
                            >{{ t.nama }}</span
                        >
                        <button
                            type="button"
                            class="dk-tbl dk-tbl-halus"
                            @click="bukaUbah(t)"
                        >
                            ✎
                        </button>
                        <button
                            type="button"
                            class="dk-tbl dk-tbl-halus text-red-600"
                            @click="hapus(t)"
                        >
                            ✕
                        </button>
                    </li>
                    <li
                        v-if="!kelompok(String(kode)).length"
                        class="dk-kosong !py-6"
                    >
                        Belum ada tahapan.
                    </li>
                </ol>
            </section>
        </div>

        <Modal
            :buka="modal"
            :judul="sunting ? 'Ubah tahapan' : 'Tahapan baru'"
            lebar="sm"
            @tutup="modal = false"
        >
            <form class="space-y-3" @submit.prevent="simpan">
                <label class="block">
                    <span class="dk-label">Jenis proyek</span>
                    <select v-model="form.jenis" class="dk-isian">
                        <option v-for="(v, k) in jenis" :key="k" :value="k">
                            {{ v }}
                        </option>
                    </select>
                </label>
                <label class="block">
                    <span class="dk-label"
                        >Nama tahapan<span class="text-red-500"> *</span></span
                    >
                    <input v-model="form.nama" class="dk-isian" autofocus />
                </label>
                <label class="block">
                    <span class="dk-label">Urutan</span>
                    <input
                        v-model="form.urutan"
                        type="number"
                        min="1"
                        max="60"
                        class="dk-isian max-w-[6rem]"
                    />
                </label>
                <label class="flex items-center gap-2 text-sm text-gray-700">
                    <input v-model="form.aktif" type="checkbox" />
                    Aktif (disalin ke proyek baru)
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
