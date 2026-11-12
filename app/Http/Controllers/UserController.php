<?php

namespace App\Http\Controllers;

use App\Models\Pemakaian;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $this->onlyAdmin($request);

        $users = User::query()
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get();

        // Jumlah catatan pemakaian per pengguna (ditampilkan & dipakai di konfirmasi hapus)
        $catatan = Pemakaian::query()
            ->selectRaw('user_id, COUNT(*) as total')
            ->whereNotNull('user_id')
            ->groupBy('user_id')
            ->pluck('total', 'user_id');

        return view('pengguna', compact('users', 'catatan'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->onlyAdmin($request);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'username' => ['required', 'string', 'max:50', 'alpha_dash', 'unique:users,username'],
            'password' => ['required', Password::min(8)],
            'is_admin' => ['nullable', 'boolean'],
        ]);
        $data['is_admin'] = $request->boolean('is_admin');
        $data['is_active'] = true;

        User::create($data);

        return back()->with('status', "Pengguna {$data['name']} ditambahkan.");
    }

    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        $this->onlyAdmin($request);

        $data = $request->validate([
            'password' => ['required', Password::min(8)],
        ]);

        $user->update(['password' => $data['password']]);

        return back()->with('status', "Password {$user->name} telah direset.");
    }

    public function toggleActive(Request $request, User $user): RedirectResponse
    {
        $this->onlyAdmin($request);

        if ($user->is($request->user())) {
            return back()->withErrors(['user' => 'Anda tidak dapat menonaktifkan akun sendiri.']);
        }

        if ($user->is_active && $user->is_admin && $this->activeAdminCount() <= 1) {
            return back()->withErrors(['user' => 'Minimal harus ada satu admin yang aktif.']);
        }

        $user->update(['is_active' => ! $user->is_active]);

        if (! $user->is_active) {
            // Keluarkan dari semua sesi yang sedang berjalan
            $this->endSessions($user);
            $user->forceFill(['remember_token' => null])->save();

            return back()->with('status', "{$user->name} dinonaktifkan dan tidak bisa login lagi.");
        }

        return back()->with('status', "{$user->name} diaktifkan kembali.");
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->onlyAdmin($request);

        if ($user->is($request->user())) {
            return back()->withErrors(['user' => 'Anda tidak dapat menghapus akun sendiri.']);
        }

        if ($user->is_admin && $user->is_active && $this->activeAdminCount() <= 1) {
            return back()->withErrors(['user' => 'Minimal harus ada satu admin yang aktif.']);
        }

        $this->endSessions($user);
        $user->delete(); // catatan pemakaiannya tetap ada, kolom "dicatat oleh" menjadi kosong

        return back()->with('status', "Pengguna {$user->name} dihapus.");
    }

    private function activeAdminCount(): int
    {
        return User::where('is_admin', true)->where('is_active', true)->count();
    }

    private function endSessions(User $user): void
    {
        if (config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))->where('user_id', $user->id)->delete();
        }
    }

    private function onlyAdmin(Request $request): void
    {
        abort_unless($request->user()->is_admin, 403, 'Halaman ini hanya untuk admin.');
    }
}
