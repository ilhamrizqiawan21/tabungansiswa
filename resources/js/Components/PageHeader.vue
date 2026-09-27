<script setup lang="ts">
defineProps<{ eyebrow?: string; title: string; description?: string }>();
</script>

<template>
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <p v-if="eyebrow" class="sneat-eyebrow">{{ eyebrow }}</p>
            <h1 class="mt-2 text-2xl font-bold text-slate-900">{{ title }}</h1>
            <p v-if="description || $slots.description" class="mt-1 text-sm text-slate-500">
                <slot name="description">{{ description }}</slot>
            </p>
        </div>
        <div v-if="$slots.actions" class="flex flex-wrap items-center gap-2">
            <slot name="actions" />
        </div>
    </div>
</template>
