{{-- Already included in layouts/app.blade.php.
     From Livewire:  $this->dispatch('toast', message: 'Profil tersimpan', type: 'success');
     From JS/Alpine: $dispatch('toast', { message: 'Hello' })   type: default | success | error --}}
<div x-data="{
        toasts: [],
        add(d) {
            const t = { id: Date.now() + Math.random(), message: d.message ?? '', description: d.description ?? null, type: d.type ?? 'default' };
            this.toasts.push(t);
            setTimeout(() => this.toasts = this.toasts.filter(x => x.id !== t.id), d.duration ?? 4000);
        },
    }"
    x-on:toast.window="add($event.detail)"
    class="pointer-events-none fixed bottom-4 right-4 z-[100] flex w-full max-w-sm flex-col gap-2">
    <template x-for="t in toasts" :key="t.id">
        <div x-transition class="pointer-events-auto rounded-lg border bg-background p-4 text-foreground shadow-lg"
             x-bind:class="t.type === 'error' ? 'border-destructive text-destructive' : 'border-border'">
            <p class="text-sm font-medium" x-text="t.message"></p>
            <p x-show="t.description" class="text-sm text-muted-foreground" x-text="t.description"></p>
        </div>
    </template>
</div>
