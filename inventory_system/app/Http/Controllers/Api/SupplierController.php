<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SupplierResource;
use App\Models\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $suppliers = Supplier::query()
            ->when($request->boolean('active'), fn($q) => $q->active())
            ->when($request->filled('search'), fn($q) =>
                $q->where('name', 'LIKE', '%' . $request->search . '%')
                  ->orWhere('email', 'LIKE', '%' . $request->search . '%')
            )
            ->paginate($request->integer('per_page', 15));

        return response()->json([
            'data' => SupplierResource::collection($suppliers),
            'meta' => ['total' => $suppliers->total(), 'current_page' => $suppliers->currentPage()],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'           => ['required', 'string', 'max:150'],
            'email'          => ['required', 'email', 'unique:suppliers,email'],
            'phone'          => ['nullable', 'string', 'max:30'],
            'address'        => ['nullable', 'string'],
            'city'           => ['nullable', 'string', 'max:100'],
            'country'        => ['nullable', 'string', 'max:100'],
            'contact_person' => ['nullable', 'string', 'max:100'],
            'is_active'      => ['nullable', 'boolean'],
        ]);

        $supplier = Supplier::create($data);

        return response()->json([
            'message' => 'Supplier created successfully.',
            'data'    => new SupplierResource($supplier),
        ], 201);
    }

    public function show(Supplier $supplier): JsonResponse
    {
        $supplier->loadCount('products');

        return response()->json(['data' => new SupplierResource($supplier)]);
    }

    public function update(Request $request, Supplier $supplier): JsonResponse
    {
        $data = $request->validate([
            'name'           => ['sometimes', 'string', 'max:150'],
            'email'          => ['sometimes', 'email', 'unique:suppliers,email,' . $supplier->id],
            'phone'          => ['sometimes', 'nullable', 'string', 'max:30'],
            'address'        => ['sometimes', 'nullable', 'string'],
            'city'           => ['sometimes', 'nullable', 'string', 'max:100'],
            'country'        => ['sometimes', 'nullable', 'string', 'max:100'],
            'contact_person' => ['sometimes', 'nullable', 'string', 'max:100'],
            'is_active'      => ['sometimes', 'boolean'],
        ]);

        $supplier->update($data);

        return response()->json([
            'message' => 'Supplier updated successfully.',
            'data'    => new SupplierResource($supplier),
        ]);
    }

    public function destroy(Supplier $supplier): JsonResponse
    {
        $supplier->delete();

        return response()->json(['message' => 'Supplier deleted successfully.']);
    }
}