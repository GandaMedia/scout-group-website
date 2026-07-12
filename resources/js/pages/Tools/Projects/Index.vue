<script setup lang="ts">
import ProjectController from '@/actions/App/Http/Controllers/MealPlanner/ProjectController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { show as mealPlannerShow } from '@/routes/meal-planner';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { Copy, Pencil, Plus, Trash2, Utensils } from 'lucide-vue-next';
import { ref } from 'vue';

interface ProjectSummary {
    id: number;
    name: string;
    people_count: number;
    event_date: string;
    totals: {
        total_cost_minor: number;
        cost_per_head_minor: number;
        total_calories_per_serving: number;
        meal_count: number;
    };
}

defineProps<{
    projects: ProjectSummary[];
}>();

const editingProjectId = ref<number | null>(null);

const projectForm = useForm({
    name: '',
    people_count: 1,
    event_date: new Date().toISOString().slice(0, 10),
});

const editProjectForm = useForm({
    name: '',
    people_count: 1,
    event_date: new Date().toISOString().slice(0, 10),
});

function money(minor: number | null | undefined): string {
    return new Intl.NumberFormat('en-GB', {
        style: 'currency',
        currency: 'GBP',
    }).format((minor ?? 0) / 100);
}

function createProject(): void {
    projectForm.submit(ProjectController.store(), {
        preserveScroll: true,
        onSuccess: () => projectForm.reset('name'),
    });
}

function startEditing(project: ProjectSummary): void {
    editingProjectId.value = project.id;
    editProjectForm.name = project.name;
    editProjectForm.people_count = project.people_count;
    editProjectForm.event_date = project.event_date;
}

function updateProject(): void {
    if (!editingProjectId.value) {
        return;
    }

    editProjectForm.submit(ProjectController.update(editingProjectId.value), {
        preserveScroll: true,
        onSuccess: () => {
            editingProjectId.value = null;
        },
    });
}
</script>

<template>
    <AppLayout>
        <Head title="Projects" />

        <main
            class="bg-slate-50 py-6 text-slate-950 dark:bg-slate-950 dark:text-slate-50"
        >
            <div class="mx-auto max-w-7xl space-y-4 px-4">
                <section class="rounded-lg bg-scout-purple p-5 text-white">
                    <div
                        class="flex flex-col gap-2 md:flex-row md:items-end md:justify-between"
                    >
                        <div>
                            <p
                                class="text-sm font-bold tracking-wide uppercase opacity-80"
                            >
                                Tools
                            </p>
                            <h1 class="text-3xl font-extrabold">Projects</h1>
                        </div>
                        <p class="max-w-2xl text-sm leading-6 text-white/85">
                            Create the project once, then open the tools that
                            apply to it. Meal planning is the first project tool
                            here.
                        </p>
                    </div>
                </section>

                <section
                    class="rounded-lg border bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900"
                >
                    <form
                        class="grid gap-3 md:grid-cols-[1fr_9rem_10rem_auto]"
                        @submit.prevent="createProject"
                    >
                        <div class="space-y-1">
                            <Label for="project-name">Project</Label>
                            <Input
                                id="project-name"
                                v-model="projectForm.name"
                            />
                            <InputError :message="projectForm.errors.name" />
                        </div>
                        <div class="space-y-1">
                            <Label for="project-people">People</Label>
                            <Input
                                id="project-people"
                                v-model="projectForm.people_count"
                                type="number"
                                min="1"
                            />
                            <InputError
                                :message="projectForm.errors.people_count"
                            />
                        </div>
                        <div class="space-y-1">
                            <Label for="project-date">Event date</Label>
                            <Input
                                id="project-date"
                                v-model="projectForm.event_date"
                                type="date"
                            />
                            <InputError
                                :message="projectForm.errors.event_date"
                            />
                        </div>
                        <Button
                            class="self-end bg-scout-green text-white hover:bg-scout-forest-green"
                            :disabled="projectForm.processing"
                        >
                            <Plus class="mr-2 size-4" />
                            Create
                        </Button>
                    </form>
                </section>

                <section class="grid gap-3">
                    <article
                        v-for="project in projects"
                        :key="project.id"
                        class="rounded-lg border bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900"
                    >
                        <div
                            class="grid gap-4 lg:grid-cols-[1fr_auto] lg:items-center"
                        >
                            <div>
                                <h2 class="text-xl font-bold">
                                    {{ project.name }}
                                </h2>
                                <div
                                    class="mt-2 flex flex-wrap gap-4 text-sm text-slate-600 dark:text-slate-300"
                                >
                                    <span
                                        >{{ project.people_count }} people</span
                                    >
                                    <span>{{ project.event_date }}</span>
                                    <span
                                        >{{
                                            project.totals.meal_count
                                        }}
                                        meals</span
                                    >
                                    <span
                                        >{{
                                            money(
                                                project.totals.total_cost_minor,
                                            )
                                        }}
                                        total</span
                                    >
                                    <span
                                        >{{
                                            money(
                                                project.totals
                                                    .cost_per_head_minor,
                                            )
                                        }}
                                        per head</span
                                    >
                                </div>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <Button
                                    as-child
                                    class="bg-scout-purple text-white hover:bg-scout-navy"
                                >
                                    <Link
                                        :href="mealPlannerShow(project.id).url"
                                    >
                                        <Utensils class="mr-2 size-4" />
                                        Meal planner
                                    </Link>
                                </Button>
                                <Button
                                    variant="outline"
                                    @click="startEditing(project)"
                                >
                                    <Pencil class="mr-2 size-4" />
                                    Edit
                                </Button>
                                <Button
                                    variant="outline"
                                    @click="
                                        router.post(
                                            ProjectController.duplicate(
                                                project.id,
                                            ).url,
                                        )
                                    "
                                >
                                    <Copy class="mr-2 size-4" />
                                    Duplicate
                                </Button>
                                <Button
                                    variant="destructive"
                                    @click="
                                        router.delete(
                                            ProjectController.destroy(
                                                project.id,
                                            ).url,
                                        )
                                    "
                                >
                                    <Trash2 class="mr-2 size-4" />
                                    Delete
                                </Button>
                            </div>
                        </div>

                        <form
                            v-if="editingProjectId === project.id"
                            class="mt-4 grid gap-3 border-t pt-4 md:grid-cols-[1fr_9rem_10rem_auto_auto] dark:border-slate-800"
                            @submit.prevent="updateProject"
                        >
                            <Input v-model="editProjectForm.name" />
                            <Input
                                v-model="editProjectForm.people_count"
                                type="number"
                                min="1"
                            />
                            <Input
                                v-model="editProjectForm.event_date"
                                type="date"
                            />
                            <Button :disabled="editProjectForm.processing"
                                >Save</Button
                            >
                            <Button
                                type="button"
                                variant="outline"
                                @click="editingProjectId = null"
                                >Cancel</Button
                            >
                        </form>
                    </article>

                    <div
                        v-if="projects.length === 0"
                        class="rounded-lg border bg-white p-10 text-center text-slate-600 shadow-sm dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300"
                    >
                        No projects yet.
                    </div>
                </section>
            </div>
        </main>
    </AppLayout>
</template>
