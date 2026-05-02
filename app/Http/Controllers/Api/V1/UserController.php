<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UserController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', User::class);
        $user = $request->user();

        $query = User::query()->with('department:id,name');
        if ($user->isAdmin()) {
            // no scope
        } elseif ($user->isViceMayor()) {
            $query
                ->whereIn('department_id', $user->managedDepartmentIds())
                ->where('role', '!=', UserRole::Admin->value);
        } elseif ($user->isManager() && $user->department_id) {
            $query
                ->where('department_id', $user->department_id)
                ->where('role', UserRole::Staff->value);
        } else {
            $query->whereKey($user->id);
        }

        return UserResource::collection($query->latest('id')->paginate(25));
    }

    public function show(Request $request, User $user): UserResource
    {
        $this->authorize('view', $user);

        return new UserResource($user->load('department:id,name'));
    }
}
