{{-- Text input with a show/hide toggle. Usage: <x-password-input wire:model="password" id="password" name="password" required /> --}}
<div x-data="{ show: false }" class="relative">
    <input type="password" x-bind:type="show ? 'text' : 'password'"
        {{ $attributes->merge(['class' => 'block w-full rounded-xl border border-input bg-card py-2.5 pl-4 pr-11 text-sm text-foreground shadow-sm outline-none transition placeholder:text-muted-foreground focus:border-ring focus:ring-2 focus:ring-ring/30 disabled:cursor-not-allowed disabled:opacity-60']) }}>
    <button type="button" x-on:click="show = ! show" tabindex="-1"
        class="absolute inset-y-0 right-0 grid w-11 place-items-center text-muted-foreground hover:text-foreground"
        x-bind:aria-label="show ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'">
        <x-lucide-eye x-show="! show" class="size-4" />
        <x-lucide-eye-off x-show="show" x-cloak class="size-4" />
    </button>
</div>
