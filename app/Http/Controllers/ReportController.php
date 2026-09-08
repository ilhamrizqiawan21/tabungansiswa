<?php
namespace App\Http\Controllers;
use App\Models\Transaksi;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Inertia\Inertia;
use Inertia\Response;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
class ReportController extends Controller
{
    private function query(Request $request) { $data=$request->validate(['start_date'=>['nullable','date'],'end_date'=>['nullable','date','after_or_equal:start_date'],'jenis'=>['nullable','in:masuk,keluar']]); return Transaksi::with('siswa')->when($data['start_date']??null,fn($q,$v)=>$q->whereDate('tanggal','>=',$v))->when($data['end_date']??null,fn($q,$v)=>$q->whereDate('tanggal','<=',$v))->when($data['jenis']??null,fn($q,$v)=>$q->where('jenis',$v))->latest('id'); }
    public function index(Request $request): Response { $items=$this->query($request)->paginate(20)->withQueryString()->through(fn($t)=>['id'=>$t->id,'tanggal'=>$t->tanggal->format('d M Y'),'siswa'=>$t->siswa?->nama??'-','jenis'=>$t->jenis,'jumlah'=>(float)$t->jumlah,'saldo'=>(float)$t->saldo,'keterangan'=>$t->keterangan]); $all=$this->query($request)->get(); return Inertia::render('Reports/Index',['items'=>$items,'filters'=>$request->only(['start_date','end_date','jenis']),'summary'=>['masuk'=>(float)$all->where('jenis','masuk')->sum('jumlah'),'keluar'=>(float)$all->where('jenis','keluar')->sum('jumlah'),'count'=>$all->count()]]); }
    public function export(Request $request): StreamedResponse { $rows=$this->query($request)->get(); return response()->streamDownload(function()use($rows){$out=fopen('php://output','w'); fprintf($out,"\xEF\xBB\xBF"); fputcsv($out,['Tanggal','NIS','Nama Siswa','Jenis','Jumlah','Saldo','Keterangan']); foreach($rows as $t) fputcsv($out,[$t->tanggal->format('Y-m-d'),$t->siswa?->nis??'-',$t->siswa?->nama??'-',$t->jenis,$t->jumlah,$t->saldo,$t->keterangan??'']); fclose($out);},'laporan-transaksi-'.now()->format('Ymd-His').'.csv',['Content-Type'=>'text/csv; charset=UTF-8']); }
    public function print(Request $request) { $rows=$this->query($request)->get(); return view('reports.print',['rows'=>$rows,'summary'=>['masuk'=>$rows->where('jenis','masuk')->sum('jumlah'),'keluar'=>$rows->where('jenis','keluar')->sum('jumlah')]]); }
    public function exportXlsx(Request $request) { $book=new Spreadsheet();$sheet=$book->getActiveSheet();$sheet->fromArray([['Tanggal','NIS','Nama Siswa','Jenis','Jumlah','Saldo','Keterangan']],null,'A1');$row=2;foreach($this->query($request)->get() as $t)$sheet->fromArray([[$t->tanggal->format('Y-m-d'),$t->siswa?->nis??'-',$t->siswa?->nama??'-',$t->jenis==='masuk'?'Setoran':'Penarikan',(float)$t->jumlah,(float)$t->saldo,$t->keterangan??'']],null,'A'.$row++);$sheet->getStyle('A1:G1')->getFont()->setBold(true);foreach(['A'=>15,'B'=>15,'C'=>28,'D'=>15,'E'=>16,'F'=>16,'G'=>32] as $c=>$w)$sheet->getColumnDimension($c)->setWidth($w);$writer=new Xlsx($book);return response()->streamDownload(fn()=>$writer->save('php://output'),'laporan-transaksi-'.now()->format('Ymd-His').'.xlsx',['Content-Type'=>'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']); }
    public function exportPdf(Request $request) { $rows=$this->query($request)->get(); return Pdf::loadView('reports.print',['rows'=>$rows,'summary'=>['masuk'=>$rows->where('jenis','masuk')->sum('jumlah'),'keluar'=>$rows->where('jenis','keluar')->sum('jumlah')]])->setPaper('a4','landscape')->download('laporan-transaksi-'.now()->format('Ymd-His').'.pdf'); }
}
