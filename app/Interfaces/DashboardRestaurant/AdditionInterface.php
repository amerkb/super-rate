<?php

namespace App\Interfaces\DashboardRestaurant;

use App\Models\Addition;
use App\Models\Service;
use Illuminate\Http\Request;

interface AdditionInterface
{
    public function getMeals();

    public function tableAddition(Request $request);

    public function avgAddition();

    public function chartAddition();

    public function showMeal(Addition $meal);

    public function storeMeal(array $dataMeal);

    public function changeStatusAdditional();

    public function updateMeal(array $dataAddition, Service $service);

    public function deleteMeal(Service $service);
}
