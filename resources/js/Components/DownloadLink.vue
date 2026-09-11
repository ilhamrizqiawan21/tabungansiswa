<script setup lang="ts">
import { ref } from 'vue';
import { downloadFile } from '../activity';
const props = withDefaults(defineProps<{href:string;loadingText?:string}>(), {loadingText:'Menyiapkan unduhan…'});
const loading = ref(false);
async function download(event: MouseEvent) {
    if (event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
    event.preventDefault();
    if (loading.value) return;
    loading.value = true;
    try { await downloadFile(props.href, props.loadingText); }
    finally { loading.value = false; }
}
</script>
<template><a :href="href" class="download-link" :aria-busy="loading" :aria-disabled="loading || undefined" @click="download"><span v-if="loading" class="loading-spinner" aria-hidden="true"/><span>{{loading ? loadingText : ''}}<slot v-if="!loading"/></span></a></template>
