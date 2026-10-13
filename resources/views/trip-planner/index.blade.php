<x-layouts.public :title="__('ui.trip_planner_title')" :meta-description="__('ui.trip_planner_subtitle')" :full-height="true">
    @php
        // Riwayat dari sesi; bila kosong tampilkan sapaan pembuka.
        $initial = $messages;
        if (empty($initial)) {
            $initial = [[
                'role' => 'assistant',
                'text' => __('ui.trip_planner_welcome'),
                'time' => now()->format('H:i'),
            ]];
        }
    @endphp

    <div class="flex min-h-0 flex-1 flex-col" data-chat
         data-endpoint="{{ route('trip-planner.send') }}"
         data-clear-endpoint="{{ route('trip-planner.clear') }}"
         data-quota-template="{{ __('ui.trip_planner_quota', ['remaining' => '__R__', 'limit' => '__L__']) }}"
         data-quota-exceeded="{{ __('ui.trip_planner_quota_exceeded', ['limit' => $dailyLimit]) }}"
         data-error="{{ __('ui.trip_planner_error') }}">

        {{-- Kepala asisten (statis) --}}
        <div class="shrink-0 border-b border-slate-200 bg-white px-4 py-3">
            <div class="mx-auto flex max-w-3xl items-center gap-3">
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-emerald-100 text-emerald-700">
                    <x-heroicon name="sparkles" class="h-5 w-5" />
                </span>
                <div class="min-w-0 flex-1">
                    <h1 class="truncate text-base font-bold text-slate-900">{{ __('ui.trip_planner_heading') }}</h1>
                    <p class="flex items-center gap-1.5 text-xs text-slate-500">
                        <span class="inline-block h-2 w-2 rounded-full bg-emerald-500"></span>
                        {{ __('ui.trip_planner_assistant') }} &middot; {{ __('ui.trip_planner_online') }}
                    </p>
                </div>
                @if ($remainingPrompts !== null)
                    <span data-chat-quota
                          class="shrink-0 rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-500"
                          title="{{ __('ui.trip_planner_quota', ['remaining' => $remainingPrompts, 'limit' => $dailyLimit]) }}">
                        {{ __('ui.trip_planner_quota', ['remaining' => $remainingPrompts, 'limit' => $dailyLimit]) }}
                    </span>
                @endif
            </div>
        </div>

        {{-- Area pesan: SATU-SATUNYA bagian yang menggulir --}}
        <div class="relative flex min-h-0 flex-1 flex-col">
            <div data-chat-scroll class="min-h-0 flex-1 overflow-y-auto">
                <div class="mx-auto flex max-w-3xl flex-col gap-3 px-4 py-4">
                    <div data-chat-messages class="flex flex-col gap-3" aria-live="polite">
                        @foreach ($initial as $message)
                            <x-chat-bubble :role="$message['role']" :text="$message['text']" :html="$message['html'] ?? null" :time="$message['time'] ?? null" />
                        @endforeach
                    </div>

                    {{-- Indikator menulis --}}
                    <div data-chat-typing class="hidden items-center gap-2 text-sm text-slate-400">
                        <span class="flex gap-1">
                            <span class="h-2 w-2 animate-bounce rounded-full bg-slate-300" style="animation-delay: 0ms"></span>
                            <span class="h-2 w-2 animate-bounce rounded-full bg-slate-300" style="animation-delay: 150ms"></span>
                            <span class="h-2 w-2 animate-bounce rounded-full bg-slate-300" style="animation-delay: 300ms"></span>
                        </span>
                        {{ __('ui.trip_planner_typing') }}
                    </div>
                </div>
            </div>

            {{-- Alat mengambang: mengikuti posisi (tidak ikut tergulir),
                 diletakkan di kanan bawah tepat di atas kolom mengetik. --}}
            <div class="pointer-events-none absolute inset-x-0 bottom-0 z-20 flex justify-end px-4 pb-4"
                 aria-label="{{ __('ui.trip_planner_tools') }}">
                <div class="pointer-events-auto flex flex-col gap-2">
                    <button type="button" data-chat-top
                            class="hidden h-11 w-11 place-items-center rounded-full border border-slate-200 bg-white text-slate-600 shadow-lg transition hover:bg-slate-50"
                            aria-label="{{ __('ui.trip_planner_back_to_top') }}" title="{{ __('ui.trip_planner_back_to_top') }}">
                        <x-heroicon name="arrow-up" class="h-5 w-5" />
                    </button>
                    <button type="button" data-chat-bottom
                            class="hidden h-11 w-11 place-items-center rounded-full border border-slate-200 bg-white text-slate-600 shadow-lg transition hover:bg-slate-50"
                            aria-label="{{ __('ui.trip_planner_back_to_bottom') }}" title="{{ __('ui.trip_planner_back_to_bottom') }}">
                        <x-heroicon name="arrow-down" class="h-5 w-5" />
                    </button>
                    <button type="button" data-chat-clear
                            class="grid h-11 w-11 place-items-center rounded-full border border-slate-200 bg-white text-slate-600 shadow-lg transition hover:bg-rose-50 hover:text-rose-600"
                            aria-label="{{ __('ui.trip_planner_clear') }}" title="{{ __('ui.trip_planner_clear') }}">
                        <x-heroicon name="arrow-path" class="h-5 w-5" />
                    </button>
                </div>
            </div>
        </div>

        {{-- Saran cepat + kolom kirim: terkunci di bawah --}}
        <div class="shrink-0 border-t border-slate-200 bg-white px-4 pb-4 pt-3">
            <div class="mx-auto max-w-3xl">
                <div data-chat-suggestions class="mb-3 flex flex-wrap items-center gap-2">
                    @foreach ($suggestions as $suggestion)
                        <button type="button" data-chat-suggestion="{{ $suggestion }}"
                                class="rounded-full border border-emerald-200 bg-white px-3 py-1.5 text-xs font-medium text-emerald-700 transition hover:bg-emerald-50">
                            {{ $suggestion }}
                        </button>
                    @endforeach
                </div>

                <form data-chat-form class="flex items-end gap-2 rounded-2xl border border-slate-200 bg-white p-2 shadow-sm">
                    <label class="sr-only" for="chat-input">{{ __('ui.trip_planner_input_placeholder') }}</label>
                    <textarea id="chat-input" data-chat-input rows="1" maxlength="1000"
                              placeholder="{{ __('ui.trip_planner_input_placeholder') }}"
                              class="max-h-32 flex-1 resize-none border-0 bg-transparent p-2 text-sm text-slate-700 placeholder:text-slate-400 focus:ring-0"></textarea>
                    <button type="submit" data-chat-send
                            class="grid h-11 w-11 shrink-0 place-items-center rounded-full bg-emerald-600 text-white transition hover:bg-emerald-700 disabled:opacity-50"
                            aria-label="{{ __('ui.trip_planner_send') }}">
                        <x-heroicon name="paper-airplane" class="h-5 w-5" />
                    </button>
                </form>
            </div>
        </div>
    </div>

    @once
        <script>
            (function () {
                function init() {
                    var root = document.querySelector('[data-chat]');
                    if (! root || root.dataset.ready === '1') return;
                    root.dataset.ready = '1';

                    var endpoint = root.dataset.endpoint;
                    var clearEndpoint = root.dataset.clearEndpoint;
                    var errorText = root.dataset.error;
                    var quotaTemplate = root.dataset.quotaTemplate || '';

                    var scroller = root.querySelector('[data-chat-scroll]');
                    var wall = root.querySelector('[data-chat-messages]');
                    var form = root.querySelector('[data-chat-form]');
                    var input = root.querySelector('[data-chat-input]');
                    var sendBtn = root.querySelector('[data-chat-send]');
                    var typing = root.querySelector('[data-chat-typing]');
                    var clearBtn = root.querySelector('[data-chat-clear]');
                    var topBtn = root.querySelector('[data-chat-top]');
                    var bottomBtn = root.querySelector('[data-chat-bottom]');
                    var quota = root.querySelector('[data-chat-quota]');
                    var suggestions = root.querySelector('[data-chat-suggestions]');
                    // Layout publik kini menyertakan <meta name="csrf-token">.
                    var csrfMeta = document.querySelector('meta[name="csrf-token"]');
                    var csrf = csrfMeta ? csrfMeta.content : '';
                    var busy = false;

                    function updateQuota(remaining, limit) {
                        if (! quota || remaining == null) return;
                        quota.textContent = quotaTemplate
                            .replace('__R__', remaining)
                            .replace('__L__', limit);
                    }

                    function toggleTopButton() {
                        // Tampilkan navigasi gulir sesuai posisi: di atas (bila sudah
                        // tergulir jauh) dan ke bawah (bila tidak di dasar).
                        var y = scroller.scrollTop;
                        var max = scroller.scrollHeight - scroller.clientHeight;

                        if (topBtn) {
                            var showTop = y > 120;
                            topBtn.classList.toggle('hidden', ! showTop);
                            topBtn.classList.toggle('grid', showTop);
                        }

                        if (bottomBtn) {
                            var showBottom = max - y > 120;
                            bottomBtn.classList.toggle('hidden', ! showBottom);
                            bottomBtn.classList.toggle('grid', showBottom);
                        }
                    }

                    function scrollDown() {
                        // Pesan "mengalir ke bawah": selalu gulir ke pesan terbaru.
                        scroller.scrollTop = scroller.scrollHeight;
                    }

                    function nowTime() {
                        return new Date().toTimeString().slice(0, 5);
                    }

                    function bubble(role, text, time, html) {
                        var isUser = role === 'user';
                        var wrap = document.createElement('div');
                        wrap.className = 'flex ' + (isUser ? 'justify-end' : 'justify-start');

                        var inner = document.createElement('div');
                        inner.className = 'max-w-[80%] rounded-2xl px-4 py-2.5 text-sm shadow-sm ' +
                            (isUser ? 'rounded-br-sm bg-emerald-600 text-white' : 'rounded-bl-sm bg-white text-slate-700');

                        var body = document.createElement('div');
                        body.className = 'chat-body break-words';
                        if (html) {
                            // HTML sudah aman (di-render server-side oleh MarkdownLite).
                            body.innerHTML = html;
                        } else {
                            body.textContent = text;
                        }
                        inner.appendChild(body);

                        if (time) {
                            var meta = document.createElement('span');
                            meta.className = 'mt-1 block text-right text-[10px] ' + (isUser ? 'text-emerald-100' : 'text-slate-400');
                            meta.textContent = time;
                            inner.appendChild(meta);
                        }

                        wrap.appendChild(inner);
                        return wrap;
                    }

                    function render(messages) {
                        wall.innerHTML = '';
                        (messages || []).forEach(function (m) {
                            wall.appendChild(bubble(m.role, m.text, m.time, m.html));
                        });
                        scrollDown();
                    }

                    function appendMessage(role, text, time, html) {
                        wall.appendChild(bubble(role, text, time, html));
                        scrollDown();
                    }

                    function setBusy(state) {
                        busy = state;
                        sendBtn.disabled = state;
                        if (typing) typing.classList.toggle('hidden', ! state);
                        if (typing) typing.classList.toggle('flex', state);
                    }

                    function autoGrow() {
                        input.style.height = 'auto';
                        input.style.height = Math.min(input.scrollHeight, 128) + 'px';
                    }

                    function notify(text) {
                        // Balasan sistem bergaya asisten (mis. batas harian).
                        appendMessage('assistant', text, nowTime());
                    }

                    function send(message) {
                        message = (message || '').trim();
                        if (! message || busy) return;

                        appendMessage('user', message, nowTime());
                        input.value = '';
                        autoGrow();
                        if (suggestions) suggestions.classList.add('hidden');
                        setBusy(true);

                        fetch(endpoint, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrf,
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            body: JSON.stringify({ message: message }),
                        })
                            .then(function (res) {
                                return res.json().then(function (data) {
                                    return { ok: res.ok, status: res.status, data: data };
                                });
                            })
                            .then(function (result) {
                                if (! result.ok) {
                                    // Batas harian (429) atau kesalahan lain: tampilkan pesan sistem.
                                    notify(result.data && result.data.message ? result.data.message : errorText);
                                    return;
                                }

                                render(result.data.messages);
                                updateQuota(result.data.remaining != null ? result.data.remaining : null, result.data.limit);
                            })
                            .catch(function () { notify(errorText); })
                            .finally(function () { setBusy(false); });
                    }

                    form.addEventListener('submit', function (event) {
                        event.preventDefault();
                        send(input.value);
                    });

                    input.addEventListener('keydown', function (event) {
                        if (event.key === 'Enter' && ! event.shiftKey) {
                            event.preventDefault();
                            send(input.value);
                        }
                    });

                    input.addEventListener('input', autoGrow);

                    root.querySelectorAll('[data-chat-suggestion]').forEach(function (chip) {
                        chip.addEventListener('click', function () { send(chip.dataset.chatSuggestion); });
                    });

                    scroller.addEventListener('scroll', toggleTopButton);

                    if (topBtn) {
                        topBtn.addEventListener('click', function () {
                            scroller.scrollTo({ top: 0, behavior: 'smooth' });
                        });
                    }

                    if (bottomBtn) {
                        bottomBtn.addEventListener('click', function () {
                            scroller.scrollTo({ top: scroller.scrollHeight, behavior: 'smooth' });
                        });
                    }

                    if (clearBtn) {
                        clearBtn.addEventListener('click', function () {
                            if (busy || ! confirm('{{ __('ui.trip_planner_clear') }}?')) return;
                            setBusy(true);

                            fetch(clearEndpoint, {
                                method: 'DELETE',
                                headers: {
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': csrf,
                                    'X-Requested-With': 'XMLHttpRequest',
                                },
                            })
                                .then(function (res) { return res.ok ? res.json() : Promise.reject(); })
                                .then(function (data) {
                                    wall.innerHTML = '';
                                    appendMessage('assistant', data.welcome, nowTime());
                                    if (suggestions) suggestions.classList.remove('hidden');
                                    updateQuota(data.remaining != null ? data.remaining : null, data.limit);
                                })
                                .catch(function () {})
                                .finally(function () { setBusy(false); });
                        });
                    }

                    autoGrow();
                    scrollDown();
                    toggleTopButton();
                }

                document.addEventListener('DOMContentLoaded', init);
            })();
        </script>
    @endonce
</x-layouts.public>
