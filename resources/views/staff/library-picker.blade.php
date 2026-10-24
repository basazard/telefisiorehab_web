{{-- Exercise library with area/type filters. $action: Livewire method called with the exercise id; $selected: chosen ids. --}}
<div class="rounded-xl border border-border p-3">
    <p class="text-sm font-medium">Tambah latihan dari pustaka</p>
    <div class="mt-2 grid gap-2 sm:grid-cols-2">
        <x-ui.select label="Area tubuh" wire:model.live="area">
            <option value="">Semua area</option>
            @foreach ($this->areas as $a)
                <option value="{{ $a }}">{{ \App\Support\Onboarding\BodyRegions::AREA_LABELS[$a] ?? str_replace('_', ' ', $a) }}</option>
            @endforeach
        </x-ui.select>
        <x-ui.select label="Jenis latihan" wire:model.live="type">
            <option value="">Semua jenis</option>
            @foreach ($types as $t)
                <option value="{{ $t->value }}">{{ $t->label() }}</option>
            @endforeach
        </x-ui.select>
    </div>
    <ul class="mt-3 max-h-64 divide-y divide-border overflow-y-auto">
        @forelse ($this->library as $exercise)
            @php $on = in_array($exercise->id, array_map('intval', $selected), true); @endphp
            <li wire:key="lib-{{ $exercise->id }}" class="flex items-center justify-between gap-2 py-2 text-sm">
                <span>{{ $exercise->name }} <span class="text-xs text-muted-foreground">· {{ $exercise->type->label() }}</span></span>
                <x-ui.button size="sm" :variant="$on ? 'default' : 'outline'" wire:click="{{ $action }}({{ $exercise->id }})">
                    {{ $on ? 'Dipilih' : 'Pilih' }}
                </x-ui.button>
            </li>
        @empty
            <li class="py-2 text-sm text-muted-foreground">Tidak ada latihan yang cocok.</li>
        @endforelse
    </ul>
    @error('add') <p class="mt-2 text-xs text-destructive">{{ $message }}</p> @enderror
</div>
