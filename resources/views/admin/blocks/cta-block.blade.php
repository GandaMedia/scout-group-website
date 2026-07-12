<div class="rounded-xl bg-scout-red px-4 py-4 text-white">
    <h3 class="text-lg font-bold">{{ data_get($block, 'data.title') }}</h3>
    <p class="mt-2 text-sm text-white/80">{{ data_get($block, 'data.body') }}</p>
    <p class="mt-3 text-xs font-semibold uppercase tracking-[0.16em] text-white/70">
        {{ data_get($block, 'data.button_label') }}
    </p>
</div>
