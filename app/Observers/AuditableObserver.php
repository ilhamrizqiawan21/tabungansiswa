<?php
namespace App\Observers;
use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
class AuditableObserver
{
    public function created(Model $model): void { $this->write($model, 'CREATE', null, $model->getAttributes()); }
    public function updated(Model $model): void { $this->write($model, 'UPDATE', $model->getOriginal(), $model->getChanges()); }
    public function deleted(Model $model): void { $this->write($model, 'DELETE', $model->getOriginal(), null); }
    private function write(Model $model, string $action, ?array $old, ?array $new): void
    {
        $admin = Auth::guard('admin')->user();
        AuditLog::create(['admin_id'=>$admin?->id,'table_name'=>$model->getTable(),'record_id'=>$model->getKey(),'action'=>$action,'old_values'=>$old,'new_values'=>$new,'description'=>"{$action} pada {$model->getTable()}",'ip_address'=>request()->ip(),'user_agent'=>request()->userAgent(),'created_at'=>now()]);
    }
}
