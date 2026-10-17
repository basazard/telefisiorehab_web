{{--
    Converted from DashboardShell.tsx
    Props: role (label), userName, nav = [['label','route','icon'], ...]
    The legacy "Switch role" demo box is intentionally dropped: roles are real accounts now.
--}}
@props(['role', 'userName', 'nav' => []])

<div class="min-h-screen flex w-full bg-background">
    <aside class="hidden md:flex w-64 shrink-0 flex-col border-r border-sidebar-border bg-sidebar">
        <div class="px-6 py-6 flex items-center gap-2">
            <div class="size-9 rounded-xl bg-[image:var(--gradient-hero)] grid place-items-center text-primary-foreground">
                <x-lucide-activity class="size-5" />
            </div>
            <div>
                <p class="font-display font-bold text-sidebar-foreground leading-tight">TeleFisioRehab</p>
                <p class="text-[11px] text-muted-foreground">Home Rehab Platform</p>
            </div>
        </div>

        <div class="px-3 mt-2">
            <p class="px-3 text-[11px] uppercase tracking-wider text-muted-foreground mb-2">{{ $role }} Menu</p>
            <nav class="flex flex-col gap-1">
                @foreach ($nav as $item)
                    @php $active = request()->routeIs($item['route']); @endphp
                    <a href="{{ route($item['route']) }}" wire:navigate
                       @class([
                           'flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors',
                           'bg-sidebar-primary text-sidebar-primary-foreground shadow-[var(--shadow-soft)]' => $active,
                           'text-sidebar-foreground hover:bg-sidebar-accent' => ! $active,
                       ])>
                        <x-dynamic-component :component="'lucide-'.$item['icon']" class="size-4" />
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </nav>
        </div>

        <div class="mt-auto p-4">
            <a href="{{ route('profile') }}" wire:navigate
               class="block rounded-xl bg-[image:var(--gradient-soft)] p-4 border border-sidebar-border text-xs font-semibold text-sidebar-foreground">
                Account settings
            </a>
        </div>
    </aside>

    <div class="flex-1 flex flex-col min-w-0">
        <header class="h-16 px-4 md:px-8 border-b border-border bg-card/60 backdrop-blur flex items-center justify-between sticky top-0 z-10">
            <div class="flex items-center gap-3 flex-1 max-w-md">
                <div class="relative flex-1">
                    <x-lucide-search class="size-4 absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground" />
                    <input placeholder="Search exercises, patients…"
                           class="w-full h-10 pl-9 pr-3 rounded-lg bg-muted text-sm outline-none focus:ring-2 focus:ring-ring" />
                </div>
            </div>
            <div class="flex items-center gap-3">
                <button type="button" class="size-10 rounded-lg grid place-items-center bg-muted hover:bg-secondary transition-colors relative">
                    <x-lucide-bell class="size-4" />
                    <span class="absolute top-2 right-2 size-2 rounded-full bg-accent"></span>
                </button>
                <div class="flex items-center gap-2 pl-2 border-l">
                    <div class="size-9 rounded-full bg-[image:var(--gradient-hero)] grid place-items-center text-primary-foreground text-sm font-semibold">
                        {{ mb_substr($userName, 0, 1) }}
                    </div>
                    <div class="hidden sm:block">
                        <p class="text-sm font-semibold leading-tight">{{ $userName }}</p>
                        <p class="text-[11px] text-muted-foreground">{{ $role }}</p>
                    </div>
                </div>
            </div>
        </header>

        <main class="flex-1 p-4 md:p-8">{{ $slot }}</main>
    </div>
</div>
