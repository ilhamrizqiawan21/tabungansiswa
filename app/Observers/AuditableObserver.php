<?php

namespace App\Observers;

use App\Models\AuditLog;
use App\Models\Transaksi;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AuditableObserver
{
    public function created(Model $model): void
    {
        $this->write($model, 'CREATE', null, $model->getAttributes());
    }

    public function updated(Model $model): void
    {
        $this->write($model, 'UPDATE', $model->getOriginal(), $model->getChanges());
    }

    public function deleted(Model $model): void
    {
        $this->write($model, 'DELETE', $model->getOriginal(), null);
    }

    /**
     * @param  array<string, mixed>|null  $old
     * @param  array<string, mixed>|null  $new
     */
    private function write(Model $model, string $action, ?array $old, ?array $new): void
    {
        $admin = Auth::guard('admin')->user();

        AuditLog::create([
            'admin_id' => $admin?->id,
            'table_name' => $model->getTable(),
            'record_id' => $model->getKey(),
            'action' => $action,
            'old_values' => $old,
            'new_values' => $new,
            'description' => $this->describe($model, $action),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
        ]);
    }

    private function describe(Model $model, string $action): string
    {
        if ($model instanceof Transaksi && $action === 'CREATE' && $model->reversal_of_id) {
            return sprintf('Koreksi (pembalikan) transaksi TS-%06d', $model->reversal_of_id);
        }

        return "{$action} pada {$model->getTable()}";
    }
}
