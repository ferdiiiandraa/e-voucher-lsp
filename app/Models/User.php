<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

#[Fillable(['name', 'username', 'avatar', 'password', 'is_admin', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    use HasFactory;

    /** Folder foto profil, di dalam public/ agar bisa diakses tanpa storage:link (aman di shared hosting). */
    public const AVATAR_DIR = 'uploads/avatars';

    public function avatarUrl(): ?string
    {
        return $this->avatar ? asset(self::AVATAR_DIR.'/'.$this->avatar) : null;
    }

    public function initial(): string
    {
        return strtoupper(mb_substr($this->name, 0, 1));
    }

    /** HTML avatar: foto bila ada, kalau tidak huruf depan nama. */
    public function avatarHtml(): string
    {
        $url = $this->avatarUrl();

        return $url
            ? '<img src="'.e($url).'" alt="" loading="lazy">'
            : e($this->initial());
    }

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}
