<script setup lang="ts">
import { onBeforeUnmount, ref, watch } from 'vue';
import { activity, activityError, dismissActivityError } from '../activity';
const visible = ref(false);
const label = ref('');
let shownAt = 0;
let hideTimer: ReturnType<typeof setTimeout> | undefined;
watch(
    [activity.pending, activity.message],
    ([pending, message]) => {
        clearTimeout(hideTimer);
        if (pending) {
            if (!visible.value) shownAt = Date.now();
            label.value = message;
            visible.value = true;
        } else {
            hideTimer = setTimeout(
                () => {
                    visible.value = false;
                },
                Math.max(0, 280 - (Date.now() - shownAt)),
            );
        }
    },
    { immediate: true },
);
onBeforeUnmount(() => clearTimeout(hideTimer));
</script>
<template>
    <div class="activity-layer">
        <Transition name="loading-bar">
            <div v-if="visible" class="activity-bar" aria-hidden="true"><span /></div>
        </Transition>
        <div class="activity-announcer" role="status" aria-live="polite" aria-atomic="true">{{ activity.pending.value ? activity.message.value : '' }}</div>
        <Transition name="soft-toast">
            <div v-if="visible" class="activity-pill" aria-hidden="true">
                <span class="loading-spinner" />
                <span>{{ label }}</span>
            </div>
        </Transition>
        <Transition name="soft-toast">
            <div v-if="activityError" class="activity-error" role="alert">
                <span>{{ activityError }}</span>
                <button type="button" aria-label="Tutup pemberitahuan" @click="dismissActivityError">×</button>
            </div>
        </Transition>
    </div>
</template>
