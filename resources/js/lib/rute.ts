/** Pemetaan rute Laravel — satu tempat, tanpa ketergantungan wayfinder. */
export const rute = {
    dasbor: '/',
    dasborData: '/dasbor/data',

    klien: '/klien',
    klienUnduh: '/klien/unduh',
    klienBaru: '/klien/baru',
    klienImpor: '/klien/impor',
    klienDetail: (id: number) => `/klien/${id}/detail`,
    klienUbah: (id: number) => `/klien/${id}/ubah`,
    klienAktivitas: (id: number) => `/klien/${id}/aktivitas`,
    klienLampiran: (id: number) => `/klien/${id}/lampiran`,

    proyek: '/proyek',
    proyekUnduh: '/proyek/unduh',
    proyekBaru: '/proyek/baru',
    proyekLihat: (id: number) => `/proyek/${id}`,
    proyekUbah: (id: number) => `/proyek/${id}/ubah`,
    proyekStatus: (id: number) => `/proyek/${id}/status`,
    proyekTahapan: (id: number, tahapan: number) => `/proyek/${id}/tahapan/${tahapan}`,
    proyekAktivitas: (id: number) => `/proyek/${id}/aktivitas`,
    proyekLampiran: (id: number) => `/proyek/${id}/lampiran`,
    proyekPortal: (id: number) => `/proyek/${id}/portal`,

    tagihan: '/tagihan',
    tagihanUnduh: '/tagihan/unduh',
    tagihanBaru: '/tagihan/baru',
    tagihanUbah: (id: number) => `/tagihan/${id}/ubah`,
    tagihanBayar: (id: number) => `/tagihan/${id}/bayar`,
    tagihanBayarHapus: (id: number, bayar: number) => `/tagihan/${id}/bayar/${bayar}`,
    tagihanBatal: (id: number) => `/tagihan/${id}/batal`,

    pengaturan: '/pengaturan',
    pengguna: '/pengaturan/pengguna',
    penggunaUbah: (id: number) => `/pengaturan/pengguna/${id}/ubah`,
    pengaturanTahapan: '/pengaturan/tahapan',
    lampiranUnduh: (id: number) => `/lampiran/${id}/unduh`,
};

export function bulan(tanggal: string | null | undefined): string {
    if (!tanggal) return '—';

    const d = new Date(tanggal + 'T00:00:00');
    if (Number.isNaN(d.getTime())) return '—';

    return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
}

export function rupiah(nilai: number | string | null | undefined): string {
    const n = Number(nilai ?? 0);

    return (
        'Rp ' +
        n.toLocaleString('id-ID', {
            minimumFractionDigits: 0,
            maximumFractionDigits: Number.isInteger(n) ? 0 : 2,
        })
    );
}

/** Tanggal hari ini dalam format YYYY-MM-DD (waktu setempat). */
export function hariIni(): string {
    const d = new Date();
    const p = (n: number) => String(n).padStart(2, '0');

    return `${d.getFullYear()}-${p(d.getMonth() + 1)}-${p(d.getDate())}`;
}

/** "3 hari lagi" / "Lewat 4 hari" */
export function selisihHari(tanggal: string | null | undefined): string {
    if (!tanggal) return '—';

    const target = new Date(tanggal + 'T00:00:00').getTime();
    const kini = new Date(hariIni() + 'T00:00:00').getTime();
    const beda = Math.round((target - kini) / 86400000);

    if (beda === 0) return 'Hari ini';
    if (beda === 1) return 'Besok';
    if (beda > 1) return `${beda} hari lagi`;
    if (beda === -1) return 'Lewat 1 hari';

    return `Lewat ${Math.abs(beda)} hari`;
}
