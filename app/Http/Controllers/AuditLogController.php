<?php
namespace App\Http\Controllers;
use App\Models\AuditLog;
use Inertia\Inertia;
use Inertia\Response;
class AuditLogController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Audit/Index', ['items' => AuditLog::with('admin')->latest('created_at')->paginate(20)->through(fn($log) => ['id'=>$log->id,'createdAt'=>$log->created_at?->format('d M Y H:i'),'admin'=>$log->admin?->nama ?? 'System','table'=>$log->table_name,'recordId'=>$log->record_id,'action'=>$log->action,'description'=>$log->description,'ip'=>$log->ip_address])]);
    }
}
