<div class="rounded-xl bg-scout-green px-4 py-4 text-white">
    <h3 class="text-lg font-bold">{{ data_get($block, 'data.title') }}</h3>
    <div class="mt-2 text-sm text-white/80">
        {!! \Illuminate\Support\Str::markdown((string) data_get($block, 'data.content', '')) !!}
    </div>
</div>
