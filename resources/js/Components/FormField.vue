<script setup lang="ts">
defineProps<{ id: string; label: string; error?: string; hint?: string; optional?: boolean }>();
</script>

<template>
    <div>
        <label :for="id" class="sneat-label">
            {{ label }}
            <span v-if="optional" class="font-normal text-slate-500">(opsional)</span>
        </label>
        <slot />
        <p v-if="hint" :id="`${id}-hint`" class="mt-1 text-xs text-slate-500">{{ hint }}</p>
        <p :id="`${id}-error`" class="sneat-error">{{ error }}</p>
    </div>
</template>
