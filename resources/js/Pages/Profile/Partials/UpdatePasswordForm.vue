<script setup>
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import Button from '@/Components/ui/Button.vue';
import FormField from '@/Components/ui/FormField.vue';
import Input from '@/Components/ui/Input.vue';

const passwordInput = ref(null);
const currentPasswordInput = ref(null);

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const updatePassword = () => {
    form.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
        onError: () => {
            if (form.errors.password) {
                form.reset('password', 'password_confirmation');
                passwordInput.value.focus();
            }
            if (form.errors.current_password) {
                form.reset('current_password');
                currentPasswordInput.value.focus();
            }
        },
    });
};
</script>

<template>
    <section class="grid gap-6 lg:grid-cols-3">
        <header>
            <h2 class="font-display text-title-3 font-semibold text-ink">{{ $t('profile.password_title') }}</h2>
            <p class="mt-2 text-ink-muted">{{ $t('profile.password_intro') }}</p>
        </header>

        <form class="flex max-w-xl flex-col gap-5 lg:col-span-2" @submit.prevent="updatePassword">
            <FormField v-slot="field" id="current_password" :label="$t('profile.current_password')" :error="form.errors.current_password">
                <Input :id="field.id" ref="currentPasswordInput" v-model="form.current_password" type="password" autocomplete="current-password" :invalid="field.invalid" :aria-describedby="field.describedby" />
            </FormField>
            <FormField v-slot="field" id="password" :label="$t('profile.new_password')" :error="form.errors.password">
                <Input :id="field.id" ref="passwordInput" v-model="form.password" type="password" autocomplete="new-password" :invalid="field.invalid" :aria-describedby="field.describedby" />
            </FormField>
            <FormField v-slot="field" id="password_confirmation" :label="$t('profile.password_confirmation')" :error="form.errors.password_confirmation">
                <Input :id="field.id" v-model="form.password_confirmation" type="password" autocomplete="new-password" :invalid="field.invalid" :aria-describedby="field.describedby" />
            </FormField>

            <div class="flex items-center gap-4">
                <Button type="submit" :loading="form.processing">{{ $t('common.save') }}</Button>
                <Transition enter-active-class="transition duration-200" enter-from-class="opacity-0" leave-active-class="transition duration-150" leave-to-class="opacity-0">
                    <p v-if="form.recentlySuccessful" class="text-sm font-medium text-success" role="status">{{ $t('common.saved') }}</p>
                </Transition>
            </div>
        </form>
    </section>
</template>
