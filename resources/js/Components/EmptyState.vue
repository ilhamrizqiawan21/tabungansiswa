<script setup lang="ts">
defineProps<{ title: string; description?: string }>();
</script>

<template>
    <div class="sneat-empty">
        <p class="text-sm font-medium text-slate-600">{{ title }}</p>
        <p v-if="description" class="mt-1 text-xs sneat-muted">{{ description }}</p>
        <div v-if="$slots.default" class="mt-3"><slot /></div>
    </div>
</template>
