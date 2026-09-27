import { readonly, ref } from 'vue';

export interface ConfirmOptions {
    title?: string;
    confirmLabel?: string;
    /** `danger` shows the trash icon and a red button; `primary` is for non-destructive changes. */
    tone?: 'danger' | 'primary';
}

const defaults: Required<ConfirmOptions> = { title: 'Hapus data?', confirmLabel: 'Ya, hapus', tone: 'danger' };
const current = ref<string | null>(null);
const options = ref<Required<ConfirmOptions>>({ ...defaults });
let resolve: ((confirmed: boolean) => void) | undefined;

/** The pending confirmation message, or null when no dialog is open. */
export const confirmation = readonly(current);
export const confirmationOptions = readonly(options);

export function answerConfirmation(confirmed: boolean) {
    const answer = resolve;
    resolve = undefined;
    current.value = null;
    answer?.(confirmed);
}

export function confirmAction(message: string, overrides: ConfirmOptions = {}): Promise<boolean> {
    answerConfirmation(false);
    options.value = { ...defaults, ...overrides };
    current.value = message;
    return new Promise<boolean>(answer => {
        resolve = answer;
    });
}
