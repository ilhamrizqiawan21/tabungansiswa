<?php

namespace App\Http\Controllers;

use App\Http\Requests\FilterAuditLogRequest;
use App\Models\Admin;
use App\Models\AuditLog;
use Inertia\Inertia;
use Inertia\Response;

class AuditLogController extends Controller
{
    public function index(FilterAuditLogRequest $request): Response
    {
        $filters = $request->validated();

        $items = AuditLog::with('admin')
            ->when($filters['table'] ?? null, fn ($q, $value) => $q->where('table_name', $value))
            ->when($filters['action'] ?? null, fn ($q, $value) => $q->where('action', $value))
            ->when($filters['admin_id'] ?? null, fn ($q, $value) => $q->where('admin_id', $value))
            ->when($filters['start_date'] ?? null, fn ($q, $value) => $q->whereDate('created_at', '>=', $value))
            ->when($filters['end_date'] ?? null, fn ($q, $value) => $q->whereDate('created_at', '<=', $value))
            ->latest('created_at')
            ->latest('id')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (AuditLog $log) => [
                'id' => $log->id,
                'createdAt' => $log->created_at?->translatedFormat('d M Y H:i'),
                'admin' => $log->admin?->nama ?? 'System',
                'table' => $log->table_name,
                'recordId' => $log->record_id,
                'action' => $log->action,
                'description' => $log->description,
                'ip' => $log->ip_address,
                'oldValues' => $log->old_values,
                'newValues' => $log->new_values,
            ]);

        return Inertia::render('Audit/Index', [
            'items' => $items,
            'filters' => $filters,
            'tables' => AuditLog::query()->distinct()->orderBy('table_name')->pluck('table_name'),
            'admins' => Admin::orderBy('nama')->get(['id', 'nama']),
        ]);
    }
}
