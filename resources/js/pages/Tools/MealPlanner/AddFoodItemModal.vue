<script setup lang="ts">
import FoodItemController from '@/actions/App/Http/Controllers/MealPlanner/FoodItemController';
import MealFoodItemController from '@/actions/App/Http/Controllers/MealPlanner/MealFoodItemController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { create as createCatalogFood } from '@/routes/meal-planner/food-items';
import { useForm } from '@inertiajs/vue3';
import { Modal, ModalLink } from '@inertiaui/modal-vue';
import { Plus, Search } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

interface FoodSearchItem {
    id: number;
    name: string;
    brand: string | null;
    store: string | null;
    servings_per_pack: number;
    calories_per_pack: number;
    latest_price_id: number | null;
    latest_price_minor: number | null;
    latest_priced_at: string | null;
}

interface ProjectSummary {
    id: number;
    name: string;
    people_count: number;
}

interface MealSummary {
    id: number;
    name: string;
    meal_type: string;
    day_number: number | null;
}

const props = defineProps<{
    project: ProjectSummary;
    meal: MealSummary;
    foodSearch: FoodSearchItem[];
}>();

const foodResults = ref<FoodSearchItem[]>(props.foodSearch);
const foodQuery = ref('');
const selectedFood = ref<FoodSearchItem | null>(null);
const isSearching = ref(false);
const isFoodPickerOpen = ref(false);
let searchTimeout: ReturnType<typeof setTimeout> | null = null;

const lineForm = useForm({
    food_item_id: '',
    food_price_id: '',
    amount_per_serving: '1.00',
});

const canAddFood = computed(
    () =>
        selectedFood.value !== null &&
        selectedFood.value.latest_price_id !== null,
);

function money(minor: number | null | undefined): string {
    return new Intl.NumberFormat('en-GB', {
        style: 'currency',
        currency: 'GBP',
    }).format((minor ?? 0) / 100);
}

function selectFood(food: FoodSearchItem): void {
    selectedFood.value = food;
    foodQuery.value = food.name;
    lineForm.food_item_id = String(food.id);
    lineForm.food_price_id = String(food.latest_price_id ?? '');
    isFoodPickerOpen.value = false;
}

function closeFoodPicker(event: FocusEvent): void {
    const nextTarget = event.relatedTarget;

    if (
        nextTarget instanceof Node &&
        event.currentTarget instanceof Node &&
        event.currentTarget.contains(nextTarget)
    ) {
        return;
    }

    isFoodPickerOpen.value = false;
}

async function searchFoods(): Promise<void> {
    isSearching.value = true;

    try {
        const response = await fetch(
            FoodItemController.index.url({ query: { q: foodQuery.value } }),
            {
                headers: { Accept: 'application/json' },
            },
        );

        const payload = await response.json();
        foodResults.value = payload.data;
    } finally {
        isSearching.value = false;
    }
}

function handleFoodCreated(food: FoodSearchItem): void {
    foodResults.value = [
        food,
        ...foodResults.value.filter((result) => result.id !== food.id),
    ];
    selectFood(food);
}

function resetLineForm(): void {
    if (searchTimeout !== null) {
        clearTimeout(searchTimeout);
        searchTimeout = null;
    }

    lineForm.resetAndClearErrors();
    foodResults.value = props.foodSearch;
    foodQuery.value = '';
    selectedFood.value = null;
    isSearching.value = false;
    isFoodPickerOpen.value = false;
}

function closeAndReset(close: () => void): void {
    resetLineForm();
    close();
}

function addLine(close: () => void): void {
    lineForm.submit(
        MealFoodItemController.store({
            project: props.project.id,
            meal: props.meal.id,
        }),
        {
            preserveScroll: true,
            onSuccess: () => {
                resetLineForm();
                close();
            },
        },
    );
}

watch(foodQuery, () => {
    if (searchTimeout !== null) {
        clearTimeout(searchTimeout);
    }

    if (foodQuery.value.trim() === '') {
        foodResults.value = props.foodSearch;
        isSearching.value = false;
        searchTimeout = null;

        return;
    }

    searchTimeout = setTimeout(() => {
        void searchFoods();
    }, 200);
});
</script>

