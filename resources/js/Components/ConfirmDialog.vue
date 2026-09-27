<script setup lang="ts">
import { onBeforeUnmount, ref, watch } from 'vue';
import { answerConfirmation, confirmation, confirmationOptions } from '../confirmation';

const dialog = ref<HTMLDialogElement | null>(null);
const message = ref('');
watch(
    confirmation,
    value => {
        if (value) {
            message.value = value;
            dialog.value?.showModal();
        } else {
            dialog.value?.close();
        }
    },
    { flush: 'post' },
);
onBeforeUnmount(() => answerConfirmation(false));
</script>

<template>
    <dialog
        ref="dialog"
        class="confirmation-dialog"
        aria-labelledby="confirm-title"
        aria-describedby="confirm-message"
        @cancel.prevent="answerConfirmation(false)"
    >
        <div class="confirmation-icon" :class="{ 'is-primary': confirmationOptions.tone === 'primary' }" aria-hidden="true">
            <svg
                v-if="confirmationOptions.tone === 'danger'"
                width="22"
                height="22"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="1.7"
                stroke-linecap="round"
            >
                <path d="M3 6h18M9 6V4h6v2M5 6l1 14h12l1-14M10 10v6m4-6v6" />
            </svg>
            <svg v-else width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round">
                <path d="M12 8v5m0 3h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </svg>
        </div>
        <h2 id="confirm-title" class="mt-4 text-lg font-bold text-slate-900">{{ confirmationOptions.title }}</h2>
        <p id="confirm-message" class="mt-2 text-sm leading-relaxed text-slate-600">{{ message }}</p>
        <div class="mt-6 flex justify-end gap-3">
            <button type="button" class="confirmation-cancel" autofocus @click="answerConfirmation(false)">Batal</button>
            <button
                type="button"
                :class="confirmationOptions.tone === 'danger' ? 'confirmation-delete' : 'sneat-primary px-4 py-2 text-sm font-semibold'"
                @click="answerConfirmation(true)"
            >
                {{ confirmationOptions.confirmLabel }}
            </button>
        </div>
    </dialog>
</template>
