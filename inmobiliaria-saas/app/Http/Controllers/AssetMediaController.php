<?php

namespace App\Http\Controllers;

use App\Enums\EntityStatus;
use App\Http\Requests\AssetMediaStoreRequest;
use App\Models\Asset;
use App\Models\AssetMedia;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\HeaderUtils;

class AssetMediaController extends Controller
{
    public function index(Asset $asset): View
    {
        $this->authorize('view', $asset);

        return view('asset-media.index', [
            'asset' => $this->loadAssetMedia($asset),
        ]);
    }

    public function store(AssetMediaStoreRequest $request, Asset $asset): JsonResponse|RedirectResponse
    {
        $this->authorize('update', $asset);
        $this->authorize('create', AssetMedia::class);

        $uploadedFiles = $request->file('files', []);

        foreach ($uploadedFiles as $uploadedFile) {
            $path = $uploadedFile->store(
                $this->storageDirectory($asset),
                'r2'
            );

            abort_unless($path, 500, 'No fue posible cargar el archivo.');

            $mimeType = $uploadedFile->getMimeType();

            AssetMedia::query()->create([
                'asset_id' => $asset->id,
                'uploaded_by' => $request->user()->id,
                'disk' => 'r2',
                'path' => $path,
                'original_name' => $uploadedFile->getClientOriginalName(),
                'mime_type' => $mimeType,
                'media_type' => str_starts_with((string) $mimeType, 'video/') ? 'video' : 'image',
                'size' => $uploadedFile->getSize(),
                'status' => EntityStatus::Active->value,
            ]);
        }

        activity('assets')
            ->causedBy($request->user())
            ->performedOn($asset)
            ->event('upload-media')
            ->withProperties([
                'files_count' => count($uploadedFiles),
                'files' => collect($uploadedFiles)->map(fn ($file) => $file->getClientOriginalName())->values()->all(),
            ])
            ->log('upload-media');

        return $this->mediaResponse($asset, 'Archivos del activo cargados correctamente.');
    }

    public function preview(Asset $asset, AssetMedia $media)
    {
        $this->authorize('view', $asset);
        $this->guardMediaBelongsToAsset($asset, $media);
        $this->authorize('view', $media);

        $disk = Storage::disk($media->disk);
        $path = $this->readableStoragePath($disk, $media->path);
        $stream = $disk->readStream($path);

        abort_unless($stream, 404);

        $fileName = $media->original_name ?: basename($media->path);
        $fallbackName = str_replace('%', '', Str::ascii($fileName)) ?: 'archivo';
        $disposition = HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_INLINE, $fileName, $fallbackName);
        $headers = [
            'Content-Type' => $media->mime_type ?: 'application/octet-stream',
            'Content-Disposition' => $disposition,
        ];

        if ($media->size) {
            $headers['Content-Length'] = (string) $media->size;
        }

        return response()->stream(function () use ($stream) {
            fpassthru($stream);
            fclose($stream);
        }, 200, $headers);
    }

    public function destroy(Request $request, Asset $asset, AssetMedia $media): JsonResponse|RedirectResponse
    {
        $this->authorize('update', $asset);
        $this->guardMediaBelongsToAsset($asset, $media);
        $this->authorize('delete', $media);

        $media->update([
            'status' => EntityStatus::Deleted->value,
        ]);

        activity('assets')
            ->causedBy($request->user())
            ->performedOn($asset)
            ->event('delete-media')
            ->withProperties([
                'media_id' => $media->id,
                'original_name' => $media->original_name,
            ])
            ->log('delete-media');

        if ($request->expectsJson()) {
            return $this->mediaResponse($asset, 'Archivo del activo eliminado correctamente.');
        }

        return redirect()
            ->route('assets.media.index', $asset)
            ->with('status', 'Archivo del activo eliminado correctamente.');
    }

    protected function mediaResponse(Asset $asset, string $message): JsonResponse
    {
        $loadedAsset = $this->loadAssetMedia($asset->fresh());

        return response()->json([
            'attachments_html' => view('asset-media._list', [
                'asset' => $loadedAsset,
            ])->render(),
            'message' => $message,
        ]);
    }

    protected function loadAssetMedia(Asset $asset): Asset
    {
        return $asset->load([
            'company',
            'media' => fn ($query) => $query
                ->where('status', '!=', EntityStatus::Deleted->value)
                ->with('uploader')
                ->latest(),
        ]);
    }

    protected function guardMediaBelongsToAsset(Asset $asset, AssetMedia $media): void
    {
        abort_unless($media->asset_id === $asset->id, 404);
    }

    protected function storageDirectory(Asset $asset): string
    {
        return collect([
            trim((string) config('filesystems.r2_root_prefix', env('R2_ROOT_PREFIX', 'inmobiliaria-saas')), '/'),
            'companies',
            $asset->company_id,
            'assets',
            $asset->id,
            'media',
        ])->filter(fn ($segment) => $segment !== null && $segment !== '')->implode('/');
    }

    protected function readableStoragePath($disk, string $path): string
    {
        if ($disk->exists($path)) {
            return $path;
        }

        $prefix = trim((string) config('filesystems.r2_root_prefix', env('R2_ROOT_PREFIX', 'inmobiliaria-saas')), '/');

        if ($prefix === '' || str_starts_with($path, $prefix.'/')) {
            return $path;
        }

        $prefixedPath = $prefix.'/'.$path;

        return $disk->exists($prefixedPath) ? $prefixedPath : $path;
    }
}