<template>
    <Modal @close="resetLineForm" @food-created="handleFoodCreated">
        <template #default="{ close }">
            <div
                class="max-h-[85vh] overflow-y-auto p-5 text-slate-950 dark:text-slate-50"
            >
                <div
                    class="flex items-start justify-between gap-4 border-b pb-4 dark:border-slate-800"
                >
                    <div>
                        <p class="text-xs font-bold text-slate-500 uppercase">
                            Add food
                        </p>
                        <h1 class="text-xl font-bold">
                            <template v-if="meal.day_number"
                                >Day {{ meal.day_number }} - </template
                            >{{ meal.name }}
                        </h1>
                        <p class="text-sm text-slate-500">
                            {{ project.people_count }} people
                        </p>
                    </div>
                    <ModalLink
                        as="button"
                        :href="
                            createCatalogFood.url({
                                project: project.id,
                                meal: meal.id,
                            })
                        "
                        class="inline-flex items-center gap-2 rounded-md bg-scout-purple px-3 py-2 text-sm font-medium text-white hover:bg-scout-navy"
                        max-width="lg"
                        panel-classes="rounded-lg bg-white shadow-xl dark:bg-slate-900"
                        padding-classes="p-0"
                    >
                        <Plus class="size-4" />
                        New food item
                    </ModalLink>
                </div>

                <div class="mt-4 space-y-4">
                    <div class="space-y-2">
                        <Label for="food-search">Search food catalog</Label>
                        <div class="relative" @focusout="closeFoodPicker">
                            <Search
                                class="pointer-events-none absolute top-2.5 left-3 size-4 text-slate-400"
                            />
                            <Input
                                id="food-search"
                                v-model="foodQuery"
                                class="pl-9"
                                type="search"
                                autocomplete="off"
                                autofocus
                                @focus="isFoodPickerOpen = true"
                                @input="isFoodPickerOpen = true"
                                @keydown.escape="isFoodPickerOpen = false"
                            />
                            <div
                                v-if="isFoodPickerOpen"
                                class="absolute z-30 mt-1 max-h-72 w-full overflow-auto rounded-md border bg-white shadow-lg dark:border-slate-800 dark:bg-slate-900"
                            >
                                <button
                                    v-for="food in foodResults"
                                    :key="food.id"
                                    class="flex w-full items-start justify-between gap-3 px-3 py-2 text-left text-sm hover:bg-slate-50 dark:hover:bg-slate-800"
                                    :class="{
                                        'bg-slate-50 dark:bg-slate-800':
                                            selectedFood?.id === food.id,
                                    }"
                                    type="button"
                                    @mousedown.prevent="selectFood(food)"
                                >
                                    <span>
                                        <span class="block font-medium">{{
                                            food.name
                                        }}</span>
                                        <span
                                            class="block text-xs text-slate-500"
                                            >{{ food.store }} -
                                            {{ food.brand }}</span
                                        >
                                    </span>
                                    <span
                                        class="text-right text-xs text-slate-500"
                                    >
                                        <span class="block">{{
                                            money(food.latest_price_minor)
                                        }}</span>
                                        <span v-if="food.latest_priced_at">{{
                                            food.latest_priced_at
                                        }}</span>
                                    </span>
                                </button>
                                <div
                                    v-if="foodResults.length === 0"
                                    class="px-3 py-6 text-center text-sm text-slate-500"
                                >
                                    {{
                                        isSearching
                                            ? 'Searching...'
                                            : 'No food items found.'
                                    }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div
                        v-if="selectedFood"
                        class="rounded-md border bg-slate-50 p-3 text-sm dark:border-slate-800 dark:bg-slate-800"
                    >
                        <div
                            class="flex flex-wrap items-center justify-between gap-3"
                        >
                            <div>
                                <strong>{{ selectedFood.name }}</strong>
                                <p class="text-slate-500">
                                    {{ selectedFood.store }} -
                                    {{ selectedFood.brand }}
                                </p>
                            </div>
                            <div class="text-right">
                                <p>
                                    {{ money(selectedFood.latest_price_minor) }}
                                    per pack
                                </p>
                                <p class="text-xs text-slate-500">
                                    {{ selectedFood.servings_per_pack }}
                                    servings -
                                    {{ selectedFood.calories_per_pack }}
                                    calories
                                </p>
                            </div>
                        </div>
                        <p
                            v-if="selectedFood.latest_price_id === null"
                            class="mt-2 text-sm text-amber-700 dark:text-amber-300"
                        >
                            This item needs a price before it can be added to a
                            meal.
                        </p>
                    </div>

                    <form class="space-y-3" @submit.prevent="addLine(close)">
                        <div class="space-y-1">
                            <Label for="amount-per-serving"
                                >Amount per serving</Label
                            >
                            <Input
                                id="amount-per-serving"
                                v-model="lineForm.amount_per_serving"
                                type="number"
                                min="0.01"
                                step="0.01"
                            />
                            <InputError
                                :message="lineForm.errors.amount_per_serving"
                            />
                        </div>
                        <InputError :message="lineForm.errors.food_item_id" />
                        <InputError :message="lineForm.errors.food_price_id" />

                        <div
                            class="flex justify-end gap-2 border-t pt-4 dark:border-slate-800"
                        >
                            <Button
                                type="button"
                                variant="outline"
                                @click="closeAndReset(close)"
                                >Cancel</Button
                            >
                            <Button
                                class="bg-scout-green text-white hover:bg-scout-forest-green"
                                :disabled="!canAddFood || lineForm.processing"
                            >
                                Add to meal
                            </Button>
                        </div>
                    </form>
                </div>
            </div>
        </template>
    </Modal>
</template>
