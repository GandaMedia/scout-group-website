<script setup lang="ts">
import CatalogOptionController from '@/actions/App/Http/Controllers/MealPlanner/CatalogOptionController';
import FoodItemController from '@/actions/App/Http/Controllers/MealPlanner/FoodItemController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { poundsInputToMinorUnits } from '@/lib/utils';
import { Modal, useModal } from '@inertiaui/modal-vue';
import { Plus, Search } from 'lucide-vue-next';
import { reactive, ref, watch } from 'vue';

interface CatalogOption {
    id: number;
    name: string;
}

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

const props = defineProps<{
    project: {
        id: number;
        name: string;
    };
    meal: {
        id: number;
        name: string;
    };
    catalogOptions: {
        brands: CatalogOption[];
        stores: CatalogOption[];
    };
}>();

const modal = useModal();
const errors = ref<Record<string, string>>({});
const isSaving = ref(false);

const brandResults = ref<CatalogOption[]>(props.catalogOptions.brands);
const storeResults = ref<CatalogOption[]>(props.catalogOptions.stores);
const brandQuery = ref('');
const storeQuery = ref('');
const isBrandPickerOpen = ref(false);
const isStorePickerOpen = ref(false);
let brandSearchTimeout: ReturnType<typeof setTimeout> | null = null;
let storeSearchTimeout: ReturnType<typeof setTimeout> | null = null;

function defaultFoodForm(): {
    name: string;
    brand_id: string;
    store_id: string;
    servings_per_pack: string;
    calories_per_pack: string;
    price_per_pack: string;
    priced_at: string;
} {
    return {
        name: '',
        brand_id: '',
        store_id: '',
        servings_per_pack: '1',
        calories_per_pack: '0',
        price_per_pack: '',
        priced_at: new Date().toISOString().slice(0, 10),
    };
}

const form = reactive(defaultFoodForm());

function resetFoodForm(): void {
    if (brandSearchTimeout !== null) {
        clearTimeout(brandSearchTimeout);
        brandSearchTimeout = null;
    }

    if (storeSearchTimeout !== null) {
        clearTimeout(storeSearchTimeout);
        storeSearchTimeout = null;
    }

    Object.assign(form, defaultFoodForm());
    errors.value = {};
    isSaving.value = false;
    brandResults.value = props.catalogOptions.brands;
    storeResults.value = props.catalogOptions.stores;
    brandQuery.value = '';
    storeQuery.value = '';
    isBrandPickerOpen.value = false;
    isStorePickerOpen.value = false;
}

function closeAndReset(close: () => void): void {
    resetFoodForm();
    close();
}

function csrfToken(): string {
    return (
        document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')
            ?.content ?? ''
    );
}

async function jsonRequest<T>(
    url: string,
    payload: Record<string, unknown>,
): Promise<T> {
    const response = await fetch(url, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
        },
        body: JSON.stringify(payload),
    });

    const data = await response.json();

    if (!response.ok) {
        errors.value = Object.fromEntries(
            Object.entries(data.errors ?? {}).map(([key, messages]) => [
                key,
                Array.isArray(messages)
                    ? String(messages[0])
                    : String(messages),
            ]),
        );

        throw new Error('Validation failed');
    }

    errors.value = {};

    return data;
}

async function searchCatalogOptions(
    type: 'brand' | 'store',
    query: string,
): Promise<void> {
    const response = await fetch(
        CatalogOptionController.index.url({ query: { type, q: query } }),
        {
            headers: { Accept: 'application/json' },
        },
    );
    const payload = await response.json();

    if (type === 'brand') {
        brandResults.value = payload.data;
    } else {
        storeResults.value = payload.data;
    }
}

function selectOption(type: 'brand' | 'store', option: CatalogOption): void {
    if (type === 'brand') {
        form.brand_id = String(option.id);
        brandQuery.value = option.name;
        isBrandPickerOpen.value = false;
    } else {
        form.store_id = String(option.id);
        storeQuery.value = option.name;
        isStorePickerOpen.value = false;
    }
}

