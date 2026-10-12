<x-layouts.public title="Profil — LokaNusa" meta-description="Kelola informasi akun LokaNusa Anda.">
    <div class="mx-auto max-w-2xl space-y-6 px-4 py-12">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Profil Saya</h1>
            <p class="mt-1 text-sm text-slate-500">Kelola informasi akun, kata sandi, dan keamanan Anda.</p>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            @include('profile.partials.update-profile-information-form')
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            @include('profile.partials.update-password-form')
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            @include('profile.partials.delete-user-form')
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            <h2 class="text-lg font-semibold text-slate-900">Keluar Akun</h2>
            <p class="mt-1 text-sm text-slate-500">Akhiri sesi Anda di perangkat ini.</p>
            <form method="POST" action="{{ route('logout') }}" class="mt-4">
                @csrf
                <x-danger-button type="submit">
                    {{ __('Keluar') }}
                </x-danger-button>
            </form>
        </div>
    </div>
</x-layouts.public>
