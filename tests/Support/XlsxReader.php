<?php
namespace Tests\Support;
class XlsxReader
{
 public \ZipArchive $zip;
 public function __construct(string $bytes) { $path=tempnam(sys_get_temp_dir(),'xlsx-reader-'); file_put_contents($path,$bytes); $this->zip=new \ZipArchive(); if($this->zip->open($path)!==true) throw new \RuntimeException('XLSX ZIP tidak valid'); }
 public function workbook(): string { return (string)$this->zip->getFromName('xl/workbook.xml'); }
 public function relations(): string { return (string)$this->zip->getFromName('xl/_rels/workbook.xml.rels'); }
 public function sheet(int $number): string { return (string)$this->zip->getFromName("xl/worksheets/sheet{$number}.xml"); }
 public function headers(int $number): array { preg_match_all('/<t>(.*?)<\/t>/s',$this->sheet($number),$m); return array_map(fn($v)=>html_entity_decode($v,ENT_QUOTES|ENT_XML1,'UTF-8'),$m[1] ?? []); }
 public function contains(int $number,string $value): bool { return str_contains($this->sheet($number),htmlspecialchars($value,ENT_XML1|ENT_COMPAT,'UTF-8')); }
 public function close(): void { $this->zip->close(); }
}
