<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { onBeforeUnmount, onMounted, ref } from 'vue';
import Tata from '@/components/Tata.vue';
import Kartu from '@/components/Kartu.vue';
import Kosong from '@/components/Kosong.vue';
import { rute } from '@/lib/rute';

const props = defineProps<{
    kartu: any;
    status: any;
    statusJumlah: Record<string, number>;
    perlu: any;
    linimasa: any[];
    waktu: string;
}>();

/** Data hidup — angka & daftar menyegar sendiri tiap 10 detik. */
const hidup = ref({
    kartu: props.kartu,
    perlu: props.perlu,
    linimasa: props.linimasa,
    waktu: props.waktu,
});

const gagal = ref(false);
let tik: number | undefined;

async function segarkan() {
    if (document.hidden) return; // tab tak terlihat: hemat RAM & kuota VM

    try {
        const r = await fetch(rute.dasborData, {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
        });
        if (!r.ok) throw new Error(String(r.status));

        const d = await r.json();
        hidup.value = {
            kartu: d.kartu,
            perlu: d.perlu_tindakan,
            linimasa: d.linimasa,
            waktu: d.waktu,
        };
        gagal.value = false;
    } catch {
        gagal.value = true; // data terakhir tetap tampil, tidak ada layar kosong
    }
}

onMounted(() => {
    tik = window.setInterval(segarkan, 10_000);
    document.addEventListener('visibilitychange', segarkan);
});

onBeforeUnmount(() => {
    window.clearInterval(tik);
    document.removeEventListener('visibilitychange', segarkan);
});
</script>

