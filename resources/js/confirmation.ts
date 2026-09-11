import { readonly, ref } from 'vue';
const current = ref<string | null>(null);
let resolve: ((confirmed: boolean) => void) | undefined;
export const confirmation = readonly(current);
export function answerConfirmation(confirmed: boolean) {
    const answer = resolve;
    resolve = undefined;
    current.value = null;
    answer?.(confirmed);
}
export function confirmAction(message: string): Promise<boolean> {
    answerConfirmation(false);
    current.value = message;
    return new Promise<boolean>(answer => { resolve = answer; });
}
