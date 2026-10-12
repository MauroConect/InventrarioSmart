@php
    $endpoint ??= '/api/support/conversations';
    $pollEvery ??= 5000;
@endphp

<div
    x-data="supportChatWidget(@js($endpoint), {{ (int) $pollEvery }})"
    x-init="init()"
    class="fixed bottom-5 right-5 z-40"
>
    <button
        type="button"
        @click="open = !open; if (open) unread = 0"
        class="relative ml-auto flex h-14 w-14 items-center justify-center rounded-full bg-blue-700 text-2xl text-white shadow-lg hover:bg-blue-800"
        aria-label="Abrir soporte"
    >?<span x-cloak x-show="unread > 0" x-text="unread" class="absolute -right-1 -top-1 min-w-5 rounded-full bg-red-600 px-1 text-xs font-bold leading-5"></span></button>

    <section
        x-cloak
        x-show="open"
        x-transition
        class="mt-3 flex h-[32rem] w-[min(24rem,calc(100vw-2.5rem))] flex-col overflow-hidden rounded-xl bg-white shadow-2xl ring-1 ring-black/10"
        aria-label="Chat de soporte"
    >
        <header class="bg-slate-800 px-4 py-3 text-white">
            <div class="font-semibold">Soporte InventarioSmart</div>
            <div class="text-xs text-slate-300">Las respuestas del equipo aparecen automáticamente.</div>
        </header>

        <div x-ref="messages" class="flex-1 space-y-3 overflow-y-auto bg-slate-50 p-4">
            <template x-if="loading">
                <p class="text-center text-sm text-slate-500">Cargando conversación…</p>
            </template>
            <template x-for="message in messages" :key="message.id">
                <div
                    class="max-w-[88%] rounded-lg px-3 py-2 text-sm"
                    :class="message.role === 'user'
                        ? 'ml-auto bg-blue-700 text-white'
                        : (message.role === 'platform' ? 'bg-emerald-100 text-emerald-950' : 'bg-white text-slate-800 shadow-sm')"
                >
                    <div
                        x-show="message.role === 'platform'"
                        class="mb-1 text-xs font-semibold text-emerald-700"
                    >Equipo de soporte</div>
                    <p class="whitespace-pre-wrap" x-text="message.body"></p>
                </div>
            </template>
            <p x-show="error" class="rounded bg-red-50 p-2 text-sm text-red-700" x-text="error"></p>
        </div>

        <form @submit.prevent="send()" class="border-t bg-white p-3">
            <div class="flex gap-2">
                <textarea
                    x-model="draft"
                    @keydown.enter.prevent="if (!$event.shiftKey) send()"
                    rows="2"
                    maxlength="4000"
                    required
                    placeholder="Contanos en qué podemos ayudarte"
                    class="min-h-11 flex-1 resize-none rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-600 focus:outline-none"
                ></textarea>
                <button
                    type="submit"
                    :disabled="sending || draft.trim().length < 2"
                    class="rounded-lg bg-blue-700 px-4 text-sm font-semibold text-white disabled:cursor-not-allowed disabled:opacity-50"
                >Enviar</button>
            </div>
        </form>
    </section>
</div>

@once
    <script>
        window.supportChatWidget = function (endpoint, pollEvery) {
            return {
                open: false,
                loading: true,
                sending: false,
                error: '',
                draft: '',
                conversationId: null,
                unread: 0,
                messages: [],
                lastId: 0,
                timer: null,

                async init() {
                    await this.resumeLatest();
                    this.timer = window.setInterval(() => this.poll(), pollEvery);
                },

                destroy() {
                    window.clearInterval(this.timer);
                },

                async resumeLatest() {
                    try {
                        const payload = await this.request(endpoint);
                        const latest = payload.data?.[0];
                        if (latest) {
                            this.unread = Number(latest.unread_count || 0);
                            this.conversationId = latest.id;
                            const conversation = await this.request(`${endpoint}/${latest.id}`);
                            this.messages = conversation.messages || [];
                            this.lastId = this.messages.at(-1)?.id || 0;
                        }
                    } catch (error) {
                        this.error = error.message;
                    } finally {
                        this.loading = false;
                        this.scroll();
                    }
                },

                async send() {
                    const body = this.draft.trim();
                    if (body.length < 2 || this.sending) return;

                    this.sending = true;
                    this.error = '';
                    this.draft = '';

                    try {
                        if (!this.conversationId) {
                            const conversation = await this.request(endpoint, {
                                method: 'POST',
                                body: JSON.stringify({ message: body, screen: window.location.pathname }),
                            });
                            this.conversationId = conversation.id;
                            this.messages = conversation.messages || [];
                        } else {
                            const conversation = await this.request(`${endpoint}/${this.conversationId}/messages`, {
                                method: 'POST',
                                body: JSON.stringify({ message: body, screen: window.location.pathname }),
                            });
                            this.messages = conversation.messages || this.messages;
                        }
                        this.lastId = this.messages.at(-1)?.id || this.lastId;
                        this.scroll();
                    } catch (error) {
                        this.draft = body;
                        this.error = error.message;
                    } finally {
                        this.sending = false;
                    }
                },

                async poll() {
                    if (!this.conversationId || document.hidden) return;

                    try {
                        const payload = await this.request(
                            `${endpoint}/${this.conversationId}/messages?after_id=${this.lastId}&mark_read=${this.open ? 1 : 0}`
                        );
                        if (payload.messages?.length) {
                            this.messages.push(...payload.messages);
                            if (!this.open) {
                                this.unread += payload.messages.filter(message => message.role === 'platform').length;
                            }
                            this.lastId = payload.last_id;
                            this.scroll();
                        }
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
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                            ...(options.headers || {}),
                        },
                        ...options,
                    });
                    const payload = await response.json().catch(() => ({}));
                    if (!response.ok) {
                        throw new Error(payload.message || 'No se pudo contactar a soporte.');
                    }
                    return payload;
                },

                scroll() {
                    this.$nextTick(() => {
                        if (this.$refs.messages) {
                            this.$refs.messages.scrollTop = this.$refs.messages.scrollHeight;
                        }
                    });
                },
            };
        };
    </script>
@endonce
