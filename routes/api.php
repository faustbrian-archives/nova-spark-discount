<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Laravel\Spark\Repositories\StripeCouponRepository;
use Laravel\Spark\Spark;
use Stripe\Coupon as StripeCoupon;

function applyCoupon(Request $request, Model $model)
{
    $request->validate([
        'type'     => 'required|in:amount,percent',
        'value'    => 'required|integer',
        'duration' => 'required|in:once,forever,repeating',
        'months'   => 'required_if:duration,repeating',
    ]);

    $coupon = StripeCoupon::create([
        'currency'           => config('cashier.currency'),
        'amount_off'         => $request->type === 'amount' ? $request->value * 100 : null,
        'percent_off'        => $request->type === 'percent' ? $request->value : null,
        'duration'           => $request->duration,
        'duration_in_months' => $request->months,
        'max_redemptions'    => 1,
    ], config('cashier.secret'));

    $model->applyCoupon($coupon->id);
}

function currentCoupon(Model $model)
{
    $coupon = resolve(StripeCouponRepository::class)->forBillable($model);

    abort_unless($coupon, 204);

    return $coupon->toArray();
}

/*
|--------------------------------------------------------------------------
| Tool API Routes
|--------------------------------------------------------------------------
|
| Here is where you may register API routes for your tool. These routes
| are loaded by the ServiceProvider of your tool. You're free to add
| as many additional routes to this file as your tool may require.
|
*/

Route::get('/users/{userId}', function ($userId) {
    return currentCoupon(Spark::user()->findOrFail($userId));
});

Route::post('/users/{userId}', function (Request $request, $userId) {
    applyCoupon($request, Spark::user()->findOrFail($userId));
});

Route::get('/teams/{teamId}', function ($teamId) {
    return currentCoupon(Spark::team()->findOrFail($teamId));
});

Route::post('/teams/{teamId}', function (Request $request, $teamId) {
    applyCoupon($request, Spark::team()->findOrFail($teamId));
});
