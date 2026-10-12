<section class="space-y-6">
    <header>
        <h2 class="text-lg font-semibold text-slate-900">Hapus Akun</h2>
        <p class="mt-1 text-sm text-slate-500">
            Setelah akun dihapus, semua sumber daya dan data terkait akan dihapus secara permanen.
            Mohon simpan data yang ingin Anda pertahankan sebelum menghapus akun.
        </p>
    </header>

    <form method="post" action="{{ route('profile.destroy') }}" class="space-y-4"
          onsubmit="return confirm('Yakin ingin menghapus akun Anda secara permanen?');">
        @csrf
        @method('delete')

        <div>
            <x-input-label for="delete_password" value="Kata Sandi" />
            <x-text-input id="delete_password" name="password" type="password" class="mt-1 block w-full sm:w-3/4" placeholder="Masukkan kata sandi" />
            <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-2" />
        </div>

        <x-danger-button>Hapus Akun</x-danger-button>
    </form>
</section>
