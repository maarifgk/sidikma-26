<?php
namespace App\Services;
class AttendanceXlsxExportService
{
 public function download(array $attendance, array $leave, string $name): \Symfony\Component\HttpFoundation\StreamedResponse
 {
  app(ZipArchiveAvailability::class)->ensureAvailable();
  $tmp = tempnam(sys_get_temp_dir(), 'attendance-xlsx-'); $zip = new \ZipArchive(); $zip->open($tmp, \ZipArchive::OVERWRITE);
  $zip->addFromString('[Content_Types].xml','<?xml version="1.0"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/worksheets/sheet2.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/></Types>');
  $zip->addFromString('_rels/.rels','<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
  $zip->addFromString('xl/workbook.xml','<?xml version="1.0"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Presensi" sheetId="1" r:id="rId1"/><sheet name="Izin" sheetId="2" r:id="rId2"/></sheets></workbook>');
  $zip->addFromString('xl/_rels/workbook.xml.rels','<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet2.xml"/></Relationships>');
  $zip->addFromString('xl/worksheets/sheet1.xml',$this->sheet($attendance)); $zip->addFromString('xl/worksheets/sheet2.xml',$this->sheet($leave)); $zip->close();
  return response()->streamDownload(function() use ($tmp): void { readfile($tmp); @unlink($tmp); }, $name, ['Content-Type'=>'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
 }
 private function sheet(array $rows): string { $xml='<?xml version="1.0"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>'; foreach($rows as $r){$xml.='<row>'; foreach($r as $v){$v=$this->safe((string)($v??''));$xml.='<c t="inlineStr"><is><t>'.htmlspecialchars($v,ENT_XML1|ENT_COMPAT,'UTF-8').'</t></is></c>';} $xml.='</row>';} return $xml.'</sheetData></worksheet>'; }
 private function safe(string $value): string { return preg_match('/^[=+\-@]/',$value) ? "'".$value : $value; }
}
