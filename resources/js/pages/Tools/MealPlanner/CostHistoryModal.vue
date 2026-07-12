<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Modal } from '@inertiaui/modal-vue';

interface CostSnapshot {
    id: number;
    total_cost_minor: number;
    cost_per_head_minor: number;
    total_calories_per_serving: number;
    meal_count: number;
    snapshot_reason: string;
    created_at: string | null;
}

defineProps<{
    project: {
        id: number;
        name: string;
    };
    snapshots: CostSnapshot[];
}>();

function money(minor: number | null | undefined): string {
    return new Intl.NumberFormat('en-GB', {
        style: 'currency',
        currency: 'GBP',
    }).format((minor ?? 0) / 100);
}
</script>

<template>
    <Modal>
        <template #default="{ close }">
            <div
                class="max-h-[85vh] overflow-y-auto p-5 text-slate-950 dark:text-slate-50"
            >
                <div
                    class="flex items-start justify-between gap-4 border-b pb-4 dark:border-slate-800"
                >
                    <div>
                        <p class="text-xs font-bold text-slate-500 uppercase">
                            Cost history
                        </p>
                        <h1 class="text-xl font-bold">{{ project.name }}</h1>
                    </div>
                    <Button type="button" variant="outline" @click="close"
                        >Close</Button
                    >
                </div>

                <div
                    class="mt-4 overflow-x-auto rounded-md border dark:border-slate-800"
                >
                    <table class="w-full min-w-[640px] text-left text-sm">
                        <thead
                            class="bg-slate-100 text-xs text-slate-600 uppercase dark:bg-slate-800 dark:text-slate-300"
                        >
                            <tr>
                                <th class="px-4 py-2">When</th>
                                <th class="px-4 py-2">Reason</th>
                                <th class="px-4 py-2">Meals</th>
                                <th class="px-4 py-2">Calories</th>
                                <th class="px-4 py-2">Per head</th>
                                <th class="px-4 py-2">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="snapshot in snapshots"
                                :key="snapshot.id"
                                class="border-t dark:border-slate-800"
                            >
                                <td class="px-4 py-3 text-xs text-slate-500">
                                    {{ snapshot.created_at }}
                                </td>
                                <td class="px-4 py-3">
                                    {{ snapshot.snapshot_reason }}
                                </td>
                                <td class="px-4 py-3">
                                    {{ snapshot.meal_count }}
                                </td>
                                <td class="px-4 py-3">
                                    {{ snapshot.total_calories_per_serving }}
                                </td>
                                <td class="px-4 py-3">
                                    {{ money(snapshot.cost_per_head_minor) }}
                                </td>
                                <td class="px-4 py-3 font-semibold">
                                    {{ money(snapshot.total_cost_minor) }}
                                </td>
                            </tr>
                            <tr v-if="snapshots.length === 0">
                                <td
                                    colspan="6"
                                    class="px-4 py-8 text-center text-sm text-slate-500"
                                >
                                    No cost history recorded yet.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </template>
    </Modal>
</template>
