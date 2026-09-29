<?php

namespace App\Models;

use App\Support\Uang;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Proyek extends Model
{
    protected $table = 'proyek';

    protected $fillable = [
        'klien_id', 'kode', 'nama', 'jenis', 'deskripsi', 'status', 'prioritas',
        'nilai_kontrak', 'dp_nominal', 'tgl_mulai', 'tgl_target', 'tgl_serah',
        'pemilik_id', 'urutan_papan', 'portal_token', 'catatan', 'revisi',
        'tautan_hasil', 'dibuat_oleh',
    ];

    protected function casts(): array
    {
        return [
            'tgl_mulai' => 'date',
            'tgl_target' => 'date',
            'tgl_serah' => 'date',
            'nilai_kontrak' => 'decimal:2',
            'dp_nominal' => 'decimal:2',
        ];
    }

    public const JENIS = [
        'website' => 'Pembuatan Website',
        'aplikasi' => 'Pembuatan Aplikasi',
        'maintenance' => 'Maintenance',
        'lain' => 'Lain-lain',
    ];

    public const STATUS = [
        'penawaran' => 'Penawaran',
        'deal' => 'Deal',
        'desain' => 'Desain',
        'bangun' => 'Pengerjaan',
        'kualitas' => 'Pemeriksaan',
        'revisi' => 'Revisi',
        'selesai' => 'Selesai',
        'ditahan' => 'Ditahan',
        'batal' => 'Batal',
    ];

    /** Kolom papan Kanban — urutan tampil dari kiri ke kanan. */
    public const PAPAN = [
        'penawaran' => 'Penawaran',
        'deal' => 'Deal',
        'desain' => 'Desain',
        'bangun' => 'Pengerjaan',
        'kualitas' => 'Pemeriksaan',
        'revisi' => 'Revisi',
        'selesai' => 'Selesai',
        'ditahan' => 'Ditahan',
    ];

    public const AKTIF = ['penawaran', 'deal', 'desain', 'bangun', 'kualitas', 'revisi', 'ditahan'];

    protected static function booted(): void
    {
        static::creating(function (Proyek $p) {
            if (empty($p->kode)) {
                $p->kode = self::kodeBaru((int) now()->year);
            }
        });
    }

    public function klien(): BelongsTo
    {
        return $this->belongsTo(Klien::class);
    }

    public function pemilik(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pemilik_id');
    }

    public function tahapan(): HasMany
    {
        return $this->hasMany(ProyekTahapan::class)->orderBy('urutan');
    }

    public function tagihan(): HasMany
    {
        return $this->hasMany(Tagihan::class);
    }

    public function aktivitas(): MorphMany
    {
        return $this->morphMany(Aktivitas::class, 'entitas', 'entitas', 'entitas_id');
    }

    public function lampiran(): MorphMany
    {
        return $this->morphMany(Lampiran::class, 'entitas', 'entitas', 'entitas_id');
    }

    public function getLabelStatusAttribute(): string
    {
        return self::STATUS[$this->status] ?? $this->status;
    }

    public function getLabelJenisAttribute(): string
    {
        return self::JENIS[$this->jenis] ?? $this->jenis;
    }

    public function getWarnaStatusAttribute(): string
    {
        return match ($this->status) {
            'selesai' => '#16a34a',
            'batal', 'ditahan' => '#dc2626',
            'revisi', 'kualitas' => '#d97706',
            'bangun', 'desain' => '#0891b2',
            'deal' => '#2563eb',
            default => '#6b7280',
        };
    }

    public function getPersenAttribute(): int
    {
        $tahapan = $this->relationLoaded('tahapan') ? $this->tahapan : $this->tahapan()->get();
        $total = $tahapan->count();

        if ($total === 0) {
            return $this->status === 'selesai' ? 100 : 0;
        }

        return (int) round($tahapan->where('status', 'selesai')->count() / $total * 100);
    }

    public function getNilaiTeksAttribute(): string
    {
        return Uang::rp($this->nilai_kontrak);
    }

    public function getTahapSekarangAttribute(): ?string
    {
        $tahapan = $this->relationLoaded('tahapan') ? $this->tahapan : $this->tahapan()->get();

        return ($tahapan->firstWhere('status', 'jalan') ?? $tahapan->firstWhere('status', 'belum'))?->nama;
    }

    public function getPortalAktifAttribute(): bool
    {
        return ! empty($this->portal_token);
    }

    public function getNilaiTertagihAttribute(): float
    {
        return (float) $this->tagihan()->where('status', '!=', 'batal')->sum('total');
    }

    public function getTerbayarAttribute(): float
    {
        return (float) $this->tagihan()->where('status', '!=', 'batal')->sum('terbayar_total_hitung');
    }

    public function getSisaTagihanAttribute(): float
    {
        return max(0, $this->nilai_tertagih - $this->terbayar);
    }

    public function getNilaiTertagihTeksAttribute(): string
    {
        return Uang::rp($this->nilai_tertagih);
    }

    public function getTerbayarTeksAttribute(): string
    {
        return Uang::rp($this->terbayar);
    }

    public function getSisaTagihanTeksAttribute(): string
    {
        return Uang::rp($this->sisa_tagihan);
    }

    /**
     * Salin tahapan dari template jenis proyek (§7.4). Dipakai controller,
     * skrip, dan tinker. Template kosong → jatuh ke TemplateTahapan::BAWAAN.
     */
    public function salinTahapan(): void
    {
        $template = TemplateTahapan::query()
            ->where('jenis', $this->jenis)->where('aktif', true)
            ->orderBy('urutan')->get();

        if ($template->isEmpty()) {
            $template = collect(TemplateTahapan::BAWAAN[$this->jenis] ?? [])
                ->map(fn ($nama, $i) => (object) ['nama' => $nama, 'urutan' => $i + 1]);
        }

        foreach ($template as $t) {
            $this->tahapan()->create([
                'nama' => $t->nama,
                'urutan' => $t->urutan,
                'status' => 'belum',
            ]);
        }
    }

    public static function kodeBaru(int $tahun): string
    {
        $prefix = "DK-{$tahun}-";
        $kode = static::query()->where('kode', 'like', $prefix.'%')
            ->orderByDesc('kode')->value('kode');

        $urut = $kode ? ((int) substr($kode, strlen($prefix))) + 1 : 1;

        return $prefix.str_pad((string) $urut, 3, '0', STR_PAD_LEFT);
    }
}
