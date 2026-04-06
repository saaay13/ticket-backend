<?php

namespace App\Http\Controllers\Api;

use App\Models\Department;
use App\Http\Controllers\Controller;
use App\Http\Resources\DepartmentResource;
use App\Http\Requests\StoreDepartmentRequest;

class DepartmentController extends Controller
{
    public function index()
    {
        $sort = request()->query('sort', 'name');
        $order = request()->query('order', 'asc');

        $departments = Department::orderBy($sort, $order)->get();
        return DepartmentResource::collection($departments);
    }

    public function store(StoreDepartmentRequest $request)
    {
        $department = Department::create($request->validated());
        return (new DepartmentResource($department))
            ->additional(['message' => 'Department created successfully']);
    }

    public function show(Department $department)
    {
        return new DepartmentResource($department);
    }

    public function update(StoreDepartmentRequest $request, Department $department)
    {
        $department->update($request->validated());
        return (new DepartmentResource($department))
            ->additional(['message' => 'Department updated successfully']);
    }

    public function destroy(Department $department)
    {
        if (! $department->active) {
            return response()->json(['message' => 'The department is already inactive'], 400);
        }

        $department->update(['active' => false]);
        return response()->json(['message' => 'Department deactivated successfully'], 200);
    }
}

