<?php

namespace App\Http\Controllers;

use App\Domain\Installments\Actions\CancelInstallmentPlanAction;
use App\Domain\Installments\Actions\CreateInstallmentPlanAction;
use App\Domain\Installments\DTOs\CreateInstallmentPlanData;
use App\Domain\Installments\Models\InstallmentPlan;
use App\Domain\Installments\Resources\InstallmentPlanResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class InstallmentPlanController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $plans = InstallmentPlan::query()
            ->where('user_id', $request->user()->id)
            ->get();

        return InstallmentPlanResource::collection($plans);
    }

    public function store(Request $request, CreateInstallmentPlanAction $action): JsonResponse
    {
        $data = CreateInstallmentPlanData::from($request);

        try {
            $plan = $action->execute($data, $request->user());
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return (new InstallmentPlanResource($plan))->response()->setStatusCode(201);
    }

    public function show(Request $request, InstallmentPlan $installmentPlan): InstallmentPlanResource
    {
        abort_if($installmentPlan->user_id !== $request->user()->id, 403);

        return new InstallmentPlanResource($installmentPlan);
    }

    public function destroy(Request $request, InstallmentPlan $installmentPlan, CancelInstallmentPlanAction $action): InstallmentPlanResource
    {
        $plan = $action->execute($installmentPlan, $request->user());

        return new InstallmentPlanResource($plan);
    }
}
