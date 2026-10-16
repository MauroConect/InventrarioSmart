<?php

namespace App\Services\Tenancy;

use App\Models\Venta;
use App\Models\VentaAdjunto;
use App\Services\PersistentUploadStore;
use App\Support\Tenancy\TenantContext;
use App\Support\Tenancy\TenantRuntime;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SaleAttachmentService
{
    public function __construct(private readonly TenantRuntime $runtime, private readonly TenantContext $context) {}

    public function upload(Venta $sale, array $files): array
    {
        if ((int) $sale->tenant_id !== $this->context->id()) { throw new RuntimeException('Venta ajena al comercio.'); }
        $stored = [];
        try {
            return DB::transaction(function () use ($sale, $files, &$stored) {
                $created = [];
                foreach ($files as $file) {
                    $path = app(PersistentUploadStore::class)->store($file, $this->runtime->privatePath('sales/'.$sale->id), 'private');
                    if (! $path) { throw new RuntimeException('No se pudo guardar el adjunto.'); }
                    $stored[] = $path;
                    $created[] = VentaAdjunto::create(['venta_id' => $sale->id, 'ruta' => $path, 'nombre_original' => basename($file->getClientOriginalName()), 'mime' => $file->getMimeType(), 'size' => $file->getSize()]);
                }
                return $created;
            });
        } catch (\Throwable $e) {
            foreach ($stored as $path) { app(PersistentUploadStore::class)->delete($path, 'private'); }
            throw $e;
        }
    }

    public function assertPrivatePath(VentaAdjunto $attachment): void
    {
        $prefix = $this->runtime->privatePath('sales/'.$attachment->venta_id).'/';
        if ((int) $attachment->tenant_id !== $this->context->id() || ! str_starts_with($attachment->ruta, $prefix) || str_contains($attachment->ruta, '..') || str_contains($attachment->ruta, '\\')) {
            throw new RuntimeException('Adjunto legacy o ruta inválida; necesita migración privada.');
        }
    }
}
