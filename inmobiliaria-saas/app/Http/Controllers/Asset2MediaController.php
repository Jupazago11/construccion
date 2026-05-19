<?php

namespace App\Http\Controllers;

use App\Enums\EntityStatus;
use App\Http\Requests\AssetMediaStoreRequest;
use App\Models\Asset2;
use App\Models\Asset2Media;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\HeaderUtils;

class Asset2MediaController extends Controller
{
    // Muestra el repositorio de archivos del activo 2 con sus adjuntos activos cargados.
    public function index(Asset2 $asset2): View
    {
        $this->authorize('view', $asset2);

        return view('asset2-media.index', [
            'asset2' => $this->loadAsset2Media($asset2),
        ]);
    }

    // Carga uno o varios archivos al activo 2 y devuelve el fragmento HTML actualizado para la lista.
    public function store(AssetMediaStoreRequest $request, Asset2 $asset2): JsonResponse|RedirectResponse
    {
        $this->authorize('update', $asset2);
        $this->authorize('create', Asset2Media::class);

        $uploadedFiles = $request->file('files', []);

        try {
            foreach ($uploadedFiles as $uploadedFile) {
                [$path, $disk] = $this->storeUploadedFile($uploadedFile, $asset2);

                abort_unless($path, 500, 'No fue posible cargar el archivo.');

                $mimeType = $uploadedFile->getMimeType();

                Asset2Media::query()->create([
                    'asset2_id' => $asset2->id,
                    'uploaded_by' => $request->user()->id,
                    'disk' => $disk,
                    'path' => $path,
                    'original_name' => $uploadedFile->getClientOriginalName(),
                    'mime_type' => $mimeType,
                    'media_type' => str_starts_with((string) $mimeType, 'video/') ? 'video' : 'image',
                    'size' => $uploadedFile->getSize(),
                    'status' => EntityStatus::Active->value,
                ]);
            }
        } catch (\Throwable $exception) {
            report($exception);

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'No fue posible cargar el archivo. Verifica la configuración de almacenamiento.',
                ], 422);
            }

            return redirect()
                ->route('assets2.media.index', $asset2)
                ->with('status', 'No fue posible cargar el archivo. Verifica la configuración de almacenamiento.');
        }

        activity('assets2')
            ->causedBy($request->user())
            ->performedOn($asset2)
            ->event('upload-media')
            ->withProperties([
                'files_count' => count($uploadedFiles),
                'files' => collect($uploadedFiles)->map(fn ($file) => $file->getClientOriginalName())->values()->all(),
            ])
            ->log('upload-media');

        return $this->mediaResponse($asset2, 'Archivos del activo cargados correctamente.');
    }

    // Sirve una vista previa en streaming validando que el archivo pertenezca al activo solicitado.
    public function preview(Asset2 $asset2, Asset2Media $media)
    {
        $this->authorize('view', $asset2);
        $this->guardMediaBelongsToAsset($asset2, $media);
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

    // Archiva un adjunto del activo 2 y refresca la lista de archivos sin recargar la pantalla.
    public function destroy(Request $request, Asset2 $asset2, Asset2Media $media): JsonResponse|RedirectResponse
    {
        $this->authorize('update', $asset2);
        $this->guardMediaBelongsToAsset($asset2, $media);
        $this->authorize('delete', $media);

        $media->update([
            'status' => EntityStatus::Deleted->value,
        ]);

        activity('assets2')
            ->causedBy($request->user())
            ->performedOn($asset2)
            ->event('delete-media')
            ->withProperties([
                'media_id' => $media->id,
                'original_name' => $media->original_name,
            ])
            ->log('delete-media');

        if ($request->expectsJson()) {
            return $this->mediaResponse($asset2, 'Archivo del activo eliminado correctamente.');
        }

        return redirect()
            ->route('assets2.media.index', $asset2)
            ->with('status', 'Archivo del activo eliminado correctamente.');
    }

    // Construye la respuesta AJAX estándar con el HTML renderizado de adjuntos.
    protected function mediaResponse(Asset2 $asset2, string $message): JsonResponse
    {
        $loadedAsset2 = $this->loadAsset2Media($asset2->fresh());

        return response()->json([
            'attachments_html' => view('asset2-media._list', [
                'asset2' => $loadedAsset2,
            ])->render(),
            'message' => $message,
        ]);
    }

    // Carga las relaciones mínimas necesarias para pintar la galería o listado de medios.
    protected function loadAsset2Media(Asset2 $asset2): Asset2
    {
        return $asset2->load([
            'company',
            'media' => fn ($query) => $query
                ->where('status', '!=', EntityStatus::Deleted->value)
                ->with('uploader')
                ->latest(),
        ]);
    }

    // Impide acceder o eliminar archivos asociados a otro activo 2.
    protected function guardMediaBelongsToAsset(Asset2 $asset2, Asset2Media $media): void
    {
        abort_unless($media->asset2_id === $asset2->id, 404);
    }

    // Define la carpeta de almacenamiento segmentada por empresa y activo.
    protected function storageDirectory(Asset2 $asset2): string
    {
        return collect([
            trim((string) config('filesystems.r2_root_prefix', env('R2_ROOT_PREFIX', 'inmobiliaria-saas')), '/'),
            'companies',
            $asset2->company_id,
            'assets2',
            $asset2->id,
            'media',
        ])->filter(fn ($segment) => $segment !== null && $segment !== '')->implode('/');
    }

    // Intenta guardar en R2 y cae a disco público local si la configuración remota falla.
    protected function storeUploadedFile($uploadedFile, Asset2 $asset2): array
    {
        $directory = $this->storageDirectory($asset2);
        $disk = $this->preferredUploadDisk();

        try {
            return [$uploadedFile->store($directory, $disk), $disk];
        } catch (\Throwable $exception) {
            if ($disk !== 'r2') {
                throw $exception;
            }

            report($exception);

            return [$uploadedFile->store($directory, 'public'), 'public'];
        }
    }

    // Resuelve el disco preferido según la configuración actual del proyecto.
    protected function preferredUploadDisk(): string
    {
        $r2 = config('filesystems.disks.r2', []);

        return filled($r2['bucket'] ?? null) && filled($r2['endpoint'] ?? null)
            ? 'r2'
            : 'public';
    }

    // Normaliza rutas antiguas o sin prefijo para que los archivos sigan siendo legibles.
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
