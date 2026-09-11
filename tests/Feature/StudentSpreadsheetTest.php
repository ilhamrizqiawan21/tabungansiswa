<?php

namespace Tests\Feature;

use App\Models\Kelas;
use App\Models\Setting;
use App\Models\Siswa;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class StudentSpreadsheetTest extends TestCase
{
    use RefreshDatabase;

    private Kelas $class;

    protected function setUp(): void
    {
        parent::setUp();
        $this->class = Kelas::create(['nama_kelas' => 'VII A', 'tingkat' => 'VII']);
        Setting::put('active_class_id', (string) $this->class->id);
    }

    private function upload(array $rows): UploadedFile
    {
        $book = new Spreadsheet;
        foreach ($rows as $r => $row) {
            foreach ($row as $c => $value) {
                $book->getActiveSheet()->setCellValueExplicit([$c + 1, $r + 1], $value, DataType::TYPE_STRING);
            }
        }
        ob_start();
        (new Xlsx($book))->save('php://output');
        $content = ob_get_clean();
        $book->disconnectWorksheets();

        return UploadedFile::fake()->createWithContent('siswa.xlsx', $content);
    }

    public function test_import_preserves_zeroes_and_skips_duplicate_nis(): void
    {
        $this->from('/master/siswa')->post('/master/siswa/import', ['file' => $this->upload([
            ['NIS', 'Nama', 'Kontak'],
            ['00123', 'Budi', '0812345678'],
            ['00123', 'Nama lain', ''],
        ])])->assertSessionHasNoErrors()->assertSessionHas('success');
        $this->assertDatabaseCount('siswa', 1);
        $this->assertDatabaseHas('siswa', ['nis' => '00123', 'nama' => 'Budi', 'kontak' => '0812345678', 'kelas_id' => $this->class->id]);
    }

    public function test_invalid_row_does_not_import_partial_data(): void
    {
        $this->post('/master/siswa/import', ['file' => $this->upload([
            ['NIS', 'Nama', 'Kontak'],
            ['001', 'Budi', ''],
            ['002', '', ''],
        ])])->assertSessionHasErrors('file');
        $this->assertDatabaseCount('siswa', 0);
    }

    public function test_missing_class_and_invalid_header_are_reported(): void
    {
        $this->post('/master/siswa/import', ['file' => $this->upload([['Wrong'], ['001']])])->assertSessionHasErrors('file');
        Setting::put('active_class_id', '');
        $this->post('/master/siswa/import', ['file' => $this->upload([['NIS', 'Nama'], ['001', 'Budi']])])->assertSessionHasErrors('file');
    }

    public function test_export_is_scoped_and_uses_text_cells(): void
    {
        Siswa::create(['nis' => '001', 'nama' => '=1+1', 'kontak' => '08123', 'kelas_id' => $this->class->id]);
        Siswa::create(['nis' => '002', 'nama' => 'Outside', 'kelas_id' => null]);
        $response = $this->get('/master/siswa/export')->assertOk();
        $path = tempnam(sys_get_temp_dir(), 'student-export-');
        try {
            file_put_contents($path, $response->streamedContent());
            $book = IOFactory::load($path);
            $sheet = $book->getActiveSheet();
            $this->assertSame(2, $sheet->getHighestDataRow());
            $this->assertSame('001', $sheet->getCell('A2')->getValue());
            $this->assertSame('08123', $sheet->getCell('C2')->getValue());
            $this->assertSame(DataType::TYPE_STRING, $sheet->getCell('B2')->getDataType());
            $book->disconnectWorksheets();
        } finally {
            unlink($path);
        }
    }

    public function test_spreadsheet_endpoints_are_available_without_login(): void
    {
        $this->get('/master/siswa/template')->assertDownload('template-siswa.xlsx');
        auth('admin')->logout();
        $this->get('/master/siswa/export')->assertOk();
        $this->get('/master/siswa/template')->assertDownload('template-siswa.xlsx');
        $this->post('/master/siswa/import')->assertSessionHasErrors('file');
    }
}
