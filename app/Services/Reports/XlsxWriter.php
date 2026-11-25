<?php

namespace App\Services\Reports;

use RuntimeException;
use ZipArchive;

/**
 * Penulis .xlsx ringan tanpa library tambahan (butuh ekstensi zip PHP).
 * Gaya tidak ditulis di sini: nama gaya dipetakan dari definisi yang diberikan (lihat ReportXlsxStyle).
 */
class XlsxWriter
{
    /** @var XlsxSheet[] */
    private array $sheets = [];

    /** @param array<string, array> $styles nama => definisi gaya */
    public function __construct(private array $styles) {}

    public function sheet(string $name, ?string $tabColor = null): XlsxSheet
    {
        $sheet = new XlsxSheet(mb_substr(preg_replace('/[\\\\\/\?\*\[\]:]/', ' ', $name), 0, 31));
        $sheet->tabColor = $tabColor;

        return $this->sheets[] = $sheet;
    }

    public function save(string $path): void
    {
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('Ekstensi zip PHP belum aktif.');
        }
        if (! $this->sheets) {
            throw new RuntimeException('Belum ada lembar kerja.');
        }

        [$stylesXml, $map] = $this->stylesXml();
        $zip = new ZipArchive();
        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Tidak bisa membuat berkas Excel.');
        }

        $n = count($this->sheets);
        $overrides = '';
        $sheetRefs = '';
        $rels = '';
        $names = '';
        foreach ($this->sheets as $i => $sheet) {
            $k = $i + 1;
            $overrides .= '<Override PartName="/xl/worksheets/sheet'.$k.'.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
            $sheetRefs .= '<sheet name="'.$this->esc($sheet->name).'" sheetId="'.$k.'" r:id="rId'.$k.'"/>';
            $rels .= '<Relationship Id="rId'.$k.'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet'.$k.'.xml"/>';
            if ($sheet->filter) {
                $names .= '<definedName name="_xlnm._FilterDatabase" localSheetId="'.$i.'" hidden="1">\''.$this->esc($sheet->name).'\'!'.preg_replace('/([A-Z]+)(\d+)/', '\$$1\$$2', $sheet->filter).'</definedName>';
            }
            $zip->addFromString('xl/worksheets/sheet'.$k.'.xml', $this->sheetXml($sheet, $map));
        }
        $rels .= '<Relationship Id="rId'.($n + 1).'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>';

        $head = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
        $zip->addFromString('[Content_Types].xml', $head.'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'.$overrides.'</Types>');
        $zip->addFromString('_rels/.rels', $head.'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
        $zip->addFromString('xl/workbook.xml', $head.'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><bookViews><workbookView xWindow="0" yWindow="0" windowWidth="24000" windowHeight="12000"/></bookViews><sheets>'.$sheetRefs.'</sheets>'.($names ? '<definedNames>'.$names.'</definedNames>' : '').'</workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels', $head.'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'.$rels.'</Relationships>');
        $zip->addFromString('xl/styles.xml', $stylesXml);
        $zip->close();
    }

    private function sheetXml(XlsxSheet $s, array $map): string
    {
        $maxCol = 1;
        foreach ($s->rows as $r) {
            $maxCol = max($maxCol, count($r['cells']));
        }
        $lastRow = max(1, count($s->rows));

        $x = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">';
        $x .= '<sheetPr>'.($s->tabColor ? '<tabColor rgb="FF'.$s->tabColor.'"/>' : '').'<pageSetUpPr fitToPage="1"/></sheetPr>';
        $x .= '<dimension ref="A1:'.$this->col($maxCol).$lastRow.'"/>';
        $x .= '<sheetViews><sheetView showGridLines="0" workbookViewId="0"'.($s === $this->sheets[0] ? ' tabSelected="1"' : '').'>';
        if ($s->freezeAfterRow) {
            $x .= '<pane ySplit="'.$s->freezeAfterRow.'" topLeftCell="A'.($s->freezeAfterRow + 1).'" activePane="bottomLeft" state="frozen"/><selection pane="bottomLeft" activeCell="A'.($s->freezeAfterRow + 1).'" sqref="A'.($s->freezeAfterRow + 1).'"/>';
        }
        $x .= '</sheetView></sheetViews><sheetFormatPr defaultRowHeight="18" customHeight="1"/>';

        if ($s->widths) {
            ksort($s->widths);
            $x .= '<cols>';
            foreach ($s->widths as $c => $w) {
                $x .= '<col min="'.$c.'" max="'.$c.'" width="'.$w.'" customWidth="1"/>';
            }
            $x .= '</cols>';
        }

        $x .= '<sheetData>';
        foreach ($s->rows as $i => $r) {
            $n = $i + 1;
            $x .= '<row r="'.$n.'"'.($r['height'] ? ' ht="'.$r['height'].'" customHeight="1"' : '').'>';
            foreach (array_values($r['cells']) as $j => $cell) {
                $x .= $this->cellXml($this->col($j + 1).$n, $cell, $map);
            }
            $x .= '</row>';
        }
        $x .= '</sheetData>';

        if ($s->filter) {
            $x .= '<autoFilter ref="'.$s->filter.'"/>';
        }
        if ($s->merges) {
            $x .= '<mergeCells count="'.count($s->merges).'">';
            foreach ($s->merges as $m) {
                $x .= '<mergeCell ref="'.$m.'"/>';
            }
            $x .= '</mergeCells>';
        }
        $x .= '<pageMargins left="0.5" right="0.5" top="0.6" bottom="0.6" header="0.3" footer="0.3"/>';
        $x .= '<pageSetup paperSize="9" orientation="landscape" fitToWidth="1" fitToHeight="0"/>';

        return $x.'</worksheet>';
    }

    private function cellXml(string $ref, mixed $cell, array $map): string
    {
        $style = null;
        if (is_array($cell)) {
            [$cell, $style] = [$cell[0] ?? null, $cell[1] ?? null];
        }
        $s = $style !== null && isset($map[$style]) ? ' s="'.$map[$style].'"' : '';

        if ($cell === null || $cell === '') {
            return '<c r="'.$ref.'"'.$s.'/>';
        }
        if (is_int($cell)) {
            return '<c r="'.$ref.'"'.$s.'><v>'.$cell.'</v></c>';
        }
        if (is_float($cell)) {
            return '<c r="'.$ref.'"'.$s.'><v>'.rtrim(rtrim(sprintf('%.10F', $cell), '0'), '.').'</v></c>';
        }

        return '<c r="'.$ref.'"'.$s.' t="inlineStr"><is><t xml:space="preserve">'.$this->esc((string) $cell).'</t></is></c>';
    }

    /** @return array{0:string, 1:array<string,int>} xml styles dan peta nama gaya => indeks xf */
    private function stylesXml(): array
    {
        $fonts = ['<font><sz val="11"/><color rgb="FF1F2A3D"/><name val="Calibri"/><family val="2"/></font>'];
        $fills = ['<fill><patternFill patternType="none"/></fill>', '<fill><patternFill patternType="gray125"/></fill>'];
        $borders = ['<border><left/><right/><top/><bottom/><diagonal/></border>'];
        $fmts = [];
        $xfs = ['<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'];
        $map = [];

        foreach ($this->styles as $name => $d) {
            $f = $d['font'] ?? [];
            $font = '<font>'.(! empty($f['bold']) ? '<b/>' : '').(! empty($f['italic']) ? '<i/>' : '')
                .'<sz val="'.($f['size'] ?? 11).'"/><color rgb="FF'.($f['color'] ?? '1F2A3D').'"/><name val="'.($f['name'] ?? 'Calibri').'"/><family val="2"/></font>';
            $fi = $this->idx($fonts, $font);

            $fill = isset($d['fill']) ? '<fill><patternFill patternType="solid"><fgColor rgb="FF'.$d['fill'].'"/><bgColor indexed="64"/></patternFill></fill>' : null;
            $fli = $fill ? $this->idx($fills, $fill) : 0;

            $b = $d['border'] ?? [];
            $bx = '<border>';
            foreach (['left', 'right', 'top', 'bottom'] as $side) {
                $bx .= isset($b[$side]) ? '<'.$side.' style="'.$b[$side][0].'"><color rgb="FF'.$b[$side][1].'"/></'.$side.'>' : '<'.$side.'/>';
            }
            $bx .= '<diagonal/></border>';
            $bi = $this->idx($borders, $bx);

            $nf = 0;
            if (! empty($d['format'])) {
                $nf = 164 + $this->idx($fmts, $d['format']);
            }

            $a = $d['align'] ?? [];
            $align = '<alignment'.(isset($a['h']) ? ' horizontal="'.$a['h'].'"' : '').' vertical="'.($a['v'] ?? 'center').'"'
                .(! empty($a['wrap']) ? ' wrapText="1"' : '').(isset($a['indent']) ? ' indent="'.$a['indent'].'"' : '').'/>';

            $xfs[] = '<xf numFmtId="'.$nf.'" fontId="'.$fi.'" fillId="'.$fli.'" borderId="'.$bi.'" xfId="0" applyNumberFormat="1" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1">'.$align.'</xf>';
            $map[$name] = count($xfs) - 1;
        }

        $x = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">';
        if ($fmts) {
            $x .= '<numFmts count="'.count($fmts).'">';
            foreach ($fmts as $i => $code) {
                $x .= '<numFmt numFmtId="'.(164 + $i).'" formatCode="'.$this->esc($code).'"/>';
            }
            $x .= '</numFmts>';
        }
        $x .= '<fonts count="'.count($fonts).'">'.implode('', $fonts).'</fonts>';
        $x .= '<fills count="'.count($fills).'">'.implode('', $fills).'</fills>';
        $x .= '<borders count="'.count($borders).'">'.implode('', $borders).'</borders>';
        $x .= '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>';
        $x .= '<cellXfs count="'.count($xfs).'">'.implode('', $xfs).'</cellXfs>';
        $x .= '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>';

        return [$x, $map];
    }

    private function idx(array &$list, string $xml): int
    {
        $i = array_search($xml, $list, true);
        if ($i === false) {
            $list[] = $xml;
            $i = count($list) - 1;
        }

        return $i;
    }

    private function col(int $n): string
    {
        $s = '';
        while ($n > 0) {
            $m = ($n - 1) % 26;
            $s = chr(65 + $m).$s;
            $n = intdiv($n - 1, 26);
        }

        return $s;
    }

    private function esc(string $v): string
    {
        $v = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', $v) ?? '';

        return htmlspecialchars($v, ENT_XML1 | ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
