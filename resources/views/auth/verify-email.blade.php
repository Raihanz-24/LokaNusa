<x-guest-layout :title="__('ui.verify_title').' — LokaNusa'">
    <h1 class="text-center text-2xl font-bold text-white">{{ __('ui.verify_title') }}</h1>
    <p class="mt-1 text-center text-sm text-slate-300">{{ __('ui.verify_subtitle') }}</p>

    @if (session('status') == 'verification-link-sent')
        <x-auth.session-status class="mt-5" :status="__('ui.verify_sent')" />
    @endif

    <div class="mt-6 flex items-center justify-between gap-4">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <x-primary-button>{{ __('ui.resend_verification') }}</x-primary-button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="text-sm text-slate-300 hover:text-emerald-300">{{ __('ui.logout') }}</button>
        </form>
    </div>
</x-guest-layout>
