<?php

namespace App\Http\Controllers\Admin;

use App\Models\Language;
use App\Helpers\HasUploader;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Pagination\LengthAwarePaginator;

class AcnooLanguageController extends Controller
{
    use HasUploader;

    public function index(Request $request)
    {
        $languages = Language::orderBy('order', 'asc')
            ->when($request->search, function ($q) use ($request) {
                $q->where(function ($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->search . '%')
                        ->orWhere('code', 'like', '%' . $request->search . '%');
                });
            })
            ->latest()
            ->paginate($request->per_page ?? 20)
            ->appends($request->query());

        if ($request->ajax()) {
            return response()->json([
                'data' => view('admin.languages.datas', compact('languages'))->render()
            ]);
        }

        return view('admin.languages.index', compact('languages'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'order' => 'required|integer|min:1',
            'name'  => 'required|string|max:100',
            'code'  => 'required|string|max:20|unique:languages,code',
            'icon' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        Language::create($request->except('icon') + [
            'icon' => $request->icon ? $this->upload($request, 'icon') : NULL
        ]);

        Cache::forget('languages');

        $source = base_path('lang/en.json');
        $destination = base_path("lang/{$request->code}.json");

        if (!File::exists($destination)) {
            File::copy($source, $destination);
        }

        return response()->json([
            'message' => __('Laguage created successfully'),
            'redirect' => route('admin.languages.index')
        ]);
    }

    public function show(Request $request, $code)
    {
        $lang = Language::where('code', $code)->firstOrFail();
        $path = base_path("lang/{$code}.json");

        if (!File::exists($path)) {
            abort(404, 'Language file not found');
        }

        $translations = json_decode(File::get($path), true) ?? [];

        // Search
        if ($request->filled('search')) {
            $search = strtolower($request->search);
            $translations = array_filter($translations, function ($value, $key) use ($search) {
                return str_contains(strtolower($key), $search)
                    || str_contains(strtolower($value), $search);
            }, ARRAY_FILTER_USE_BOTH);
        }

        // Convert to collection
        $collection = collect($translations)->map(function ($value, $key) {
            return [
                'key'   => $key,
                'value' => $value,
            ];
        })->values();

        // Pagination
        $perPage = 20;
        $page = $request->get('page', 1);

        $rows = new LengthAwarePaginator(
            $collection->forPage($page, $perPage),
            $collection->count(),
            $perPage,
            $page,
            ['path' => url()->current(), 'query' => $request->query()]
        );

        if ($request->ajax()) {
            return response()->json([
                'data' => view('admin.languages.translates.datas', compact('rows', 'lang'))->render()
            ]);
        }

        return view('admin.languages.translates.translate', compact('rows', 'lang'));
    }

    public function update(Request $request, Language $language)
    {
        $request->validate([
            'name'  => 'required|string|max:100',
            'code'  => 'required|string|max:20|unique:languages,code,' . $language->id,
            'order' => 'required|integer|min:1',
            'icon'  => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        if ($language->code !== $request->code) {
            $oldPath = base_path("lang/{$language->code}.json");
            $newPath = base_path("lang/{$request->code}.json");

            if (File::exists($oldPath)) {
                File::move($oldPath, $newPath);
            }
        }

        $language->update($request->except('icon') + [
            'icon' => $request->icon ? $this->upload($request, 'icon', $language->icon) : $language->icon,
        ]);

        Cache::forget('languages');

        return response()->json([
            'message' => __('Language updated successfully'),
            'redirect' => route('admin.languages.index')
        ]);
    }

    public function destroy(Language $language)
    {
        if ($language->is_default) {
            return response()->json([
                'message' => __('Default language cannot be deleted')
            ], 403);
        }

        if (file_exists($language->icon)) {
            Storage::delete($language->icon);
        }

        $langPath = base_path("lang/{$language->code}.json");

        if (File::exists($langPath)) {
            File::delete($langPath);
        }

        $language->delete();

        Cache::forget('languages');

        return response()->json([
            'message'   => __('Language deleted successfully'),
            'redirect'  => route('admin.languages.index')
        ]);
    }

    public function status(Request $request, $id)
    {
        $language = Language::findOrFail($id);

        if ($language->is_default) {
            return response()->json([
                'message' => __('Default language status cannot be changed')
            ], 403);
        }

        $language->update(['status' => $request->status]);

        // cache forget
        Cache::forget('languages');

        return response()->json(['message' => __('Language')]);
    }

    public function isDefault(string $id)
    {
        DB::transaction(function () use ($id) {
            Language::query()->update([
                'is_default' => 0,
            ]);

            Language::where('id', $id)->update([
                'is_default' => 1,
                'order' => 0,
                'created_at' => now(),
            ]);
        });

        return response()->json([
            'message' => __('Default language updated successfully')
        ]);
    }

    public function deleteAll(Request $request)
    {
        $languages = Language::whereIn('id', $request->ids)->where('is_default', 0)->get();

        foreach ($languages as $language) {
            if (file_exists($language->icon)) {
                Storage::delete($language->icon);
            }

            $langPath = base_path("lang/{$language->code}.json");
            if (File::exists($langPath)) {
                File::delete($langPath);
            }
        }

        $languages->each->delete();

        Cache::forget('languages');

        return response()->json([
            'message' => __('Selected Language deleted successfully'),
            'redirect' => route('admin.languages.index')
        ]);
    }
}
