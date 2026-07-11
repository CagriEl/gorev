<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UploadController extends Controller
{
    public function taskPhoto(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'photo' => ['required', 'image', 'max:10240'],
            'type' => ['required', 'in:arrival,completion'],
        ]);

        $folder = $validated['type'] === 'arrival'
            ? 'task-arrival-photos'
            : 'task-completion-photos';

        $filename = Str::ulid().'.'.$request->file('photo')->getClientOriginalExtension();
        $path = $request->file('photo')->storeAs($folder, $filename, 'public');

        return response()->json([
            'path' => $path,
            'url' => Storage::disk('public')->url($path),
        ]);
    }
}
