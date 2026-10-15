<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\File;

class TranslationController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'key'   => 'required|string',
            'value' => 'required|string',
            'lang_code'  => 'required|string',
        ]);

        $path = base_path("lang/{$request->lang_code}.json");

        $translations = json_decode(File::get($path), true);

        if (!is_array($translations)) {
            $translations = [];
        }

        if (array_key_exists($request->key, $translations)) {
            return response()->json([
                'status' => false,
                'message' => __('Key already exists')
            ], 422);
        }

        $translations[$request->key] = $request->value;

        File::put(
            $path,
            json_encode(
                $translations,
                JSON_UNESCAPED_UNICODE
                | JSON_PRETTY_PRINT
                | JSON_UNESCAPED_SLASHES
            )
        );

        return response()->json([
            'status' => true,
            'message' => __('Translation added successfully')
        ]);
    }

    public function update(Request $request, string $lang)
    {
        $request->validate([
            'key'   => 'required|string',
            'value' => 'required|string',
        ]);

        $path = base_path("lang/{$lang}.json");

        $translations = json_decode(File::get($path), true) ?? [];

        $translations[$request->key] = $request->value;

        File::put(
            $path,
            json_encode($translations, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );

        return response()->json([
            'status' => true,
            'message' => __('Translation updated successfully')
        ]);
    }

    public function bulkUpdate(Request $request, $lang)
    {
        $request->validate([
            'translations' => 'required|array',
        ]);

        $path = base_path("lang/{$lang}.json");

        if (!File::exists($path)) {
            return response()->json([
                'status' => false,
                'message' => __('Language file not found')
            ], 404);
        }

        $existing = json_decode(File::get($path), true);

        if (!is_array($existing)) {
            $existing = [];
        }

        // Merge & overwrite values
        foreach ($request->translations as $key => $value) {
            $existing[$key] = $value;
        }

        // Write once (very fast)
        File::put(
            $path,
            json_encode(
                $existing,
                JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
            )
        );

        return response()->json(__('Translations updated successfully'));
    }

    public function destroy(Request $request, $lang)
    {
        $request->validate([
            'key' => 'required|string',
        ]);

        $path = base_path("lang/{$lang}.json");

        if (!File::exists($path)) {
            return response()->json([
                'status' => false,
                'message' => __('Language file not found')
            ], 404);
        }

        $translations = json_decode(File::get($path), true);

        if (!is_array($translations)) {
            $translations = [];
        }

        if (!array_key_exists($request->key, $translations)) {
            return response()->json([
                'status' => false,
                'message' => __('Translation key not found')
            ], 404);
        }

        unset($translations[$request->key]);

        File::put(
            $path,
            json_encode(
                $translations,
                JSON_UNESCAPED_UNICODE
                | JSON_PRETTY_PRINT
                | JSON_UNESCAPED_SLASHES
            )
        );

        return response()->json([
            'status' => true,
            'message' => __('Translation deleted successfully')
        ]);
    }
}
