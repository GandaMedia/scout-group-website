<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section
            heading="Connection overview"
            description="This site uses the OAuth app configured in your environment. Reconnect if the tokens have expired or the OSM app has changed."
        >
            <div class="grid gap-4 lg:grid-cols-[minmax(0,1.2fr)_minmax(0,1fr)]">
                <div class="flex flex-col gap-4 rounded-xl bg-gray-50/80 p-4 ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
                    <div class="flex flex-col gap-1">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400">
                            Connection status
                        </p>
                        <div class="flex flex-wrap items-center gap-3">
                            <span @class([
                                'inline-flex items-center rounded-full px-2.5 py-1 text-sm font-medium ring-1 ring-inset',
                                'bg-success-50 text-success-700 ring-success-600/20 dark:bg-success-500/10 dark:text-success-300 dark:ring-success-500/30' => $this->connected,
                                'bg-gray-100 text-gray-700 ring-gray-300 dark:bg-gray-800 dark:text-gray-300 dark:ring-gray-700' => ! $this->connected,
                            ])>
                                {{ $this->connected ? 'Connected' : 'Not connected' }}
                            </span>

                            @if ($this->connectedAccount)
                                <span class="text-sm text-gray-600 dark:text-gray-300">{{ $this->connectedAccount }}</span>
                            @endif
                        </div>
                    </div>

                    @if ($this->directoryError)
                        <div class="rounded-lg border border-warning-200 bg-warning-50 px-3 py-2 text-sm text-warning-800 dark:border-warning-500/30 dark:bg-warning-500/10 dark:text-warning-200">
                            {{ $this->directoryError }}
                        </div>
                    @endif

                    <div class="flex flex-wrap gap-x-4 gap-y-2 text-xs text-gray-500 dark:text-gray-400">
                        @if ($this->directoryRefreshedAt)
                            <span>Last refreshed: {{ \Illuminate\Support\Carbon::parse($this->directoryRefreshedAt)->format('j M Y H:i') }}</span>
                        @endif

                        @if ($this->directoryRefreshQueuedAt)
                            <span>Refresh queued: {{ \Illuminate\Support\Carbon::parse($this->directoryRefreshQueuedAt)->format('j M Y H:i') }}</span>
                        @endif
                    </div>
                </div>

                <dl class="grid gap-3 sm:grid-cols-3 lg:grid-cols-1">
                    <div class="flex flex-col gap-1 rounded-xl bg-white p-4 ring-1 ring-gray-950/5 dark:bg-gray-900/60 dark:ring-white/10">
                        <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500 dark:text-gray-400">Client ID</dt>
                        <dd class="break-all text-sm font-medium text-gray-950 dark:text-white">
                            {{ $this->oauthClientId ?: 'Not configured' }}
                        </dd>
                    </div>

                    <div class="flex flex-col gap-1 rounded-xl bg-white p-4 ring-1 ring-gray-950/5 dark:bg-gray-900/60 dark:ring-white/10">
                        <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500 dark:text-gray-400">Redirect URI</dt>
                        <dd class="break-all text-sm font-medium text-gray-950 dark:text-white">
                            {{ $this->redirectUri ?: 'Not configured' }}
                        </dd>
                    </div>

                    <div class="flex flex-col gap-1 rounded-xl bg-white p-4 ring-1 ring-gray-950/5 dark:bg-gray-900/60 dark:ring-white/10">
                        <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500 dark:text-gray-400">Requested scopes</dt>
                        <dd class="break-all text-sm font-medium text-gray-950 dark:text-white">
                            {{ $this->requestedScopes ?: 'Not configured' }}
                        </dd>
                    </div>
                </dl>
            </div>
        </x-filament::section>

        <form wire:submit="save" class="space-y-6">
            {{ $this->form }}

            <x-filament::button type="submit">
                Save OSM mappings
            </x-filament::button>
        </form>
    </div>
</x-filament-panels::page>