<template>
    <Head title="Dasbor" />
    <Tata>
        <section class="mb-3 flex flex-wrap items-end justify-between gap-2">
            <div>
                <h1 class="text-lg font-semibold text-gray-900">Dasbor</h1>
                <p class="dk-sub">
                    Diperbarui {{ hidup.waktu }}
                    <span v-if="gagal" class="text-amber-600"
                        >· gagal menyegar, menampilkan data terakhir</span
                    >
                </p>
            </div>
        </section>

        <!-- Kartu ringkas -->
        <div class="mb-4 grid grid-cols-2 gap-3 lg:grid-cols-5">
            <Kartu
                judul="Klien aktif"
                :nilai="hidup.kartu.klien_aktif"
                :sub="`+${hidup.kartu.klien_baru_bulan} bulan ini`"
                ikon="★"
                warna="#2196f3"
            />
            <Kartu
                judul="Proyek jalan"
                :nilai="hidup.kartu.proyek_jalan"
                :sub="hidup.kartu.nilai_jalan_teks || 'nilai disembunyikan'"
                ikon="▣"
                warna="#7c3aed"
            />
            <Kartu
                judul="Tahap lewat tenggat"
                :nilai="hidup.kartu.tahap_lewat"
                sub="perlu dikejar"
                ikon="⧗"
                warna="#dc2626"
            />
            <Kartu
                judul="Jatuh tempo ≤7 hari"
                :nilai="hidup.kartu.jatuh_tempo"
                sub="siap ditagih"
                ikon="▤"
                warna="#d97706"
            />
            <Kartu
                judul="Piutang"
                :nilai="hidup.kartu.piutang_teks || '—'"
                :sub="`${hidup.kartu.jatuh_tempo} tagihan menunggu`"
                ikon="Σ"
                warna="#0d9488"
            />
        </div>

        <div class="grid gap-4 lg:grid-cols-3">
            <!-- Perlu tindakan -->
            <div class="space-y-4 lg:col-span-2">
                <div class="dk-kartu">
                    <header
                        class="flex items-center justify-between border-b border-gray-100 px-4 py-2.5"
                    >
                        <h2 class="text-sm font-semibold">
                            Perlu tindakan hari ini
                        </h2>
                    </header>

                    <div
                        v-if="
                            hidup.perlu.proyek.length ||
                            hidup.perlu.tahapan.length ||
                            hidup.perlu.tagihan.length
                        "
                    >
                        <!-- Tahapan lewat tenggat -->
                        <div
                            v-if="hidup.perlu.tahapan.length"
                            class="divide-y divide-gray-50"
                        >
                            <Link
                                v-for="t in hidup.perlu.tahapan"
                                :key="'th' + t.id"
                                :href="`/proyek/${t.proyek_id}`"
                                class="flex items-center justify-between gap-3 px-4 py-2.5 hover:bg-gray-50"
                            >
                                <div class="min-w-0">
                                    <p class="truncate text-sm text-gray-800">
                                        {{ t.nama }}
                                    </p>
                                    <p class="dk-sub">
                                        {{ t.kode }} · {{ t.proyek }}
                                    </p>
                                </div>
                                <span
                                    class="shrink-0 rounded-full bg-red-50 px-2 py-0.5 text-[11px] font-medium text-red-700"
                                    >{{ t.hari_lewat }} hari lewat</span
                                >
                            </Link>
                        </div>

                        <!-- Proyek lewat target -->
                        <div
                            v-if="hidup.perlu.proyek.length"
                            class="divide-y divide-gray-50"
                        >
                            <Link
                                v-for="p in hidup.perlu.proyek"
                                :key="'pj' + p.id"
                                :href="`/proyek/${p.id}`"
                                class="flex items-center justify-between gap-3 px-4 py-2.5 hover:bg-gray-50"
                            >
                                <div class="min-w-0">
                                    <p class="truncate text-sm text-gray-800">
                                        {{ p.nama }}
                                    </p>
                                    <p class="dk-sub">
                                        {{ p.kode }} · {{ p.klien }}
                                    </p>
                                </div>
                                <span
                                    class="shrink-0 rounded-full bg-red-50 px-2 py-0.5 text-[11px] font-medium text-red-700"
                                    >target {{ p.tgl_target_teks }}</span
                                >
                            </Link>
                        </div>

                        <!-- Tagihan jatuh tempo -->
                        <div
                            v-if="hidup.perlu.tagihan.length"
                            class="divide-y divide-gray-50"
                        >
                            <Link
                                v-for="t in hidup.perlu.tagihan"
                                :key="'tg' + t.id"
                                :href="rute.tagihan"
                                class="flex items-center justify-between gap-3 px-4 py-2.5 hover:bg-gray-50"
                            >
                                <div class="min-w-0">
                                    <p class="truncate text-sm text-gray-800">
                                        {{ t.judul }}
                                    </p>
                                    <p class="dk-sub">
                                        {{ t.klien
                                        }}<span v-if="t.proyek">
                                            · {{ t.proyek }}</span
                                        >
                                    </p>
                                </div>
                                <span
                                    class="shrink-0 text-right text-xs"
                                    :class="
                                        t.hari_lewat
                                            ? 'font-medium text-red-600'
                                            : 'text-amber-600'
                                    "
                                >
                                    {{ t.tgl_teks }}
                                    <span
                                        v-if="t.sisa_teks"
                                        class="dk-sub block"
                                        >{{ t.sisa_teks }}</span
                                    >
                                </span>
                            </Link>
                        </div>
                    </div>

                    <Kosong
                        v-else
                        teks="Tidak ada yang lewat tenggat. Bersih."
                        ikon="✓"
                    />
                </div>

                <!-- Proyek per status -->
                <div class="dk-kartu dk-kartu-p">
                    <h2 class="mb-2 text-sm font-semibold">
                        Proyek per status
                    </h2>
                    <div class="flex flex-wrap gap-1.5">
                        <Link
                            v-for="(label, kunci) in status"
                            :key="kunci"
                            :href="`/proyek?status=${kunci}`"
                            class="inline-flex items-center gap-1.5 rounded-full border border-gray-200 px-2.5 py-1 text-xs text-gray-700 hover:border-blue-400 hover:text-blue-700"
                        >
                            {{ label }}
                            <span
                                class="rounded-full bg-gray-100 px-1.5 text-[11px] font-semibold text-gray-600 tabular-nums"
                                >{{ statusJumlah[kunci] ?? 0 }}</span
                            >
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Linimasa -->
            <div class="dk-kartu self-start">
                <header class="border-b border-gray-100 px-4 py-2.5">
                    <h2 class="text-sm font-semibold">Aktivitas terakhir</h2>
                </header>
                <div
                    v-if="hidup.linimasa.length"
                    class="divide-y divide-gray-50"
                >
                    <component
                        :is="a.tautan ? Link : 'div'"
                        v-for="a in hidup.linimasa"
                        :key="a.id"
                        :href="a.tautan || undefined"
                        class="block px-4 py-2.5"
                        :class="a.tautan ? 'hover:bg-gray-50' : ''"
                    >
                        <p class="text-sm text-gray-800">{{ a.judul }}</p>
                        <p class="dk-sub">
                            {{ a.tgl_teks }} · {{ a.label_jenis }}
                            <span v-if="a.user"> · {{ a.user }}</span>
                        </p>
                    </component>
                </div>
                <Kosong v-else teks="Belum ada aktivitas." ikon="•" />
            </div>
        </div>
    </Tata>
</template>
