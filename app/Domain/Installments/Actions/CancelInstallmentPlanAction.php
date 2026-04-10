<?php

namespace App\Domain\Installments\Actions;

use App\Domain\Installments\Models\InstallmentPlan;
use App\Models\User;

class CancelInstallmentPlanAction
{
    public function execute(InstallmentPlan $plan, User $user): InstallmentPlan
    {
        abort_if($plan->user_id !== $user->id, 403);

        $plan->cancelled_at = now();
        $plan->save();

        return $plan;
    }
}
