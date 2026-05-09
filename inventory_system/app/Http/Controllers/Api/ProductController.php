<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\ProductFilterRequest;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    private int $cacheTtl;

    public function __construct()
    {
        $this->cacheTtl = (int) config('cache.ttl', 300);
    }

    public function index(ProductFilterRequest $request): JsonResponse
    {
        $cacheKey = 'products:' . md5(serialize($request->validated()));

        $paginated = Cache::remember($cacheKey, $this->cacheTtl, function () use ($request) {
            $query = Product::with(['category', 'suppliers'])
                ->active();

            // --- Filtering ---
            if ($request->filled('category_id')) {
                $query->inCategory($request->integer('category_id'));
            }

            if ($request->filled('category_ids')) {
                $query->inCategory($request->input('category_ids'));
            }

            if ($request->filled('supplier_id')) {
                $query->whereHas('suppliers', fn($q) =>
                    $q->where('suppliers.id', $request->integer('supplier_id'))
                );
            }

            if ($request->filled('min_price') || $request->filled('max_price')) {
                $query->priceRange(
                    (float) $request->input('min_price', 0),
                    (float) $request->input('max_price', PHP_INT_MAX)
                );
            }

            if ($request->filled('stock_status')) {
                match ($request->string('stock_status')->toString()) {
                    'low_stock'    => $query->lowStock(),
                    'out_of_stock' => $query->outOfStock(),
                    default        => null,
                };
            }

            if ($request->filled('search')) {
                $query->search($request->string('search'));
            }

            if ($request->boolean('is_active', true) === false) {
                $query->where('is_active', false);
            }

            // --- Sorting ---
            $sortBy  = $request->input('sort_by', 'created_at');
            $sortDir = $request->input('sort_direction', 'desc');
            $query->orderBy($sortBy, $sortDir);

            return $query->paginate($request->integer('per_page', 15));
        });

        return response()->json([
            'data'  => ProductResource::collection($paginated),
            'meta'  => [
                'current_page' => $paginated->currentPage(),
                'per_page'     => $paginated->perPage(),
                'total'        => $paginated->total(),
                'last_page'    => $paginated->lastPage(),
                'from'         => $paginated->firstItem(),
                'to'           => $paginated->lastItem(),
            ],
            'links' => [
                'first' => $paginated->url(1),
                'last'  => $paginated->url($paginated->lastPage()),
                'prev'  => $paginated->previousPageUrl(),
                'next'  => $paginated->nextPageUrl(),
            ],
        ]);
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = DB::transaction(function () use ($request) {
            $data = $request->validated();

            if (empty($data['slug'])) {
                $data['slug'] = Str::slug($data['name']);
            }

            $product = Product::create($data);

            // Sync suppliers with pivot data
            if ($request->filled('supplier_ids')) {
                $product->suppliers()->sync($request->input('supplier_ids'));
            } elseif ($request->filled('supplier_pivot')) {
                $syncData = [];
                foreach ($request->input('supplier_pivot') as $pivot) {
                    $syncData[$pivot['supplier_id']] = [
                        'supply_price'   => $pivot['supply_price'] ?? null,
                        'lead_time_days' => $pivot['lead_time_days'] ?? null,
                        'is_preferred'   => $pivot['is_preferred'] ?? false,
                    ];
                }
                $product->suppliers()->sync($syncData);
            }

            return $product->load(['category', 'suppliers']);
        });

        $this->clearProductCache();

        return response()->json([
            'message' => 'Product created successfully.',
            'data'    => new ProductResource($product),
        ], 201);
    }

    public function show(Product $product): JsonResponse
    {
        $product->load(['category', 'suppliers']);

        return response()->json([
            'data' => new ProductResource($product),
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        DB::transaction(function () use ($request, $product) {
            $product->update($request->validated());

            if ($request->has('supplier_ids')) {
                $product->suppliers()->sync($request->input('supplier_ids', []));
            } elseif ($request->has('supplier_pivot')) {
                $syncData = [];
                foreach ($request->input('supplier_pivot', []) as $pivot) {
                    $syncData[$pivot['supplier_id']] = [
                        'supply_price'   => $pivot['supply_price'] ?? null,
                        'lead_time_days' => $pivot['lead_time_days'] ?? null,
                        'is_preferred'   => $pivot['is_preferred'] ?? false,
                    ];
                }
                $product->suppliers()->sync($syncData);
            }
        });

        $this->clearProductCache();
        $product->load(['category', 'suppliers']);

        return response()->json([
            'message' => 'Product updated successfully.',
            'data'    => new ProductResource($product),
        ]);
    }

    public function destroy(Product $product): JsonResponse
    {
        $product->delete();
        $this->clearProductCache();

        return response()->json(['message' => 'Product deleted successfully.']);
    }

    public function restore(int $id): JsonResponse
    {
        $product = Product::withTrashed()->findOrFail($id);
        $product->restore();
        $this->clearProductCache();

        return response()->json([
            'message' => 'Product restored successfully.',
            'data'    => new ProductResource($product->load(['category', 'suppliers'])),
        ]);
    }

    public function forceDelete(int $id): JsonResponse
    {
        $product = Product::withTrashed()->findOrFail($id);
        $product->forceDelete();
        $this->clearProductCache();

        return response()->json(['message' => 'Product permanently deleted.']);
    }

    public function trashed(): JsonResponse
    {
        $products = Product::onlyTrashed()
            ->with(['category', 'suppliers'])
            ->paginate(15);

        return response()->json([
            'data' => ProductResource::collection($products),
            'meta' => [
                'current_page' => $products->currentPage(),
                'total'        => $products->total(),
                'last_page'    => $products->lastPage(),
            ],
        ]);
    }

    private function clearProductCache(): void
    {
        Cache::flush(); // In production, use tagged cache: Cache::tags('products')->flush()
    }
}