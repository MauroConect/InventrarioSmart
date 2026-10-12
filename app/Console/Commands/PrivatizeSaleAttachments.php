<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Models\VentaAdjunto;
use App\Support\Tenancy\TenantContext;
use App\Support\Tenancy\TenantRuntime;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PrivatizeSaleAttachments extends Command
{
    protected $signature = 'tenant:privatize-sale-attachments {--apply : Copy, reconcile and remove public originals after a verified backup}';
    protected $description = 'Preflight and migrate legacy sale attachments into authorized private tenant storage';

    public function handle(TenantContext $context, TenantRuntime $runtime): int
    {
        $rows = DB::table('venta_adjuntos')->where('ruta', 'not like', 'tenants/%')->get();
        foreach ($rows as $row) {
            if (! preg_match('/^ventas_adjuntos\/[a-zA-Z0-9._-]+$/', $row->ruta) || ! Storage::disk('public')->exists($row->ruta) || ! Tenant::whereKey($row->tenant_id)->exists()) {
                $this->error('Preflight failed: invalid owner, missing file or unsupported legacy path. No files changed.');
                return self::FAILURE;
            }
        }
        if (! $this->option('apply')) { $this->info('Preflight OK: '.$rows->count().' legacy attachments. Use --apply after a private backup.'); return self::SUCCESS; }
        foreach ($rows as $row) {
            $tenant = Tenant::findOrFail($row->tenant_id);
            $context->run($tenant, function () use ($row, $runtime) {
                $public = Storage::disk('public');
                $private = Storage::disk('private');
                $target = $runtime->privatePath('sales/'.$row->venta_id.'/legacy-'.$row->id.'-'.basename($row->ruta));
                $stream = $public->readStream($row->ruta);
                try { $private->put($target, $stream); } finally { if (is_resource($stream)) { fclose($stream); } }
                if (hash_file('sha256', $public->path($row->ruta)) !== hash_file('sha256', $private->path($target))) { throw new \RuntimeException('Attachment reconciliation failed; original preserved.'); }
                DB::transaction(function () use ($row, $target) {
                    $locked = VentaAdjunto::whereKey($row->id)->lockForUpdate()->firstOrFail();
                    if ($locked->ruta !== $row->ruta) { throw new \RuntimeException('Attachment changed during migration.'); }
                    $locked->update(['ruta' => $target]);
                });
                $public->delete($row->ruta);
            });
        }
        $this->info('Migrated and reconciled '.$rows->count().' attachments.');
        return self::SUCCESS;
    }
}
