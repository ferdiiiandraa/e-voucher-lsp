<?php

namespace Tests\Feature;

use App\Models\Pemakaian;
use App\Models\User;
use App\Models\Voucher;
use App\Support\Revision;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Menguji seluruh alur aplikasi: login, halaman, voucher, pemakaian,
 * update sisa, riwayat, sinkronisasi, profil/foto, password, dan pengguna.
 */
class AppFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    /** Foto yang sudah ada sebelum test (milik data asli) — tidak boleh tersentuh. */
    private array $avatarsBefore = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->avatarsBefore = File::glob(public_path(User::AVATAR_DIR.'/*'));
        $this->admin = User::factory()->admin()->create(['name' => 'Wulan', 'username' => 'wulan', 'is_active' => true]);
    }

    protected function tearDown(): void
    {
        // Hapus hanya foto yang dibuat selama test
        foreach (array_diff(File::glob(public_path(User::AVATAR_DIR.'/*')), $this->avatarsBefore) as $f) {
            File::delete($f);
        }
        parent::tearDown();
    }

    private function voucherData(array $over = []): array
    {
        return array_merge([
            'klien' => 'LSP Uji',
            'kode' => 'NAS1',
            'telepon' => '0856-2484-3679',
            'kuota' => 50,
            'mulai' => '2026-11-12',
            'bulan' => 3,
            'berakhir' => '',
            'catatan' => '',
        ], $over);
    }

    private function makeVoucher(array $over = []): Voucher
    {
        $id = $this->actingAs($this->admin)->postJson('/data/vouchers', $this->voucherData($over))->assertOk()->json('id');

        return Voucher::findOrFail($id);
    }

    /* ---------------- Login & akses ---------------- */

    public function test_tamu_diarahkan_ke_login(): void
    {
        $this->get('/')->assertRedirect('/login');
        $this->get('/kelola')->assertRedirect('/login');
        $this->getJson('/data/state')->assertUnauthorized();
    }

    public function test_halaman_login_tampil(): void
    {
        $this->get('/login')->assertOk()->assertSee('username', false);
    }

    public function test_login_berhasil_dan_gagal(): void
    {
        $this->post('/login', ['username' => 'wulan', 'password' => 'salah'])->assertSessionHasErrors('username');
        $this->assertGuest();

        $this->post('/login', ['username' => 'wulan', 'password' => 'password'])->assertRedirect(route('beranda'));
        $this->assertAuthenticatedAs($this->admin);
    }

    public function test_akun_nonaktif_tidak_bisa_login(): void
    {
        User::factory()->create(['username' => 'ferdi', 'is_active' => false]);
        $this->post('/login', ['username' => 'ferdi', 'password' => 'password'])->assertSessionHasErrors('username');
        $this->assertGuest();
    }

    public function test_logout(): void
    {
        $this->actingAs($this->admin)->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }

    /* ---------------- Halaman ---------------- */

    public function test_semua_halaman_tampil_tanpa_error(): void
    {
        $this->makeVoucher();

        $this->actingAs($this->admin)->get('/')->assertOk()
            ->assertSee('Halo, Wulan')
            ->assertSee('dlgProfile', false)
            ->assertSee('Keluar')
            ->assertDontSee('user-card', false);

        $this->get('/kelola')->assertOk()->assertSee('table-logs', false)->assertSee('exceljs', false);
        $this->get('/pengguna')->assertOk()->assertSee('Wulan')
            ->assertDontSee('Akun tidak bisa login dan langsung dikeluarkan');
    }

    public function test_halaman_pengguna_hanya_admin(): void
    {
        $staf = User::factory()->create(['is_active' => true]);
        $this->actingAs($staf)->get('/pengguna')->assertForbidden();
        $this->actingAs($staf)->get('/')->assertOk();
    }

    /* ---------------- Voucher ---------------- */

    public function test_tambah_voucher_dan_normalisasi_telepon(): void
    {
        $rev = Revision::current();
        $v = $this->makeVoucher();

        $this->assertSame('6285624843679', $v->telepon);
        $this->assertSame('2027-02-11', $v->berakhir->toDateString()); // 12 Nov + 3 bulan - 1 hari
        $this->assertSame(50, $v->kuota);
        $this->assertGreaterThan($rev, Revision::current());
    }

    public function test_format_telepon_lain_diterima(): void
    {
        foreach (['856-2484-3679', '+62 856 2484 3679', '6285624843679'] as $no) {
            $this->assertSame('6285624843679', $this->makeVoucher(['telepon' => $no])->telepon);
        }
        $this->assertNull($this->makeVoucher(['telepon' => ''])->telepon);
    }

    public function test_validasi_voucher(): void
    {
        $this->actingAs($this->admin);
        // Kuota wajib diisi (tidak ada default)
        $this->postJson('/data/vouchers', $this->voucherData(['kuota' => '']))->assertUnprocessable()->assertJsonValidationErrors('kuota');
        $this->postJson('/data/vouchers', $this->voucherData(['klien' => '']))->assertJsonValidationErrors('klien');
        $this->postJson('/data/vouchers', $this->voucherData(['telepon' => 'abc']))->assertJsonValidationErrors('telepon');
        $this->postJson('/data/vouchers', $this->voucherData(['berakhir' => '2026-01-01']))->assertJsonValidationErrors('berakhir');
        $this->assertSame(0, Voucher::count());
    }

    public function test_edit_dan_hapus_voucher(): void
    {
        $v = $this->makeVoucher();
        $this->putJson("/data/vouchers/{$v->id}", $this->voucherData(['klien' => 'LSP Baru', 'kuota' => 80]))->assertOk();
        $v->refresh();
        $this->assertSame('LSP Baru', $v->klien);
        $this->assertSame(80, $v->kuota);

        $this->postJson('/data/pemakaian', ['voucher_id' => $v->id, 'tanggal' => '2026-11-12', 'jumlah' => 5])->assertOk();
        $this->deleteJson("/data/vouchers/{$v->id}")->assertOk();
        $this->assertSame(0, Voucher::count());
        $this->assertSame(0, Pemakaian::count());
    }

    /* ---------------- Pemakaian & update sisa ---------------- */

    public function test_catat_dan_hapus_pemakaian(): void
    {
        $v = $this->makeVoucher();
        $this->postJson('/data/pemakaian', ['voucher_id' => $v->id, 'tanggal' => '2026-11-12', 'jumlah' => 10, 'keterangan' => 'TUK A'])->assertOk();
        $this->postJson('/data/pemakaian', ['voucher_id' => $v->id, 'tanggal' => '2026-11-12', 'jumlah' => 0])->assertJsonValidationErrors('jumlah');

        $p = Pemakaian::firstOrFail();
        $this->assertSame($this->admin->id, $p->user_id);

        $this->deleteJson("/data/pemakaian/{$p->id}")->assertOk();
        $this->assertSame(0, Pemakaian::count());
    }

    public function test_update_sisa_kuota_dicatat_sebagai_koreksi(): void
    {
        $v = $this->makeVoucher();

        $this->patchJson("/data/vouchers/{$v->id}/sisa", ['kategori' => 'kuota', 'sisa' => 40, 'keterangan' => 'cek LSP'])
            ->assertOk()->assertJsonPath('message', 'Sisa kuota diperbarui menjadi 40 asesi.');

        $p = Pemakaian::firstOrFail();
        $this->assertSame('koreksi', $p->jenis);
        $this->assertSame(10, $p->jumlah);
        $this->assertStringContainsString('Penyesuaian sisa 50 → 40', $p->keterangan);

        // Sisa sama → tidak ada catatan baru
        $this->patchJson("/data/vouchers/{$v->id}/sisa", ['kategori' => 'kuota', 'sisa' => 40])->assertOk();
        $this->assertSame(1, Pemakaian::count());

        // Sisa bertambah → koreksi negatif
        $this->patchJson("/data/vouchers/{$v->id}/sisa", ['kategori' => 'kuota', 'sisa' => 45])->assertOk();
        $this->assertSame(-5, Pemakaian::latest('id')->first()->jumlah);

        // Melebihi kuota ditolak
        $this->patchJson("/data/vouchers/{$v->id}/sisa", ['kategori' => 'kuota', 'sisa' => 51])->assertJsonValidationErrors('sisa');
    }

    public function test_update_masa_berlaku(): void
    {
        $v = $this->makeVoucher();
        $this->patchJson("/data/vouchers/{$v->id}/sisa", ['kategori' => 'masa', 'berakhir' => '2027-03-31'])->assertOk();
        $this->assertSame('2027-03-31', $v->fresh()->berakhir->toDateString());

        $this->patchJson("/data/vouchers/{$v->id}/sisa", ['kategori' => 'masa', 'berakhir' => '2026-01-01'])->assertJsonValidationErrors('berakhir');
    }

    /* ---------------- Sinkronisasi & riwayat ---------------- */

    public function test_state_dan_riwayat_sesuai_kuota_masing_masing(): void
    {
        $a = $this->makeVoucher(['klien' => 'LSP A', 'kuota' => 50]);
        $b = $this->makeVoucher(['klien' => 'LSP B', 'kuota' => 150]);
        $this->postJson('/data/pemakaian', ['voucher_id' => $a->id, 'tanggal' => '2026-11-12', 'jumlah' => 10])->assertOk();
        $this->postJson('/data/pemakaian', ['voucher_id' => $b->id, 'tanggal' => '2026-11-12', 'jumlah' => 100])->assertOk();

        $state = $this->getJson('/data/state')->assertOk()->assertJsonPath('changed', true)->json();
        $byId = collect($state['vouchers'])->keyBy('id');
        $this->assertSame(10, $byId[$a->id]['terpakai']);
        $this->assertSame(50, $byId[$a->id]['kuota']);
        $this->assertSame(100, $byId[$b->id]['terpakai']);
        $this->assertSame(150, $byId[$b->id]['kuota']);
        $this->assertSame('Wulan', $state['terbaru'][0]['oleh']);

        // Revisi sama → tidak kirim ulang data
        $this->getJson('/data/state?rev='.$state['rev'])->assertJsonPath('changed', false);

        $logs = $this->getJson("/data/riwayat?ids={$a->id},{$b->id}")->assertOk()->json('logs');
        $this->assertCount(1, $logs[$a->id]);
        $this->assertSame(100, $logs[$b->id][0]['jumlah']);
        $this->getJson('/data/riwayat?ids=')->assertOk();
    }

    /* ---------------- Profil, foto & password ---------------- */

    public function test_ubah_nama_dan_upload_foto_profil(): void
    {
        $this->actingAs($this->admin);
        $file = UploadedFile::fake()->image('test-foto.jpg', 200, 200);

        $res = $this->post('/data/akun/profil', ['name' => 'Wulan Sari', 'avatar' => $file], ['Accept' => 'application/json'])->assertOk();

        $u = $this->admin->fresh();
        $this->assertSame('Wulan Sari', $u->name);
        $this->assertNotNull($u->avatar);
        $path = public_path(User::AVATAR_DIR.'/'.$u->avatar);
        $this->assertFileExists($path);
        $this->assertSame($u->avatarUrl(), $res->json('avatar'));

        // Foto tampil di halaman
        $this->get('/')->assertSee($u->avatar, false)->assertSee('Halo, Wulan Sari');

        // Ganti foto → foto lama terhapus
        $this->post('/data/akun/profil', ['name' => 'Wulan Sari', 'avatar' => UploadedFile::fake()->image('test-2.png')], ['Accept' => 'application/json'])->assertOk();
        $this->assertFileDoesNotExist($path);

        // Hapus foto
        $new = public_path(User::AVATAR_DIR.'/'.$u->fresh()->avatar);
        $this->post('/data/akun/profil', ['name' => 'Wulan Sari', 'hapus_foto' => '1'], ['Accept' => 'application/json'])->assertOk();
        $this->assertNull($u->fresh()->avatar);
        $this->assertFileDoesNotExist($new);
    }

    public function test_validasi_profil(): void
    {
        $this->actingAs($this->admin);
        $h = ['Accept' => 'application/json'];
        $this->post('/data/akun/profil', ['name' => ''], $h)->assertJsonValidationErrors('name');
        $this->post('/data/akun/profil', ['name' => 'A', 'avatar' => UploadedFile::fake()->create('test-x.pdf', 10, 'application/pdf')], $h)->assertJsonValidationErrors('avatar');
        $this->post('/data/akun/profil', ['name' => 'A', 'avatar' => UploadedFile::fake()->image('test-big.jpg')->size(3000)], $h)->assertJsonValidationErrors('avatar');
        $this->assertSame('Wulan', $this->admin->fresh()->name);
    }

    public function test_ganti_password(): void
    {
        $this->actingAs($this->admin);
        $this->putJson('/data/akun/password', ['password_lama' => 'salah', 'password' => 'rahasia123', 'password_confirmation' => 'rahasia123'])
            ->assertJsonValidationErrors('password_lama');
        $this->putJson('/data/akun/password', ['password_lama' => 'password', 'password' => 'rahasia123', 'password_confirmation' => 'rahasia123'])->assertOk();

        $this->post('/logout');
        $this->post('/login', ['username' => 'wulan', 'password' => 'rahasia123'])->assertRedirect(route('beranda'));
    }

    /* ---------------- Manajemen pengguna ---------------- */

    public function test_kelola_pengguna(): void
    {
        $this->actingAs($this->admin);

        $this->post('/pengguna', ['name' => 'Ferdi', 'username' => 'ferdi', 'password' => 'password123'])->assertSessionHasNoErrors();
        $ferdi = User::where('username', 'ferdi')->firstOrFail();
        $this->assertFalse($ferdi->is_admin);

        $this->post('/pengguna', ['name' => 'X', 'username' => 'ferdi', 'password' => 'password123'])->assertSessionHasErrors('username');

        $this->put("/pengguna/{$ferdi->id}/password", ['password' => 'baru12345'])->assertSessionHasNoErrors();

        $this->patch("/pengguna/{$ferdi->id}/status")->assertSessionHasNoErrors();
        $this->assertFalse($ferdi->fresh()->is_active);
        $this->patch("/pengguna/{$ferdi->id}/status");
        $this->assertTrue($ferdi->fresh()->is_active);

        // Tidak boleh menonaktifkan / menghapus diri sendiri
        $this->patch("/pengguna/{$this->admin->id}/status")->assertSessionHasErrors('user');
        $this->delete("/pengguna/{$this->admin->id}")->assertSessionHasErrors('user');

        $this->delete("/pengguna/{$ferdi->id}")->assertSessionHasNoErrors();
        $this->assertNull(User::find($ferdi->id));
    }

    public function test_pengguna_dinonaktifkan_langsung_dikeluarkan(): void
    {
        $staf = User::factory()->create(['is_active' => true]);
        $this->actingAs($staf)->get('/')->assertOk();
        $staf->update(['is_active' => false]);
        $this->actingAs($staf)->get('/')->assertRedirect('/login');
    }
}
