<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import Tata from '@/components/Tata.vue';
import Kepala from '@/components/Kepala.vue';
import Badge from '@/components/Badge.vue';
import Kosong from '@/components/Kosong.vue';
import Paginasi from '@/components/Paginasi.vue';
import Modal from '@/components/Modal.vue';
import { rute } from '@/lib/rute';

const props = defineProps<{
    daftar: any;
    filter: any;
    opsi: any;
}>();

const q = ref(props.filter.q || '');
const status = ref(props.filter.status || '');
const urutan = ref(props.filter.urutan || 'baru');
const detailId = ref<number | null>(null);
const detail = ref<any>(null);
const memuatDetail = ref(false);

let jeda: number | undefined;

/** Cari dengan jeda 300 ms supaya tiap ketikan tidak memicu permintaan. */
watch(q, () => {
    window.clearTimeout(jeda);
    jeda = window.setTimeout(terapkan, 300);
});
watch([status, urutan], terapkan);

function terapkan() {
    router.get(
        rute.klien,
        {
            q: q.value || undefined,
            status: status.value || undefined,
            urutan: urutan.value,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

function bersihkan() {
    q.value = '';
    status.value = '';
    urutan.value = 'baru';
    router.get(rute.klien, {}, { preserveState: true, replace: true });
}

async function bukaDetail(k: any) {
    detailId.value = k.id;
    detail.value = null;
    memuatDetail.value = true;
    try {
        const res = await fetch(rute.klienDetail(k.id), {
            headers: {
                Accept: 'application/json',
                'X-Inertia': 'true',
                'X-Inertia-Version': '',
            },
            credentials: 'same-origin',
        });
        // Inertia balas JSON penuh; ambil props-nya.
        const teks = await res.text();
        detail.value = JSON.parse(teks)?.props?.klien ?? k;
    } catch {
        detail.value = k;
    } finally {
        memuatDetail.value = false;
    }
}

function hapus(k: any) {
    if (!confirm(`Hapus klien "${k.nama}"? Data tidak bisa dikembalikan.`))
        return;
    router.delete(`/klien/${k.id}`, { preserveScroll: true });
}

const ada = computed(() => props.daftar.data.length > 0);
</script>

<template>
    <Head title="Klien" />
    <Tata>
        <Kepala
            :judul="`Klien (${daftar.total})`"
            sub="Prospek, klien aktif, dan pelanggan lama"
        >
            <template #aksi>
                <a :href="rute.klienUnduh" class="dk-tbl dk-tbl-kosong"
                    >↓ CSV</a
                >
                <Link :href="rute.klienImpor" class="dk-tbl dk-tbl-kosong"
                    >Impor prospek</Link
                >
                <Link :href="rute.klienBaru" class="dk-tbl dk-tbl-utama"
                    >+ Klien</Link
                >
            </template>
        </Kepala>

        <!-- Saring -->
        <div class="dk-kartu dk-kartu-p mb-3 flex flex-wrap items-center gap-2">
            <input
                v-model="q"
                type="search"
                class="dk-isian max-w-xs flex-1"
                placeholder="Cari nama, perusahaan, email, telepon…"
            />
            <select v-model="status" class="dk-isian w-auto">
                <option value="">Semua status</option>
                <option v-for="(v, k) in opsi.status" :key="k" :value="k">
                    {{ (v as string[])[0] }}
                </option>
            </select>
            <select v-model="urutan" class="dk-isian w-auto">
                <option value="baru">Terbaru</option>
                <option value="nama">Nama A–Z</option>
            </select>
            <button
                v-if="q || status || urutan !== 'baru'"
                type="button"
                class="dk-tbl dk-tbl-halus"
                @click="bersihkan"
            >
                Bersihkan
            </button>
        </div>

        <!-- Tabel -->
        <div class="dk-kartu overflow-hidden">
            <div v-if="ada" class="overflow-x-auto">
                <table class="dk-tabel">
                    <thead>
                        <tr>
                            <th>Klien</th>
                            <th>Kontak</th>
                            <th>Status</th>
                            <th class="dk-angka">Proyek</th>
                            <th class="dk-angka">Nilai kontrak</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="k in daftar.data" :key="k.id">
                            <td>
                                <button
                                    type="button"
                                    class="text-left"
                                    @click="bukaDetail(k)"
                                >
                                    <span class="font-medium text-gray-900">{{
                                        k.nama
                                    }}</span>
                                    <span
                                        v-if="k.perusahaan"
                                        class="dk-sub block truncate"
                                        :title="k.perusahaan"
                                        >{{ k.perusahaan }}</span
                                    >
                                </button>
                            </td>
                            <td class="text-gray-600">
                                <span v-if="k.email" class="block text-xs">{{
                                    k.email
                                }}</span>
                                <span v-if="k.telepon" class="block text-xs">{{
                                    k.telepon
                                }}</span>
                                <span
                                    v-if="!k.email && !k.telepon"
                                    class="text-gray-300"
                                    >—</span
                                >
                            </td>
                            <td>
                                <Badge
                                    :teks="
                                        (
                                            opsi.status[k.status] as string[]
                                        )?.[0] || k.status
                                    "
                                    :warna="
                                        (opsi.status[k.status] as string[])?.[1]
                                    "
                                />
                                <span
                                    v-if="!k.aktif"
                                    class="dk-sub ml-1 text-[11px]"
                                    >nonaktif</span
                                >
                            </td>
                            <td class="dk-angka text-gray-600">
                                {{ k.proyek_aktif }}
                            </td>
                            <td class="dk-angka font-medium">
                                {{ k.nilai_lifetime }}
                            </td>
                            <td class="dk-aksi">
                                <div class="dk-aksi-grup">
                                    <Link
                                        :href="rute.klienDetail(k.id)"
                                        class="dk-tbl dk-tbl-halus"
                                        >Buka</Link
                                    >
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <Kosong
                v-else
                teks="Belum ada klien cocok."
                ikon="★"
                aksi="Tambah klien"
                @klik="router.visit(rute.klienBaru)"
            />
        </div>

        <Paginasi :halaman="daftar" />

        <!-- Detail ringkas (klik nama) -->
        <Modal
            :buka="detailId !== null"
            :judul="detail?.nama || 'Klien'"
            lebar="md"
            @tutup="((detailId = null), (detail = null))"
        >
            <p v-if="memuatDetail" class="dk-sub">Memuat…</p>
            <div v-else-if="detail" class="space-y-3 text-sm">
                <p class="dk-sub">
                    {{ detail.kode }} · {{ detail.label_status }}
                </p>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <p class="dk-label">Perusahaan</p>
                        <p>{{ detail.perusahaan || '—' }}</p>
                    </div>
                    <div>
                        <p class="dk-label">Kota</p>
                        <p>{{ detail.kota || '—' }}</p>
                    </div>
                    <div>
                        <p class="dk-label">Email</p>
                        <p class="break-all">{{ detail.email || '—' }}</p>
                    </div>
                    <div>
                        <p class="dk-label">Telepon</p>
                        <p>{{ detail.telepon || '—' }}</p>
                    </div>
                </div>
                <div class="flex flex-wrap gap-2 pt-1">
                    <Link
                        :href="rute.klienDetail(detail.id)"
                        class="dk-tbl dk-tbl-utama"
                        >Buka halaman klien</Link
                    >
                    <a
                        v-if="detail.whatsapp"
                        :href="detail.whatsapp"
                        target="_blank"
                        rel="noopener"
                        class="dk-tbl dk-tbl-kosong"
                        >WhatsApp</a
                    >
                    <Link
                        :href="rute.klienDetail(detail.id) + '?tab=ubah'"
                        class="dk-tbl dk-tbl-kosong"
                        >Ubah</Link
                    >
                </div>
            </div>
        </Modal>
    </Tata>
</template>
