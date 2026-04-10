<?php

namespace App\Http\Controllers;

use App\Domain\Categories\Actions\CreateCategoryAction;
use App\Domain\Categories\DTOs\CreateCategoryData;
use App\Domain\Categories\Models\Category;
use App\Domain\Categories\Resources\CategoryResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CategoryController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        // Optionally switch locale for this request
        if ($request->query('locale')) {
            app()->setLocale($request->query('locale'));
        }

        $categories = Category::where(function ($query) use ($request) {
            $query->whereNull('user_id')
                ->orWhere('user_id', $request->user()->id);
        })->get();

        return CategoryResource::collection($categories);
    }

    public function store(Request $request, CreateCategoryAction $action): JsonResponse
    {
        $data = CreateCategoryData::validateAndCreate([
            'slug' => $request->input('slug'),
            'user_id' => $request->user()->id,
        ]);

        $category = $action->execute($data);

        return (new CategoryResource($category))->response()->setStatusCode(201);
    }

    public function destroy(Request $request, Category $category): JsonResponse
    {
        // System category (user_id is null) — forbidden
        if (is_null($category->user_id)) {
            abort(403, 'Cannot delete a system category.');
        }

        // Belongs to another user — forbidden
        if ($category->user_id !== $request->user()->id) {
            abort(403, 'This category does not belong to you.');
        }

        $category->delete();

        return response()->json(null, 204);
    }
}
