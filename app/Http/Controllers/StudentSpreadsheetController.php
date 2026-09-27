<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportStudentsRequest;
use App\Models\Kelas;
use App\Models\Setting;
use App\Models\Siswa;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as Reader;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class StudentSpreadsheetController extends Controller
{
    public function import(ImportStudentsRequest $request)
    {
        $class = Kelas::find(Setting::get('active_class_id'));
        if (! $class) {
            throw ValidationException::withMessages(['file' => 'Atur kelas aktif terlebih dahulu.']);
        }
        $reader = new Reader;
        try {
            $info = $reader->listWorksheetInfo($request->file('file')->getRealPath());
            if (! $info || $info[0]['totalRows'] > 1001 || $info[0]['totalColumns'] > 20) {
                throw new \RuntimeException('Ukuran lembar tidak didukung.');
            }
            $reader->setLoadSheetsOnly($info[0]['worksheetName']);
            $book = $reader->load($request->file('file')->getRealPath());
        } catch (\Throwable $e) {
            throw ValidationException::withMessages(['file' => 'File tidak dapat dibaca. Gunakan template XLSX, maksimal 1.000 siswa dan 2 MB.']);
        }
        try {
            $sheet = $book->getSheet(0);
            $headers = [];
            foreach ($sheet->rangeToArray('A1:'.$sheet->getHighestDataColumn().'1', null, false, true)[0] as $index => $header) {
                $key = strtolower(trim((string) $header));
                if (in_array($key, ['nis', 'nama', 'kontak'])) {
                    if (isset($headers[$key])) {
                        throw ValidationException::withMessages(['file' => 'Kolom '.$key.' muncul lebih dari sekali.']);
                    }
                    $headers[$key] = $index + 1;
                }
            }
            if (! isset($headers['nis'], $headers['nama'])) {
                throw ValidationException::withMessages(['file' => 'Baris pertama harus memuat kolom NIS dan Nama. Kontak opsional.']);
            }
            $records = [];
            $seen = [];
            $skipped = 0;
            for ($row = 2; $row <= $sheet->getHighestDataRow(); $row++) {
                $data = ['nis' => '', 'nama' => '', 'kontak' => ''];
                foreach ($headers as $key => $col) {
                    $cell = $sheet->getCell([$col, $row]);
                    if ($cell->getDataType() === DataType::TYPE_FORMULA) {
                        throw ValidationException::withMessages(['file' => "Baris {$row}: gunakan teks, bukan rumus."]);
                    }
                    $data[$key] = trim((string) $cell->getFormattedValue());
                }
                if (implode('', $data) === '') {
                    continue;
                }
                $validator = Validator::make($data, ['nis' => 'required|string|max:20', 'nama' => 'required|string|max:100', 'kontak' => 'nullable|string|max:25']);
                if ($validator->fails()) {
                    throw ValidationException::withMessages(['file' => "Baris {$row}: ".$validator->errors()->first().' Tidak ada data yang diimpor.']);
                }
                if (isset($seen[$data['nis']]) || Siswa::where('nis', $data['nis'])->exists()) {
                    $skipped++;

                    continue;
                }
                $seen[$data['nis']] = true;
                $records[] = $data + ['kelas_id' => $class->id];
            }
            if (! $records && ! $skipped) {
                throw ValidationException::withMessages(['file' => 'File belum berisi data siswa.']);
            }
            DB::transaction(function () use ($records) {
                foreach ($records as $data) {
                    Siswa::create($data);
                }
            });
        } finally {
            $book->disconnectWorksheets();
        }

        return back()->with('success', 'Impor selesai: '.count($records)." siswa ditambahkan, {$skipped} NIS duplikat dilewati. Data lama tidak diubah.");
    }

    public function template()
    {
        return $this->download([], 'template-siswa.xlsx');
    }

    public function export()
    {
        $class = Kelas::find(Setting::get('active_class_id'));
        if (! $class) {
            return back()->with('error', 'Atur kelas aktif sebelum mengekspor siswa.');
        }

        return $this->download(Siswa::where('kelas_id', $class->id)->orderBy('nama')->get(['nis', 'nama', 'kontak'])->toArray(), 'siswa-kelas-'.$class->id.'-'.now()->format('Ymd').'.xlsx');
    }

    private function download(array $rows, string $filename)
    {
        $book = new Spreadsheet;
        $sheet = $book->getActiveSheet();
        $sheet->setTitle('Data Siswa');
        $sheet->fromArray([['NIS', 'Nama', 'Kontak']], null, 'A1');
        $sheet->getStyle('A1:C1')->getFont()->setBold(true);
        $sheet->getStyle('A2:C'.($rows ? count($rows) + 1 : 1001))->getNumberFormat()->setFormatCode('@');
        foreach (['A' => 22, 'B' => 40, 'C' => 25] as $col => $width) {
            $sheet->getColumnDimension($col)->setWidth($width);
        }
        foreach ($rows as $index => $row) {
            foreach (['nis', 'nama', 'kontak'] as $col => $key) {
                $sheet->setCellValueExplicit([$col + 1, $index + 2], (string) ($row[$key] ?? ''), DataType::TYPE_STRING);
            }
        }
        $sheet->freezePane('A2');
        $sheet->setAutoFilter('A1:C'.max(1, count($rows) + 1));

        return response()->streamDownload(function () use ($book) {
            try {
                (new Xlsx($book))->save('php://output');
            } finally {
                $book->disconnectWorksheets();
            }
        }, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }
}
