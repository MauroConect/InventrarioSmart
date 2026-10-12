<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $conversation->subject }} · Soporte</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>[x-cloak] { display: none !important; }</style>
</head>
<body class="min-h-screen bg-slate-100 text-slate-900">
    <main
        x-data="platformSupportThread(
            {{ $conversation->id }},
            @js($conversation->messages),
            @js($conversation->supportRequest)
        )"
        x-init="init()"
        class="mx-auto grid max-w-7xl gap-6 p-6 lg:grid-cols-[1fr_20rem]"
    >
        <section class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
            <header class="flex items-start justify-between border-b p-5">
                <div>
                    <a href="/platform/support" class="text-sm text-blue-700 hover:underline">← Volver al inbox</a>
                    <h1 class="mt-2 text-xl font-bold">{{ $conversation->subject }}</h1>
                    <p class="text-sm text-slate-500">
                        {{ $conversation->tenant->name }} · {{ $conversation->user->name }} ({{ $conversation->user->email }})
                    </p>
                </div>
                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold" x-text="request.status"></span>
            </header>

            <div x-ref="messages" class="h-[60vh] space-y-3 overflow-y-auto bg-slate-50 p-5">
                <template x-for="message in messages" :key="message.id">
                    <article
                        class="max-w-[85%] rounded-lg px-4 py-3"
                        :class="message.role === 'platform'
                            ? 'ml-auto bg-emerald-700 text-white'
                            : (message.role === 'user' ? 'bg-blue-100 text-blue-950' : 'bg-white shadow-sm')"
                    >
                        <div class="mb-1 text-xs font-semibold opacity-70" x-text="label(message)"></div>
                        <p class="whitespace-pre-wrap text-sm" x-text="message.body"></p>
                    </article>
                </template>
            </div>

            <form @submit.prevent="reply()" class="border-t p-4">
                <p x-show="error" x-cloak class="mb-2 rounded bg-red-50 p-2 text-sm text-red-700" x-text="error"></p>
                <div class="flex gap-3">
                    <textarea
                        x-model="draft"
                        rows="3"
                        maxlength="4000"
                        required
                        class="flex-1 resize-none rounded-lg border border-slate-300 p-3 text-sm focus:border-emerald-600 focus:outline-none"
                        placeholder="Responder al usuario"
                    ></textarea>
                    <button
                        :disabled="sending || draft.trim().length < 2"
                        class="rounded-lg bg-emerald-700 px-5 font-semibold text-white disabled:opacity-50"
                    >Responder</button>
                </div>
            </form>
        </section>

        <aside class="h-fit rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <h2 class="mb-4 font-bold">Solicitud</h2>
            <form @change="saveRequest()" class="space-y-4">
                <label class="block text-sm">
                    <span class="mb-1 block text-slate-600">Tipo</span>
                    <select x-model="request.type" class="w-full rounded border-slate-300">
                        <option value="support">Soporte</option>
                        <option value="bug">Bug</option>
                        <option value="feature_request">Mejora</option>
                    </select>
                </label>
                <label class="block text-sm">
                    <span class="mb-1 block text-slate-600">Prioridad</span>
                    <select x-model="request.priority" class="w-full rounded border-slate-300">
                        <option value="low">Baja</option>
                        <option value="normal">Normal</option>
                        <option value="high">Alta</option>
                    </select>
                </label>
                <label class="block text-sm">
                    <span class="mb-1 block text-slate-600">Estado</span>
                    <select x-model="request.status" class="w-full rounded border-slate-300">
                        <option value="open">Abierta</option>
                        <option value="in_progress">En curso</option>
                        <option value="resolved">Resuelta</option>
                        <option value="closed">Cerrada</option>
                    </select>
                </label>
            </form>
            <p x-show="saved" x-transition class="mt-3 text-xs font-medium text-emerald-700">Cambios guardados.</p>
        </aside>
    </main>

    <script>
        window.platformSupportThread = function (conversationId, initialMessages, initialRequest) {
            const base = `/platform/support/${conversationId}`;
            return {
                messages: initialMessages || [],
                request: initialRequest || {},
                draft: '',
                sending: false,
                saved: false,
                error: '',
                lastId: initialMessages.at(-1)?.id || 0,
                timer: null,

                init() {
                    this.scroll();
                    this.timer = window.setInterval(() => this.poll(), 5000);
                },

                destroy() {
                    window.clearInterval(this.timer);
                },

                label(message) {
                    if (message.role === 'platform') return message.sender?.name || 'Plataforma';
                    if (message.role === 'user') return message.sender?.name || 'Usuario';
                    if (message.role === 'assistant') return 'Asistente';
                    return 'Sistema';
                },

                async poll() {
                    if (document.hidden) return;
                    try {
                        const payload = await this.request(`${base}/messages?after_id=${this.lastId}`);
                        if (payload.messages?.length) {
                            this.messages.push(...payload.messages);
                            this.lastId = payload.last_id;
                            this.scroll();
                        }
                    } catch (error) {
                        this.error = error.message;
                    }
                },

                async reply() {
                    const body = this.draft.trim();
                    if (body.length < 2 || this.sending) return;
                    this.sending = true;
                    this.error = '';
                    try {
                        const message = await this.request(`${base}/messages`, {
                            method: 'POST',
                            body: JSON.stringify({ message: body }),
                        });
                        this.messages.push(message);
                        this.lastId = message.id;
                        this.request.status = 'in_progress';
                        this.draft = '';
                        this.scroll();
                    } catch (error) {
                        this.error = error.message;
                    } finally {
                        this.sending = false;
                    }
                },

                async saveRequest() {
                    this.saved = false;
                    this.error = '';
                    try {
                        this.request = await this.request(`${base}/request`, {
                            method: 'PATCH',
                            body: JSON.stringify({
                                status: this.request.status,
                                priority: this.request.priority,
                                type: this.request.type,
                            }),
                        });
                        this.saved = true;
                        window.setTimeout(() => this.saved = false, 1800);
                    } catch (error) {
                        this.error = error.message;
                    }
                },

                async request(url, options = {}) {
                    const response = await fetch(url, {
                        credentials: 'same-origin',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        ...options,
                    });
                    const payload = await response.json().catch(() => ({}));
                    if (!response.ok) throw new Error(payload.message || 'No se pudo completar la acción.');
                    return payload;
                },

                scroll() {
                    this.$nextTick(() => {
                        this.$refs.messages.scrollTop = this.$refs.messages.scrollHeight;
                    });
                },
            };
        };
    </script>
</body>
</html>
