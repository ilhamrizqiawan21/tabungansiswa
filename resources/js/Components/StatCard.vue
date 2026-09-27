<script setup lang="ts">
withDefaults(
    defineProps<{ label: string; value: string; note?: string; tone?: 'default' | 'indigo' | 'emerald' | 'rose' | 'orange' | 'blue'; icon?: string }>(),
    { tone: 'default' },
);

/** Literal class strings so Tailwind's scanner can find them. */
const valueClasses: Record<string, string> = {
    default: 'text-slate-900',
    indigo: 'text-indigo-600',
    emerald: 'text-emerald-700',
    rose: 'text-rose-700',
    orange: 'text-slate-900',
    blue: 'text-slate-900',
};
const iconClasses: Record<string, string> = {
    default: 'bg-slate-100 text-slate-600',
    indigo: 'bg-indigo-100 text-indigo-600',
    emerald: 'bg-emerald-100 text-emerald-600',
    rose: 'bg-rose-100 text-rose-600',
    orange: 'bg-orange-100 text-orange-600',
    blue: 'bg-blue-100 text-blue-600',
};
</script>

<template>
    <article class="sneat-card flex items-start justify-between gap-3 p-5">
        <div class="min-w-0">
            <p class="text-sm text-slate-500">{{ label }}</p>
            <p class="mt-2 text-xl font-bold" :class="valueClasses[tone]">{{ value }}</p>
            <p v-if="note" class="mt-2 text-xs sneat-muted">{{ note }}</p>
        </div>
        <span v-if="icon" class="grid h-11 w-11 shrink-0 place-items-center rounded-lg" :class="iconClasses[tone]" aria-hidden="true">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                <path :d="icon" />
            </svg>
        </span>
    </article>
</template>
