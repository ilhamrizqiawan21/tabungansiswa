<?php
namespace App\Http\Controllers;
use App\Models\Admin;
use App\Models\ApprovalStatus;
use App\Models\TransaksiApproval;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
class ApprovalController extends Controller
{
    public function index(): Response {
        return Inertia::render('Approvals/Index', ['items' => TransaksiApproval::with(['transaksi.siswa','status','requestedBy'])->whereHas('status', fn($q) => $q->where('name','pending'))->latest('request_date')->paginate(15)->through(fn($a) => ['id'=>$a->id,'transaksiId'=>$a->transaksi_id,'tanggal'=>$a->transaksi?->tanggal?->format('d M Y'),'siswa'=>$a->transaksi?->siswa?->nama ?? '-','jenis'=>$a->transaksi?->jenis,'jumlah'=>(float)($a->transaksi?->jumlah ?? 0),'requestedBy'=>$a->requestedBy?->nama ?? '-', 'requestDate'=>$a->request_date?->format('d M Y H:i')])]);
    }
    public function update(Request $request, TransaksiApproval $approval): RedirectResponse {
        abort_unless($request->user('admin')?->isAdmin(), 403);
        $data = $request->validate(['status'=>['required','in:approved,rejected'],'reason'=>['required_if:status,rejected','nullable','string','max:1000']]);
        DB::transaction(function () use ($approval, $data, $request) {
            $approval = TransaksiApproval::whereKey($approval->id)->lockForUpdate()->firstOrFail();
            abort_if($approval->status?->name !== 'pending' && ApprovalStatus::whereKey($approval->status_id)->value('name') !== 'pending', 409, 'Approval sudah diproses.');
            $status = ApprovalStatus::where('name', $data['status'])->firstOrFail();
            $approval->update(['status_id'=>$status->id,'approved_by'=>$request->user('admin')->id,'rejection_reason'=>$data['status']==='rejected' ? $data['reason'] : null,'approval_date'=>now()]);
            $approval->transaksi()->update(['approval_required'=>$data['status'] !== 'approved']);
        });
        return back()->with('success', 'Status approval berhasil diperbarui.');
    }
}
