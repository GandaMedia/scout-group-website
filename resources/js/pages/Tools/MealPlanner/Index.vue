<script setup lang="ts">
import CostHistoryModalController from '@/actions/App/Http/Controllers/MealPlanner/CostHistoryModalController';
import FoodPriceController from '@/actions/App/Http/Controllers/MealPlanner/FoodPriceController';
import MealController from '@/actions/App/Http/Controllers/MealPlanner/MealController';
import MealFoodItemController from '@/actions/App/Http/Controllers/MealPlanner/MealFoodItemController';
import ProjectExcelExportController from '@/actions/App/Http/Controllers/MealPlanner/ProjectExcelExportController';
import ProjectPdfExportController from '@/actions/App/Http/Controllers/MealPlanner/ProjectPdfExportController';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { minorUnitsToPoundsInput, poundsInputToMinorUnits } from '@/lib/utils';
import { create as createMealFoodItem } from '@/routes/meal-planner/meal-food-items';
import { projects as projectsIndex } from '@/routes/tools';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ModalLink } from '@inertiaui/modal-vue';
import {
    ArrowLeft,
    Copy,
    FileText,
    History,
    Pencil,
    Plus,
    RefreshCw,
    Table,
    Trash2,
} from 'lucide-vue-next';
import { ref, watch } from 'vue';

interface MealLine {
    id: number;
    food_item_id: number;
    food_price_id: number | null;
    amount_per_serving: number;
    packs_required: number;
    cost_per_serving_minor: number;
    calories_per_serving: number;
    total_cost_minor: number;
    price_per_pack_minor: number;
    priced_at: string | null;
    is_stale: boolean;
    food: {
        id: number;
        name: string;
        brand: string | null;
        store: string | null;
        latest_price_minor: number | null;
    };
}

interface MealPlan {
    id: number;
    name: string;
    meal_type: string;
    day_number: number | null;
    totals: {
        total_cost_minor: number;
        cost_per_serving_minor: number;
        calories_per_serving: number;
    };
    has_stale_prices: boolean;
    lines: MealLine[];
}

interface ProjectPlan {
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
    meals: MealPlan[];
}

const props = defineProps<{
    mealTypes: Record<string, string>;
    project: ProjectPlan;
}>();

const selectedMealId = ref<number | null>(props.project.meals[0]?.id ?? null);
const editingPriceLineId = ref<number | null>(null);

const mealForm = useForm({
    name: '',
    meal_type: Object.keys(props.mealTypes)[0] ?? 'Other',
    day_number: '',
});

const priceForm = useForm({
    price_per_pack: '',
    priced_at: new Date().toISOString().slice(0, 10),
});

watch(
    () => props.project.id,
    () => {
        selectedMealId.value = props.project.meals[0]?.id ?? null;
    },
);

function money(minor: number | null | undefined): string {
    return new Intl.NumberFormat('en-GB', {
        style: 'currency',
        currency: 'GBP',
    }).format((minor ?? 0) / 100);
}

function createMeal(): void {
    mealForm.submit(MealController.store(props.project.id), {
        preserveScroll: true,
        onSuccess: () => mealForm.reset('name', 'day_number'),
    });
}

function startPriceEdit(line: MealLine): void {
    editingPriceLineId.value = line.id;
    priceForm.price_per_pack = minorUnitsToPoundsInput(
        line.price_per_pack_minor,
    );
    priceForm.priced_at = new Date().toISOString().slice(0, 10);
}

function savePrice(line: MealLine): void {
    const pricePerPack = poundsInputToMinorUnits(priceForm.price_per_pack);

    if (pricePerPack === null) {
        priceForm.setError('price_per_pack', 'Enter a price like 2.30.');

        return;
    }

    priceForm
        .transform((data) => ({
            ...data,
            price_per_pack: pricePerPack,
            meal_food_item_id: line.id,
        }))
        .submit(FoodPriceController.store(line.food_item_id), {
            preserveScroll: true,
            onSuccess: () => {
                editingPriceLineId.value = null;
                priceForm.reset('price_per_pack');
            },
        });
}
</script>

