<script setup>
import { Head } from '@inertiajs/vue3';
import { Bell, Plus, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import Avatar from '@/Components/ui/Avatar.vue';
import Azulejo from '@/Components/ui/Azulejo.vue';
import Badge from '@/Components/ui/Badge.vue';
import Button from '@/Components/ui/Button.vue';
import Checkbox from '@/Components/ui/Checkbox.vue';
import DateBlock from '@/Components/ui/DateBlock.vue';
import Dialog from '@/Components/ui/Dialog.vue';
import EmptyState from '@/Components/ui/EmptyState.vue';
import FormField from '@/Components/ui/FormField.vue';
import IconButton from '@/Components/ui/IconButton.vue';
import Input from '@/Components/ui/Input.vue';
import Kicker from '@/Components/ui/Kicker.vue';
import PageHeader from '@/Components/ui/PageHeader.vue';
import Select from '@/Components/ui/Select.vue';
import Skeleton from '@/Components/ui/Skeleton.vue';
import Tabs from '@/Components/ui/Tabs.vue';
import Textarea from '@/Components/ui/Textarea.vue';
import ThemeToggle from '@/Components/ui/ThemeToggle.vue';
import Tooltip from '@/Components/ui/Tooltip.vue';
import { confirmDialog } from '@/Components/ui/confirm';
import { toast } from '@/Components/ui/toast';
import { t } from '@/i18n';

const swatches = [
    ['canvas', 'bg-canvas'], ['surface', 'bg-surface'], ['sunken', 'bg-sunken'], ['line', 'bg-line'],
    ['ink', 'bg-ink'], ['ink-muted', 'bg-ink-muted'], ['primary', 'bg-primary'], ['primary-soft', 'bg-primary-soft'],
    ['accent', 'bg-accent'], ['accent-soft', 'bg-accent-soft'], ['success', 'bg-success'], ['warning', 'bg-warning'],
    ['danger', 'bg-danger'], ['info', 'bg-info'],
];
const scale = ['50', '100', '200', '300', '400', '500', '600', '700', '800', '900'];
const variants = ['primary', 'secondary', 'accent', 'ghost', 'danger', 'link'];
const tones = ['neutral', 'primary', 'accent', 'success', 'warning', 'danger', 'info', 'outline'];
const tab = ref('a');
const dialogOpen = ref(false);
const checked = ref(true);
const today = new Date().toISOString();
</script>

<template>
    <Head :title="$t('styleguide.title')" />

    <AppLayout>
        <PageHeader :title="$t('styleguide.title')" :lead="$t('styleguide.intro')" :kicker="$t('app.name')">
            <template #actions><ThemeToggle /></template>
        </PageHeader>

        <section class="mt-10">
            <h2 class="font-display text-title-2 font-semibold">{{ $t('styleguide.colors') }}</h2>
            <ul class="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-7">
                <li v-for="[name, cls] in swatches" :key="name">
                    <div class="h-16 rounded border border-line" :class="cls" />
                    <p class="mt-2 font-mono text-xs text-ink-muted">{{ name }}</p>
                </li>
            </ul>
            <div v-for="family in ['red', 'gold', 'sand']" :key="family" class="mt-6">
                <p class="kicker">{{ family }}</p>
                <div class="mt-2 grid grid-cols-10 overflow-hidden rounded border border-line">
                    <div v-for="step in scale" :key="step" :class="`bg-${family}-${step}`" class="h-10" :title="`${family}-${step}`" />
                </div>
            </div>
        </section>

        <section class="mt-14 border-t border-line pt-10">
            <h2 class="font-display text-title-2 font-semibold">{{ $t('styleguide.type') }}</h2>
            <div class="mt-6 space-y-5">
                <p class="font-display text-display font-semibold">{{ $t('styleguide.sample_heading') }}</p>
                <p class="font-display text-title-1 font-semibold">{{ $t('styleguide.sample_heading') }}</p>
                <p class="font-display text-title-2 font-semibold">{{ $t('styleguide.sample_heading') }}</p>
                <p class="font-display text-title-3 font-semibold">{{ $t('styleguide.sample_heading') }}</p>
                <Kicker>{{ $t('app.patron') }}</Kicker>
                <p class="prose-body">{{ $t('styleguide.sample_text') }}</p>
                <p class="font-display text-xl italic text-ink-muted">{{ $t('welcome.quote') }}</p>
            </div>
        </section>

        <section class="mt-14 border-t border-line pt-10">
            <h2 class="font-display text-title-2 font-semibold">{{ $t('styleguide.components') }}</h2>

            <div class="mt-6 flex flex-wrap items-center gap-3">
                <Button v-for="v in variants" :key="v" :variant="v">{{ v }}</Button>
                <Button loading>{{ $t('common.loading') }}</Button>
                <Button disabled>{{ $t('common.save') }}</Button>
                <Button size="sm"><Plus aria-hidden="true" />{{ $t('common.save') }}</Button>
                <Tooltip :text="$t('common.delete')"><IconButton :label="$t('common.delete')"><Trash2 class="size-4" aria-hidden="true" /></IconButton></Tooltip>
            </div>

            <div class="mt-6 flex flex-wrap gap-2">
                <Badge v-for="tone in tones" :key="tone" :tone="tone">{{ tone }}</Badge>
            </div>

            <div class="mt-8 grid gap-6 md:grid-cols-2">
                <FormField v-slot="field" :label="$t('styleguide.sample_heading')" :hint="$t('styleguide.intro')">
                    <Input :id="field.id" :placeholder="$t('questions.search_placeholder')" :aria-describedby="field.describedby" />
                </FormField>
                <FormField v-slot="field" :label="$t('scope.label')" :error="$t('common.confirm_delete')">
                    <Select :id="field.id" :invalid="field.invalid" :aria-describedby="field.describedby"><option>{{ $t('exams.all_scopes') }}</option></Select>
                </FormField>
                <FormField v-slot="field" :label="$t('answers.body')" class="md:col-span-2">
                    <Textarea :id="field.id" rows="3" :placeholder="$t('answers.placeholder')" />
                </FormField>
                <label class="flex items-center gap-3 text-sm"><Checkbox v-model:checked="checked" />{{ $t('auth.remember') }}</label>
            </div>

            <div class="mt-8 flex flex-wrap items-center gap-6">
                <Avatar name="Мигел Сервантес" size="sm" />
                <Avatar name="Мигел Сервантес" />
                <Avatar name="Мигел Сервантес" size="lg" />
                <DateBlock :date="today" />
                <DateBlock :date="today" size="lg" />
            </div>

            <div class="mt-8">
                <Tabs v-model="tab" :label="$t('styleguide.components')" :items="[{ value: 'a', label: $t('nav.exams') }, { value: 'b', label: $t('nav.questions') }, { value: 'c', label: $t('nav.clubs') }]">
                    <template #a><p class="py-4 text-ink-muted">{{ $t('exams.intro') }}</p></template>
                    <template #b><p class="py-4 text-ink-muted">{{ $t('questions.intro') }}</p></template>
                    <template #c><p class="py-4 text-ink-muted">{{ $t('clubs.intro') }}</p></template>
                </Tabs>
            </div>

            <div class="mt-8 flex flex-wrap gap-3">
                <Button variant="secondary" @click="toast({ title: $t('styleguide.title'), description: $t('styleguide.intro') })"><Bell aria-hidden="true" />Toast</Button>
                <Button variant="secondary" @click="confirmDialog(t('common.confirm_delete'))">Confirm</Button>
                <Dialog v-model:open="dialogOpen" :title="$t('styleguide.title')" :description="$t('styleguide.intro')">
                    <template #trigger><Button variant="secondary">Dialog</Button></template>
                    <p class="text-ink-muted">{{ $t('styleguide.sample_text') }}</p>
                    <template #footer><Button @click="dialogOpen = false">{{ $t('common.cancel') }}</Button></template>
                </Dialog>
            </div>

            <div class="mt-8 grid gap-6 md:grid-cols-2">
                <div class="space-y-3"><Skeleton class="h-6 w-2/3" /><Skeleton class="h-4" /><Skeleton class="h-4 w-5/6" /></div>
                <div class="relative h-40 overflow-hidden rounded bg-primary"><Azulejo /></div>
            </div>

            <EmptyState class="mt-8" :title="$t('exams.empty')" :hint="$t('exams.empty_hint')" />
        </section>
    </AppLayout>
</template>
