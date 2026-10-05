<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        return view('admin.categories', ['categories' => Category::withCount('products')->orderBy('sort')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        Category::create($this->validated($request));

        return back()->with('status', 'Category added.');
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $category->update($this->validated($request, $category));

        return back()->with('status', 'Category saved.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        if ($category->products()->exists()) {
            return back()->with('error', 'Move or delete the products in this category first.');
        }
        $category->delete();

        return back()->with('status', 'Category deleted.');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, ?Category $category = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'type' => ['required', Rule::in(['product', 'subscription'])],
            'description' => ['nullable', 'string', 'max:500'],
            'sort' => ['nullable', 'integer', 'min:0'],
        ]);
        $data['sort'] ??= 0;
        $data['slug'] = Str::slug($data['name']);
        validator($data, ['slug' => [Rule::unique('categories', 'slug')->ignore($category?->id)]], ['slug.unique' => 'A category with that name already exists.'])->validate();

        return $data;
    }
}
