<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class AccountController extends Controller
{
    public function password(Request $request): JsonResponse
    {
        $data = $request->validate([
            'password_lama' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $request->user()->update(['password' => $data['password']]);

        return response()->json(['ok' => true, 'message' => 'Password berhasil diganti.']);
    }

    /** Ubah nama & foto profil (foto opsional, bisa juga dihapus). */
    public function profile(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'hapus_foto' => ['nullable', 'boolean'],
        ], [
            'avatar.image' => 'File harus berupa gambar.',
            'avatar.mimes' => 'Format foto harus JPG, PNG, atau WEBP.',
            'avatar.max' => 'Ukuran foto maksimal 2 MB.',
        ]);

        $user = $request->user();
        $dir = public_path(User::AVATAR_DIR);
        $old = $user->avatar;
        $avatar = $old;

        if (! config('app.avatar_upload')) {
            // Hosting tanpa disk permanen (mis. Vercel): foto tidak disimpan, hanya nama yang diubah.
        } elseif ($request->hasFile('avatar')) {
            File::ensureDirectoryExists($dir);
            $file = $request->file('avatar');
            $avatar = $user->id.'-'.Str::random(10).'.'.$file->extension();
            $file->move($dir, $avatar);
        } elseif ($request->boolean('hapus_foto')) {
            $avatar = null;
        }

        if ($old && $old !== $avatar) {
            File::delete($dir.DIRECTORY_SEPARATOR.$old);
        }

        $user->update(['name' => $data['name'], 'avatar' => $avatar]);

        return response()->json([
            'ok' => true,
            'message' => 'Profil berhasil disimpan.',
            'avatar' => $user->avatarUrl(),
        ]);
    }
}
