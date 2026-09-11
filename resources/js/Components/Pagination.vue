<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
const props = defineProps<{ data: { current_page: number; last_page: number; total: number; from?: number|null; to?: number|null; prev_page_url?: string|null; next_page_url?: string|null; links?: Array<{url:string|null;label:string;active:boolean}> } }>();
const pages = computed(() => (props.data.links ?? []).filter(link => /^\d+$/.test(link.label) || link.label === '...'));
const number = (value:number) => value.toLocaleString('id-ID');
</script>
<template>
    <nav aria-label="Navigasi halaman" class="data-pagination">
        <p class="pagination-summary"><strong>{{ number(data.from ?? 0) }}–{{ number(data.to ?? 0) }}</strong> dari {{ number(data.total) }} data</p>
        <div class="pagination-controls">
            <Link v-if="data.prev_page_url" :href="data.prev_page_url" class="pagination-button" aria-label="Halaman sebelumnya" preserve-scroll><span aria-hidden="true">←</span><span class="hidden sm:inline">Sebelumnya</span></Link>
            <button v-else type="button" disabled class="pagination-button" aria-label="Halaman sebelumnya"><span aria-hidden="true">←</span><span class="hidden sm:inline">Sebelumnya</span></button>
            <div class="hidden items-center gap-1 sm:flex">
                <template v-for="(link,index) in pages" :key="index">
                    <span v-if="!link.url" class="pagination-ellipsis">{{link.label}}</span>
                    <Link v-else :href="link.url" class="pagination-button pagination-number" :class="{'is-current':link.active}" :aria-current="link.active?'page':undefined" :aria-label="'Halaman '+link.label" preserve-scroll>{{link.label}}</Link>
                </template>
            </div>
            <span class="pagination-summary sm:hidden">{{data.current_page}} / {{data.last_page}}</span>
            <Link v-if="data.next_page_url" :href="data.next_page_url" class="pagination-button" aria-label="Halaman berikutnya" preserve-scroll><span class="hidden sm:inline">Berikutnya</span><span aria-hidden="true">→</span></Link>
            <button v-else type="button" disabled class="pagination-button" aria-label="Halaman berikutnya"><span class="hidden sm:inline">Berikutnya</span><span aria-hidden="true">→</span></button>
        </div>
    </nav>
</template>
