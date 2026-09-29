<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $peran
 * @property bool $aktif
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'email', 'password', 'peran', 'aktif'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const PERAN = [
        'admin' => 'Admin',
        'staf' => 'Staf',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'aktif' => 'boolean',
        ];
    }

    public function getLabelPeranAttribute(): string
    {
        return self::PERAN[$this->peran] ?? $this->peran;
    }

    /**
     * Admin selalu boleh. Staf tergantung pengaturan `peran_boleh_lihat_nilai`:
     * `admin` (bawaan) | `staf` | `semua`.
     */
    public function bolehLihatNilai(): bool
    {
        if ($this->peran === 'admin') {
            return true;
        }

        return Pengaturan::nilai('peran_boleh_lihat_nilai', 'admin') !== 'admin';
    }

    public function getInisialAttribute(): string
    {
        $kata = preg_split('/\s+/', trim((string) $this->name)) ?: [];
        $inisial = '';

        foreach (array_slice($kata, 0, 2) as $k) {
            $inisial .= mb_strtoupper(mb_substr($k, 0, 1));
        }

        return $inisial ?: '?';
    }
}
