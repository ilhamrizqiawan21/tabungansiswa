<script setup lang="ts">
defineProps<{ title: string; description?: string; caption?: string }>();
</script>

<template>
    <section class="sneat-card overflow-hidden">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b px-5 py-4">
            <div>
                <h2 class="font-bold text-slate-900">{{ title }}</h2>
                <p v-if="description" class="mt-1 text-xs text-slate-500">{{ description }}</p>
            </div>
            <div v-if="$slots.actions" class="flex flex-wrap items-center gap-2"><slot name="actions" /></div>
        </div>
        <slot name="before" />
        <div class="overflow-x-auto">
            <table class="sneat-table w-full text-left text-sm">
                <caption class="sr-only">
                    {{ caption ?? title }}
                </caption>
                <slot />
            </table>
        </div>
        <slot name="footer" />
    </section>
</template>