async function addCatalogOption(type: 'brand' | 'store'): Promise<void> {
    const name = (
        type === 'brand' ? brandQuery.value : storeQuery.value
    ).trim();

    if (name === '') {
        return;
    }

    const payload = await jsonRequest<{ data: CatalogOption }>(
        CatalogOptionController.store.url(),
        {
            type,
            name,
        },
    );

    if (type === 'brand') {
        brandResults.value = [
            payload.data,
            ...brandResults.value.filter(
                (option) => option.id !== payload.data.id,
            ),
        ];
        selectOption('brand', payload.data);
    } else {
        storeResults.value = [
            payload.data,
            ...storeResults.value.filter(
                (option) => option.id !== payload.data.id,
            ),
        ];
        selectOption('store', payload.data);
    }
}

async function saveFood(): Promise<void> {
    const pricePerPack = poundsInputToMinorUnits(form.price_per_pack);

    if (pricePerPack === null) {
        errors.value = {
            ...errors.value,
            price_per_pack: 'Enter a price like 2.30.',
        };

        return;
    }

    isSaving.value = true;

    try {
        const payload = await jsonRequest<{ data: FoodSearchItem }>(
            FoodItemController.store.url(),
            {
                ...form,
                price_per_pack: pricePerPack,
            },
        );

        modal?.getParentModal()?.emit('food-created', payload.data);
        resetFoodForm();
        modal?.close();
    } finally {
        isSaving.value = false;
    }
}

watch(brandQuery, () => {
    if (brandSearchTimeout !== null) {
        clearTimeout(brandSearchTimeout);
    }

    if (brandQuery.value.trim() === '') {
        brandResults.value = props.catalogOptions.brands;
        brandSearchTimeout = null;

        return;
    }

    brandSearchTimeout = setTimeout(() => {
        void searchCatalogOptions('brand', brandQuery.value);
    }, 200);
});

watch(storeQuery, () => {
    if (storeSearchTimeout !== null) {
        clearTimeout(storeSearchTimeout);
    }

    if (storeQuery.value.trim() === '') {
        storeResults.value = props.catalogOptions.stores;
        storeSearchTimeout = null;

        return;
    }

    storeSearchTimeout = setTimeout(() => {
        void searchCatalogOptions('store', storeQuery.value);
    }, 200);
});
</script>

