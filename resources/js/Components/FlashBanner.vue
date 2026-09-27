<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';

const page = usePage();
</script>

<template>
    <div v-if="page.props.flash?.success || page.props.flash?.error" class="mb-5 space-y-3">
        <p v-if="page.props.flash.success" role="status" class="sneat-banner is-success">{{ page.props.flash.success }}</p>
        <p v-if="page.props.flash.error" role="alert" class="sneat-banner is-error">{{ page.props.flash.error }}</p>
    </div>
</template>
