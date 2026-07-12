<?php

namespace App\Policies;

use App\Models\FoodItem;
use App\Models\User;

class FoodItemPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, FoodItem $foodItem): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, FoodItem $foodItem): bool
    {
        return $foodItem->created_by_user_id === $user->id || $user->can('access admin');
    }

    public function delete(User $user, FoodItem $foodItem): bool
    {
        return $this->update($user, $foodItem) && ! $foodItem->mealFoodItems()->exists();
    }

    public function addPrice(User $user, FoodItem $foodItem): bool
    {
        return true;
    }
}