<template>
    <AppLayout>
        <Head :title="`${project.name} meal planner`" />

        <main
            class="bg-slate-50 py-6 text-slate-950 dark:bg-slate-950 dark:text-slate-50"
        >
            <div class="mx-auto max-w-7xl space-y-4 px-4">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <Button as-child variant="outline">
                        <Link :href="projectsIndex().url">
                            <ArrowLeft class="mr-2 size-4" />
                            Projects
                        </Link>
                    </Button>
                    <div class="flex items-center gap-2">
                        <Button as-child variant="outline">
                            <a
                                :href="
                                    ProjectPdfExportController.url(project.id)
                                "
                                download
                            >
                                <FileText class="mr-2 size-4" />
                                PDF
                            </a>
                        </Button>
                        <Button as-child variant="outline">
                            <a
                                :href="
                                    ProjectExcelExportController.url(project.id)
                                "
                                download
                            >
                                <Table class="mr-2 size-4" />
                                Excel
                            </a>
                        </Button>
                    </div>
                </div>

                <section
                    class="rounded-lg bg-scout-forest-green p-4 text-white"
                >
                    <div
                        class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between"
                    >
                        <div>
                            <p
                                class="text-sm font-bold tracking-wide uppercase opacity-80"
                            >
                                Meal planner
                            </p>
                            <h1 class="text-2xl font-extrabold">
                                {{ project.name }}
                            </h1>
                            <p class="text-sm opacity-90">
                                {{ project.people_count }} people -
                                {{ project.event_date }}
                            </p>
                        </div>
                        <div
                            class="grid grid-cols-2 gap-3 text-sm md:grid-cols-4"
                        >
                            <div>
                                <span class="block opacity-80">Total</span>
                                <strong>{{
                                    money(project.totals.total_cost_minor)
                                }}</strong>
                            </div>
                            <div>
                                <span class="block opacity-80">Per head</span>
                                <strong>{{
                                    money(project.totals.cost_per_head_minor)
                                }}</strong>
                            </div>
                            <div>
                                <span class="block opacity-80">Calories</span>
                                <strong>{{
                                    project.totals.total_calories_per_serving
                                }}</strong>
                            </div>
                            <div>
                                <span class="block opacity-80">Meals</span>
                                <strong>{{ project.totals.meal_count }}</strong>
                            </div>
                        </div>
                    </div>
                </section>

                <div class="space-y-4">
                    <div class="space-y-4">
                        <section
                            class="rounded-lg border bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900"
                        >
                            <form
                                class="grid gap-3 md:grid-cols-[1fr_12rem_8rem_auto]"
                                @submit.prevent="createMeal"
                            >
                                <div class="space-y-1">
                                    <Label for="meal-name">Meal</Label>
                                    <Input
                                        id="meal-name"
                                        v-model="mealForm.name"
                                    />
                                </div>
                                <div class="space-y-1">
                                    <Label for="meal-type">Type</Label>
                                    <select
                                        id="meal-type"
                                        v-model="mealForm.meal_type"
                                        class="h-9 rounded-md border bg-transparent px-3 text-sm"
                                    >
                                        <option
                                            v-for="(label, value) in mealTypes"
                                            :key="value"
                                            :value="value"
                                        >
                                            {{ label }}
                                        </option>
                                    </select>
                                </div>
                                <div class="space-y-1">
                                    <Label for="meal-day">Day</Label>
                                    <Input
                                        id="meal-day"
                                        v-model="mealForm.day_number"
                                        type="number"
                                        min="1"
                                    />
                                </div>
                                <Button
                                    class="self-end bg-scout-purple text-white hover:bg-scout-navy"
                                    :disabled="mealForm.processing"
                                >
                                    <Plus class="mr-2 size-4" />
                                    Add meal
                                </Button>
                            </form>
                        </section>

                        <section
                            v-for="meal in project.meals"
                            :key="meal.id"
                            class="overflow-hidden rounded-lg border bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900"
                            :class="{
                                'ring-2 ring-scout-purple':
                                    selectedMealId === meal.id,
                            }"
                        >
                            <div
                                class="flex items-stretch justify-between gap-2 bg-scout-navy px-4 py-3 text-white"
                            >
                                <button
                                    class="min-w-0 flex-1 text-left"
                                    @click="selectedMealId = meal.id"
                                >
                                    <span
                                        class="block truncate text-lg font-bold"
                                    >
                                        <template v-if="meal.day_number"
                                            >Day
                                            {{ meal.day_number }} - </template
                                        >{{ meal.name }}
                                    </span>
                                    <span class="text-sm opacity-85"
                                        >{{ meal.totals.calories_per_serving }}
                                        calories -
                                        {{
                                            money(
                                                meal.totals
                                                    .cost_per_serving_minor,
                                            )
                                        }}
                                        per serving</span
                                    >
                                </button>
                                <div class="flex shrink-0 items-center gap-2">
                                    <span
                                        class="rounded-md border border-white/50 px-2 py-1 text-xs font-bold uppercase"
                                        >{{ meal.meal_type }}</span
                                    >
                                    <ModalLink
                                        as="button"
                                        :href="
                                            createMealFoodItem.url({
                                                project: project.id,
                                                meal: meal.id,
                                            })
                                        "
                                        class="inline-flex size-9 items-center justify-center rounded-md border border-white/50 text-white hover:bg-white/10"
                                        max-width="2xl"
                                        panel-classes="rounded-lg bg-white shadow-xl dark:bg-slate-900"
                                        padding-classes="p-0"
                                        prefetch="hover"
                                        title="Add food"
                                    >
                                        <Plus class="size-4" />
                                    </ModalLink>
                                </div>
                            </div>

                            <div
                                v-if="meal.has_stale_prices"
                                class="flex items-center justify-between gap-3 border-b border-amber-200 bg-amber-50 px-4 py-2 text-sm text-amber-950"
                            >
                                <span
                                    >Saved prices differ from current catalog
                                    prices.</span
                                >
                                <Button
                                    size="sm"
                                    variant="outline"
                                    @click="
                                        router.post(
                                            MealController.refreshPrices({
                                                project: project.id,
                                                meal: meal.id,
                                            }).url,
                                        )
                                    "
                                >
                                    <RefreshCw class="mr-2 size-4" />
                                    Update prices
                                </Button>
                            </div>

                            <div class="overflow-x-auto">
                                <table
                                    class="w-full min-w-[760px] text-left text-sm"
                                >
                                    <thead
                                        class="bg-slate-100 text-xs text-slate-600 uppercase dark:bg-slate-800 dark:text-slate-300"
                                    >
                                        <tr>
                                            <th class="px-4 py-2">Item</th>
                                            <th class="px-4 py-2">
                                                Qty per serving
                                            </th>
                                            <th class="px-4 py-2">Packs</th>
                                            <th class="px-4 py-2">Price</th>
                                            <th class="px-4 py-2">Calories</th>
                                            <th class="px-4 py-2">Total</th>
                                            <th class="px-4 py-2"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr
                                            v-for="line in meal.lines"
                                            :key="line.id"
                                            class="group/line border-t dark:border-slate-800"
                                        >
                                            <td class="px-4 py-3">
                                                <span class="font-medium">{{
                                                    line.food.name
                                                }}</span>
                                                <span
                                                    class="block text-xs text-slate-500"
                                                    >{{ line.food.store }} -
                                                    {{ line.food.brand }}</span
                                                >
                                            </td>
                                            <td class="px-4 py-3">
                                                {{ line.amount_per_serving }}
                                            </td>
                                            <td class="px-4 py-3">
                                                {{ line.packs_required }}
                                            </td>
                                            <td class="px-4 py-3">
                                                <div
                                                    class="flex items-center gap-1"
                                                >
                                                    <span
                                                        >{{
                                                            money(
                                                                line.cost_per_serving_minor,
                                                            )
                                                        }}
                                                        serving</span
                                                    >
                                                    <span
                                                        class="text-xs text-slate-500"
                                                        >({{
                                                            money(
                                                                line.price_per_pack_minor,
                                                            )
                                                        }}
                                                        pack)</span
                                                    >
                                                    <Button
                                                        size="icon"
                                                        variant="ghost"
                                                        class="size-7 opacity-100 md:opacity-0 md:group-hover/line:opacity-100"
                                                        @click="
                                                            startPriceEdit(line)
                                                        "
                                                    >
                                                        <Pencil
                                                            class="size-3.5"
                                                        />
                                                    </Button>
                                                </div>
                                                <form
                                                    v-if="
                                                        editingPriceLineId ===
                                                        line.id
                                                    "
                                                    class="mt-2 flex items-center gap-2"
                                                    @submit.prevent="
                                                        savePrice(line)
                                                    "
                                                >
                                                    <div class="relative">
                                                        <span
                                                            class="pointer-events-none absolute top-1.5 left-2.5 text-sm text-slate-500"
                                                            >£</span
                                                        >
                                                        <Input
                                                            v-model="
                                                                priceForm.price_per_pack
                                                            "
                                                            class="h-8 w-28 pl-6"
                                                            type="text"
                                                            inputmode="decimal"
                                                            placeholder="2.30"
                                                            aria-label="Price per pack"
                                                        />
                                                    </div>
                                                    <Input
                                                        v-model="
                                                            priceForm.priced_at
                                                        "
                                                        class="h-8 w-36"
                                                        type="date"
                                                    />
                                                    <Button
                                                        size="sm"
                                                        :disabled="
                                                            priceForm.processing
                                                        "
                                                        >Save</Button
                                                    >
                                                </form>
                                            </td>
                                            <td class="px-4 py-3">
                                                {{ line.calories_per_serving }}
                                            </td>
                                            <td class="px-4 py-3">
                                                {{
                                                    money(line.total_cost_minor)
                                                }}
                                            </td>
                                            <td class="px-4 py-3 text-right">
                                                <Button
                                                    size="icon"
                                                    variant="ghost"
                                                    @click="
                                                        router.delete(
                                                            MealFoodItemController.destroy(
                                                                {
                                                                    project:
                                                                        project.id,
                                                                    meal: meal.id,
                                                                    mealFoodItem:
                                                                        line.id,
                                                                },
                                                            ).url,
                                                        )
                                                    "
                                                >
                                                    <Trash2 class="size-4" />
                                                </Button>
                                            </td>
                                        </tr>
                                        <tr v-if="meal.lines.length === 0">
                                            <td
                                                colspan="7"
                                                class="px-4 py-6 text-center text-sm text-slate-500"
                                            >
                                                No food items added.
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div
                                class="flex flex-wrap gap-2 border-t p-3 dark:border-slate-800"
                            >
                                <Button
                                    size="sm"
                                    variant="outline"
                                    @click="
                                        router.post(
                                            MealController.duplicate({
                                                project: project.id,
                                                meal: meal.id,
                                            }).url,
                                        )
                                    "
                                >
                                    <Copy class="mr-2 size-4" />
                                    Duplicate meal
                                </Button>
                                <Button
                                    size="sm"
                                    variant="destructive"
                                    @click="
                                        router.delete(
                                            MealController.destroy({
                                                project: project.id,
                                                meal: meal.id,
                                            }).url,
                                        )
                                    "
                                >
                                    <Trash2 class="mr-2 size-4" />
                                    Delete meal
                                </Button>
                            </div>
                        </section>
                    </div>

                    <div class="flex justify-end">
                        <ModalLink
                            as="button"
                            :href="CostHistoryModalController.url(project.id)"
                            class="inline-flex items-center gap-2 rounded-md border bg-white px-4 py-2 text-sm font-medium shadow-sm hover:bg-slate-50 dark:border-slate-800 dark:bg-slate-900 dark:hover:bg-slate-800"
                            max-width="2xl"
                            panel-classes="rounded-lg bg-white shadow-xl dark:bg-slate-900"
                            padding-classes="p-0"
                        >
                            <History class="size-4" />
                            Cost history
                        </ModalLink>
                    </div>
                </div>
            </div>
        </main>
    </AppLayout>
</template>
