<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\DepartmentResource;
use App\Models\Department;
use App\Support\ReportScope;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DepartmentController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Department::class);

        return DepartmentResource::collection(
            ReportScope::scopedDepartmentQuery()
                ->with(['viceMayor:id,name', 'foremanUser:id,name'])
                ->orderBy('name')
                ->paginate(25),
        );
    }

    public function show(Department $department): DepartmentResource
    {
        $this->authorize('view', $department);

        return new DepartmentResource($department->load(['viceMayor:id,name', 'foremanUser:id,name']));
    }
}
