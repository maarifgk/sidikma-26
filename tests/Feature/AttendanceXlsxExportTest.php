<?php
namespace Tests\Feature;
use App\Services\AttendanceXlsxExportService;
use Illuminate\Support\Str;
use Tests\TestCase;
class AttendanceXlsxExportTest extends TestCase
{
 public function test_xlsx_contains_two_sheets_headers_and_sanitized_values(): void
 {
  $response=app(AttendanceXlsxExportService::class)->download([
   ['Tanggal','Nama','Employee ID','Sekolah','Status','Geofence','Mode Geofence','Jarak Datang (m)','Jarak Pulang (m)','Fake GPS','Sumber Fake GPS','Alasan Pulang Awal','Rejection Code','Rejection Reason','Geofence Version','Keterangan'],
   ['16-09-2026','=Nama','E-1','Sekolah A','Hadir','true','radius','10','20','Ya','browser','Pulang','fake_gps','Ditolak','7',"=SUM(A1)"]
  ],[
   ['Nama','Employee ID','Sekolah','Kategori','Tanggal Mulai','Tanggal Selesai','Alasan','Status','Reviewer','Catatan Review','Waktu Pengajuan','Waktu Review'],
   ['Guru','E-1','Sekolah A','Izin','16-09-2026','16-09-2026','Sakit','approved','Admin','-','16-09-2026 08:00:00','16-09-2026 09:00:00']
  ],'laporan-presensi-2026-09-16-2026-09-16.xlsx');
  $this->assertSame('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',$response->headers->get('Content-Type'));
  ob_start(); $response->sendContent(); $bytes=ob_get_clean(); $path=tempnam(sys_get_temp_dir(),'xlsx-test-'); file_put_contents($path,$bytes);
  $zip=new \ZipArchive(); $this->assertSame(true,$zip->open($path));
  $workbook=$zip->getFromName('xl/workbook.xml'); $rels=$zip->getFromName('xl/_rels/workbook.xml.rels'); $sheet1=$zip->getFromName('xl/worksheets/sheet1.xml'); $sheet2=$zip->getFromName('xl/worksheets/sheet2.xml');
  $this->assertNotFalse($workbook); $this->assertNotFalse($rels); $this->assertStringContainsString('Presensi',$workbook); $this->assertStringContainsString('Izin',$workbook); $this->assertStringContainsString('Tanggal',$sheet1); $this->assertStringContainsString('Employee ID',$sheet2); $this->assertStringContainsString("'=Nama",$sheet1); $this->assertStringContainsString("'=SUM(A1)",$sheet1); $this->assertStringContainsString('fake_gps',$sheet1); $this->assertStringContainsString('Ditolak',$sheet1); $this->assertStringContainsString('Geofence Version',$sheet1); $this->assertStringNotContainsString('password',$sheet1.$sheet2); $this->assertStringNotContainsString('token',$sheet1.$sheet2); $this->assertStringNotContainsString('selfie_path',$sheet1.$sheet2); $zip->close(); @unlink($path);
 }
}
