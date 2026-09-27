<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import FormField from '../../Components/FormField.vue';

const form = useForm({ username: '', password: '' });
const submit = () => form.post('/login', { onFinish: () => form.reset('password') });
</script>

<template>
    <main class="sneat-login">
        <Head title="Masuk" />
        <section class="sneat-card w-full max-w-md p-8">
            <p class="sneat-eyebrow">Tabungan Siswa</p>
            <h1 class="mt-3 text-2xl font-bold text-slate-900">Masuk ke panel admin</h1>
            <p class="mt-2 text-sm text-slate-500">Kelola data tabungan sekolah dengan aman.</p>
            <form class="mt-8 space-y-5" @submit.prevent="submit">
                <fieldset :disabled="form.processing" class="space-y-5">
                    <FormField id="login-username" label="Username" :error="form.errors.username">
                        <input
                            id="login-username"
                            v-model="form.username"
                            class="w-full"
                            autocomplete="username"
                            required
                            autofocus
                            :aria-invalid="!!form.errors.username"
                            aria-describedby="login-username-error"
                        />
                    </FormField>
                    <FormField id="login-password" label="Password" :error="form.errors.password">
                        <input
                            id="login-password"
                            v-model="form.password"
                            type="password"
                            class="w-full"
                            autocomplete="current-password"
                            required
                            :aria-invalid="!!form.errors.password"
                            aria-describedby="login-password-error"
                        />
                    </FormField>
                </fieldset>
                <button :aria-busy="form.processing" :disabled="form.processing" class="sneat-primary w-full px-4 py-3 text-sm font-semibold">
                    {{ form.processing ? 'Memproses…' : 'Masuk' }}
                </button>
            </form>
        </section>
    </main>
</template>
