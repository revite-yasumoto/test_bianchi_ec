<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Member\UpdateMemberStatusRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

class MemberStatusController extends Controller
{
    public function update(UpdateMemberStatusRequest $request, User $user): RedirectResponse
    {
        $user->update(['status' => $request->enum('status', UserStatus::class)]);

        return back();
    }
}