<template>
    <Modal @close="resetFoodForm">
        <template #default="{ close }">
            <div
                class="max-h-[85vh] overflow-y-auto p-5 text-slate-950 dark:text-slate-50"
            >
                <div class="border-b pb-4 dark:border-slate-800">
                    <p class="text-xs font-bold text-slate-500 uppercase">
                        Catalog food
                    </p>
                    <h1 class="text-xl font-bold">New food item</h1>
                    <p class="text-sm text-slate-500">
                        {{ meal.name }} - {{ project.name }}
                    </p>
                </div>

                <form class="mt-4 space-y-5" @submit.prevent="saveFood">
                    <section class="space-y-3">
                        <h2 class="text-sm font-bold text-slate-500 uppercase">
                            Food
                        </h2>
                        <div class="space-y-1">
                            <Label for="food-name">Name</Label>
                            <Input
                                id="food-name"
                                v-model="form.name"
                                autofocus
                            />
                            <InputError :message="errors.name" />
                        </div>

                        <div class="grid gap-3 md:grid-cols-2">
                            <div class="space-y-1">
                                <Label for="brand-search">Brand</Label>
                                <div class="relative">
                                    <Search
                                        class="pointer-events-none absolute top-2.5 left-3 size-4 text-slate-400"
                                    />
                                    <Input
                                        id="brand-search"
                                        v-model="brandQuery"
                                        class="pl-9"
                                        autocomplete="off"
                                        @focus="isBrandPickerOpen = true"
                                        @blur="isBrandPickerOpen = false"
                                    />
                                    <div
                                        v-if="isBrandPickerOpen"
                                        class="absolute z-20 mt-1 max-h-48 w-full overflow-auto rounded-md border bg-white shadow-lg dark:border-slate-800 dark:bg-slate-900"
                                    >
                                        <button
                                            v-for="brand in brandResults"
                                            :key="brand.id"
                                            class="block w-full px-3 py-2 text-left text-sm hover:bg-slate-50 dark:hover:bg-slate-800"
                                            type="button"
                                            @mousedown.prevent="
                                                selectOption('brand', brand)
                                            "
                                        >
                                            {{ brand.name }}
                                        </button>
                                        <button
                                            class="flex w-full items-center gap-2 border-t px-3 py-2 text-left text-sm font-medium dark:border-slate-800"
                                            type="button"
                                            @mousedown.prevent="
                                                addCatalogOption('brand')
                                            "
                                        >
                                            <Plus class="size-4" />
                                            Add "{{ brandQuery }}"
                                        </button>
                                    </div>
                                </div>
                                <InputError :message="errors.brand_id" />
                            </div>

                            <div class="space-y-1">
                                <Label for="store-search">Store</Label>
                                <div class="relative">
                                    <Search
                                        class="pointer-events-none absolute top-2.5 left-3 size-4 text-slate-400"
                                    />
                                    <Input
                                        id="store-search"
                                        v-model="storeQuery"
                                        class="pl-9"
                                        autocomplete="off"
                                        @focus="isStorePickerOpen = true"
                                        @blur="isStorePickerOpen = false"
                                    />
                                    <div
                                        v-if="isStorePickerOpen"
                                        class="absolute z-20 mt-1 max-h-48 w-full overflow-auto rounded-md border bg-white shadow-lg dark:border-slate-800 dark:bg-slate-900"
                                    >
                                        <button
                                            v-for="store in storeResults"
                                            :key="store.id"
                                            class="block w-full px-3 py-2 text-left text-sm hover:bg-slate-50 dark:hover:bg-slate-800"
                                            type="button"
                                            @mousedown.prevent="
                                                selectOption('store', store)
                                            "
                                        >
                                            {{ store.name }}
                                        </button>
                                        <button
                                            class="flex w-full items-center gap-2 border-t px-3 py-2 text-left text-sm font-medium dark:border-slate-800"
                                            type="button"
                                            @mousedown.prevent="
                                                addCatalogOption('store')
                                            "
                                        >
                                            <Plus class="size-4" />
                                            Add "{{ storeQuery }}"
                                        </button>
                                    </div>
                                </div>
                                <InputError :message="errors.store_id" />
                            </div>
                        </div>
                    </section>

                    <section class="space-y-3">
                        <h2 class="text-sm font-bold text-slate-500 uppercase">
                            Pack details
                        </h2>
                        <div class="grid gap-3 md:grid-cols-2">
                            <div class="space-y-1">
                                <Label for="servings-per-pack"
                                    >Servings per pack</Label
                                >
                                <Input
                                    id="servings-per-pack"
                                    v-model="form.servings_per_pack"
                                    type="number"
                                    min="1"
                                />
                                <InputError
                                    :message="errors.servings_per_pack"
                                />
                            </div>
                            <div class="space-y-1">
                                <Label for="calories-per-pack"
                                    >Calories per pack</Label
                                >
                                <Input
                                    id="calories-per-pack"
                                    v-model="form.calories_per_pack"
                                    type="number"
                                    min="0"
                                />
                                <InputError
                                    :message="errors.calories_per_pack"
                                />
                            </div>
                        </div>
                    </section>

                    <section class="space-y-3">
                        <h2 class="text-sm font-bold text-slate-500 uppercase">
                            Opening price
                        </h2>
                        <div class="grid gap-3 md:grid-cols-2">
                            <div class="space-y-1">
                                <Label for="price-per-pack"
                                    >Price per pack</Label
                                >
                                <div class="relative">
                                    <span
                                        class="pointer-events-none absolute top-2 left-3 text-sm text-slate-500"
                                        >£</span
                                    >
                                    <Input
                                        id="price-per-pack"
                                        v-model="form.price_per_pack"
                                        class="pl-7"
                                        type="text"
                                        inputmode="decimal"
                                        placeholder="2.30"
                                    />
                                </div>
                                <InputError :message="errors.price_per_pack" />
                            </div>
                            <div class="space-y-1">
                                <Label for="priced-at">Price date</Label>
                                <Input
                                    id="priced-at"
                                    v-model="form.priced_at"
                                    type="date"
                                />
                                <InputError :message="errors.priced_at" />
                            </div>
                        </div>
                    </section>

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
                            class="bg-scout-purple text-white hover:bg-scout-navy"
                            :disabled="isSaving"
                        >
                            Save food
                        </Button>
                    </div>
                </form>
            </div>
        </template>
    </Modal>
</template>
