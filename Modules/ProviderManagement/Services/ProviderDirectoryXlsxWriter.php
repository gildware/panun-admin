<?php

namespace Modules\ProviderManagement\Services;

use App\Support\CloudStorageConfigurator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Comment\Comment;
use OpenSpout\Common\Entity\Comment\TextRun;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\CellVerticalAlignment;
use OpenSpout\Common\Entity\Style\Color;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\Common\Helper\CellHelper;
use OpenSpout\Writer\XLSX\Options;
use OpenSpout\Writer\XLSX\Writer;
use ZipArchive;

/**
 * Excel download for the provider directory.
 * A web address is a clickable "Link". A photo column is the picture itself.
 */
class ProviderDirectoryXlsxWriter
{
    private const MAX_EMBEDDED = 500;

    private const MAX_PER_CELL = 6;

    private const THUMB = 48;

    private const GAP = 4;

    private const PAD = 4;

    private const EMU = 9525;

    private string $linkLabel = 'Link';

    /** @var array<string, string|null> */
    private array $imageCache = [];

    private int $embedded = 0;

    /** @var list<array{col: int, row: int, index: int, bytes: string}> */
    private array $pictures = [];

    /** @var list<array{ref: string, url: string}> */
    private array $hyperlinks = [];

    /** @var array<int, int> */
    private array $pictureCounts = [];

    private Style $headerStyle;

    private Style $linkStyle;

    private Style $linkWrapStyle;

    private Style $wrapStyle;

    private Style $imageRowStyle;

    public function __construct(private ProviderDirectoryExcelExport $export) {}

    /**
     * @param  list<string>  $keys
     * @param  array<string, string>  $labels
     */
    public function write(string $path, Builder $query, array $keys, int $limit, array $labels): void
    {
        $this->writeRows($path, $keys, $this->export->headerLabels($keys, $labels), $this->export->streamValueRows($query, $keys, $limit));
    }

    /**
     * @param  list<string>  $keys
     * @param  array<string, string>  $headers
     * @param  iterable<array<string, string>>  $rows
     */
    public function writeRows(string $path, array $keys, array $headers, iterable $rows): void
    {
        @set_time_limit(0);
        $this->reset();
        $directory = dirname($path);
        if (! is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $options = new Options();
        $options->setTempFolder(storage_path('framework/cache'));
        $writer = new Writer($options);
        $writer->openToFile($path);

        try {
            $headerCells = [];
            foreach ($keys as $key) {
                $headerCells[] = Cell::fromValue((string) ($headers[$key] ?? $key), $this->headerStyle);
            }
            $writer->addRow(new Row($headerCells));

            $excelRow = 2;
            foreach ($rows as $row) {
                $built = $this->buildRow($keys, $row, $excelRow);
                $sheetRow = new Row($built['cells'], $built['images'] > 0 ? $this->imageRowStyle : null);
                if ($built['images'] > 0) {
                    $sheetRow->setHeight($this->imageRowHeight($built['images']));
                }
                $writer->addRow($sheetRow);
                $excelRow++;
            }
        } finally {
            $writer->close();
        }

        $this->applyWorkbookExtras($path);
    }

    /**
     * @param  list<string>  $keys
     * @param  array<string, string>  $row
     * @return array{cells: list<Cell>, images: int}
     */
    private function buildRow(array $keys, array $row, int $excelRow): array
    {
        $cells = [];
        $rowImages = 0;
        $imageKeys = array_flip($this->export->imageKeys());

        foreach ($keys as $column => $key) {
            $value = (string) ($row[$key] ?? '');
            if (isset($imageKeys[$key])) {
                $placed = $this->placeImages($value, $column, $excelRow);
                $rowImages = max($rowImages, $placed['count']);
                $cells[] = $placed['cell'];
                continue;
            }

            $cells[] = $this->textCell($value, $column, $excelRow);
        }

        return ['cells' => $cells, 'images' => $rowImages];
    }

    /**
     * @return array{cell: Cell, count: int}
     */
    private function placeImages(string $value, int $column, int $excelRow): array
    {
        $urls = array_slice($this->urlsIn($value), 0, self::MAX_PER_CELL);
        if ($urls === []) {
            return ['cell' => Cell::fromValue(''), 'count' => 0];
        }

        $saved = 0;
        $missed = [];
        foreach ($urls as $url) {
            if ($this->embedded >= self::MAX_EMBEDDED) {
                $missed[] = $url;
                continue;
            }
            $bytes = $this->imageBytes($url);
            if ($bytes === null) {
                $missed[] = $url;
                continue;
            }
            $this->pictures[] = [
                'col' => $column,
                'row' => $excelRow - 1,
                'index' => $saved,
                'bytes' => $bytes,
            ];
            $saved++;
            $this->embedded++;
        }

        if ($saved > 0) {
            $this->pictureCounts[$column] = max($this->pictureCounts[$column] ?? 0, $saved);
        }

        if ($saved === 0) {
            return ['cell' => $this->linkCell($missed, $column, $excelRow), 'count' => 0];
        }

        $cell = Cell::fromValue('');
        if ($missed !== []) {
            $cell->comment = $this->urlComment($missed);
        }

        return ['cell' => $cell, 'count' => $saved];
    }

    /**
     * @param  list<string>  $urls
     */
    private function linkCell(array $urls, int $column, int $excelRow): Cell
    {
        $urls = array_values(array_filter($urls, fn ($url) => $url !== ''));
        if ($urls === []) {
            return Cell::fromValue('');
        }
        if (count($urls) === 1) {
            return Cell::fromValue($this->hyperlinkFormula($urls[0]), $this->linkStyle);
        }

        $cell = Cell::fromValue(implode("\n", array_fill(0, count($urls), $this->linkLabel)), $this->linkWrapStyle);
        $this->rememberHyperlink($column, $excelRow, $urls[0]);
        $cell->comment = $this->urlComment($urls);

        return $cell;
    }

    private function textCell(string $value, int $column, int $excelRow): Cell
    {
        if ($value === '') {
            return Cell::fromValue('');
        }

        $urls = $this->urlsIn($value);
        $text = $this->textWithoutUrls($value);
        if ($urls === []) {
            $style = str_contains($value, "\n") ? $this->wrapStyle : null;

            return Cell::fromValue($value, $style);
        }
        if ($text === '') {
            return $this->linkCell($urls, $column, $excelRow);
        }

        $lines = [$text];
        foreach ($urls as $unused) {
            $lines[] = $this->linkLabel;
        }
        $cell = Cell::fromValue(implode("\n", $lines), $this->linkWrapStyle);
        $this->rememberHyperlink($column, $excelRow, $urls[0]);
        if (count($urls) > 1) {
            $cell->comment = $this->urlComment($urls);
        }

        return $cell;
    }

    private function hyperlinkFormula(string $url): string
    {
        return '=HYPERLINK("'.$this->formulaString($url).'","'.$this->formulaString($this->linkLabel).'")';
    }

    private function formulaString(string $value): string
    {
        return str_replace('"', '""', $value);
    }

    private function rememberHyperlink(int $column, int $excelRow, string $url): void
    {
        $this->hyperlinks[] = [
            'ref' => CellHelper::getColumnLettersFromColumnIndex($column).$excelRow,
            'url' => $url,
        ];
    }

    /**
     * @param  list<string>  $urls
     */
    private function urlComment(array $urls): Comment
    {
        $comment = new Comment();
        $comment->width = '280pt';
        $comment->height = '90pt';
        $comment->addTextRun(new TextRun(implode("\n", $urls)));

        return $comment;
    }

    /**
     * @return list<string>
     */
    private function urlsIn(string $value): array
    {
        $urls = [];
        foreach (preg_split('/\R/u', $value) ?: [] as $line) {
            $line = trim($line);
            if (preg_match('#^https?://#i', $line) === 1) {
                $urls[] = $this->cleanUrl($line);
            }
        }

        return $urls;
    }

    private function textWithoutUrls(string $value): string
    {
        $lines = [];
        foreach (preg_split('/\R/u', $value) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || preg_match('#^https?://#i', $line) === 1) {
                continue;
            }
            $lines[] = $line;
        }

        return implode("\n", $lines);
    }

    private function cleanUrl(string $url): string
    {
        $url = preg_replace('/[\x00-\x1F\x7F]/', '', trim($url)) ?? trim($url);

        return $url;
    }

    private function imageBytes(string $url): ?string
    {
        if (array_key_exists($url, $this->imageCache)) {
            return $this->imageCache[$url];
        }

        $raw = $this->readLocalPublicFile($url) ?? $this->readCloudFile($url) ?? $this->httpGet($url);
        $jpeg = is_string($raw) && $raw !== '' ? $this->thumbnail($raw) : null;
        $this->imageCache[$url] = $jpeg;

        return $jpeg;
    }

    private function readLocalPublicFile(string $url): ?string
    {
        $path = (string) (parse_url($url, PHP_URL_PATH) ?? '');
        $marker = '/storage/';
        $pos = strpos($path, $marker);
        if ($pos === false) {
            return null;
        }

        $relative = rawurldecode(ltrim(substr($path, $pos + strlen($marker)), '/'));
        if ($relative === '' || str_contains($relative, '..')) {
            return null;
        }

        $full = storage_path('app/public/'.$relative);
        if (! is_file($full)) {
            return null;
        }

        $bytes = file_get_contents($full);

        return is_string($bytes) ? $bytes : null;
    }

    private function readCloudFile(string $url): ?string
    {
        $base = rtrim((string) CloudStorageConfigurator::publicBaseUrl(), '/');
        if ($base === '' || ! str_starts_with($url, $base.'/')) {
            return null;
        }

        $key = rawurldecode(ltrim((string) parse_url(substr($url, strlen($base)), PHP_URL_PATH), '/'));
        if ($key === '' || str_contains($key, '..')) {
            return null;
        }

        try {
            $bytes = Storage::disk('s3')->get($key);
        } catch (\Throwable) {
            return null;
        }

        return is_string($bytes) && $bytes !== '' ? $bytes : null;
    }

    private function httpGet(string $url): ?string
    {
        $parts = parse_url($url);
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) ($parts['host'] ?? ''));
        if (! in_array($scheme, ['http', 'https'], true) || $host === '' || $this->isOwnHost($host)) {
            return null;
        }
        if (! function_exists('curl_init')) {
            return null;
        }

        $handle = curl_init($url);
        if ($handle === false) {
            return null;
        }

        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_CONNECTTIMEOUT => 4,
            CURLOPT_TIMEOUT => 12,
            CURLOPT_USERAGENT => 'PanunProviderExport',
        ];
        if (defined('CURLPROTO_HTTP') && defined('CURLPROTO_HTTPS')) {
            $options[CURLOPT_PROTOCOLS] = CURLPROTO_HTTP | CURLPROTO_HTTPS;
        }
        curl_setopt_array($handle, $options);
        $body = curl_exec($handle);
        $code = (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);
        curl_close($handle);
        if (! is_string($body) || $body === '' || $code >= 400 || strlen($body) > 8_000_000) {
            return null;
        }

        return $body;
    }

    private function isOwnHost(string $host): bool
    {
        $host = strtolower(trim($host, '[]'));
        if ($host === 'localhost' || $host === '::1' || str_starts_with($host, '127.')) {
            return true;
        }

        $requestHost = strtolower((string) request()?->getHost());

        return $requestHost !== '' && $host === $requestHost;
    }

    private function thumbnail(string $raw): ?string
    {
        if (! function_exists('imagecreatefromstring') || ! function_exists('imagejpeg')) {
            return null;
        }

        $info = @getimagesizefromstring($raw);
        if ($info === false || ($info[0] * $info[1]) > 20_000_000) {
            return null;
        }

        $source = @imagecreatefromstring($raw);
        if ($source === false) {
            return null;
        }

        $width = imagesx($source);
        $height = imagesy($source);
        $scale = min(160 / max(1, $width), 160 / max(1, $height), 1);
        $targetWidth = max(1, (int) round($width * $scale));
        $targetHeight = max(1, (int) round($height * $scale));
        $target = imagecreatetruecolor($targetWidth, $targetHeight);
        if ($target === false) {
            imagedestroy($source);

            return null;
        }

        $white = imagecolorallocate($target, 255, 255, 255);
        imagefill($target, 0, 0, $white);
        imagecopyresampled($target, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);
        ob_start();
        $saved = imagejpeg($target, null, 75);
        $jpeg = ob_get_clean();
        imagedestroy($source);
        imagedestroy($target);

        return $saved && is_string($jpeg) && $jpeg !== '' ? $jpeg : null;
    }

    private function imageRowHeight(int $count): float
    {
        $rows = (int) ceil($count / 3);
        $px = (self::PAD * 2) + ($rows * self::THUMB) + (($rows - 1) * self::GAP);

        return (int) ceil($px * 0.75) + 4;
    }

    private function imageColumnWidth(int $count): float
    {
        $across = min(3, max(1, $count));
        $px = 16 + ($across * self::THUMB) + (($across - 1) * self::GAP);

        return round(max(14, $px / 6.5), 2);
    }

    private function applyWorkbookExtras(string $path): void
    {
        if ($this->pictures === [] && $this->hyperlinks === [] && $this->pictureCounts === []) {
            return;
        }

        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            return;
        }

        if ($this->pictures !== []) {
            $this->addDrawings($zip);
        }
        $this->patchWorksheet($zip);
        $zip->close();
    }

    private function addDrawings(ZipArchive $zip): void
    {
        $anchors = '';
        $rels = '';
        foreach ($this->pictures as $i => $picture) {
            $id = $i + 1;
            $name = 'image'.$id.'.jpeg';
            $zip->addFromString('xl/media/'.$name, $picture['bytes']);
            $rels .= '<Relationship Id="rId'.$id.'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="../media/'.$name.'"/>';
            $anchors .= $this->anchorXml($picture['col'], $picture['row'], $picture['index'], $id);
        }

        $zip->addFromString('xl/drawings/_rels/drawing1.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'.$rels.'</Relationships>');
        $zip->addFromString('xl/drawings/drawing1.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<xdr:wsDr xmlns:xdr="http://schemas.openxmlformats.org/drawingml/2006/spreadsheetDrawing" xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .$anchors
            .'</xdr:wsDr>');
    }

    private function anchorXml(int $column, int $row, int $index, int $id): string
    {
        $across = $index % 3;
        $down = intdiv($index, 3);
        $offX = (self::PAD + ($across * (self::THUMB + self::GAP))) * self::EMU;
        $offY = (self::PAD + ($down * (self::THUMB + self::GAP))) * self::EMU;
        $size = self::THUMB * self::EMU;

        return '<xdr:oneCellAnchor>'
            .'<xdr:from><xdr:col>'.$column.'</xdr:col><xdr:colOff>'.$offX.'</xdr:colOff><xdr:row>'.$row.'</xdr:row><xdr:rowOff>'.$offY.'</xdr:rowOff></xdr:from>'
            .'<xdr:ext cx="'.$size.'" cy="'.$size.'"/>'
            .'<xdr:pic><xdr:nvPicPr><xdr:cNvPr id="'.$id.'" name="Picture '.$id.'"/><xdr:cNvPicPr><a:picLocks noChangeAspect="1"/></xdr:cNvPicPr></xdr:nvPicPr>'
            .'<xdr:blipFill><a:blip r:embed="rId'.$id.'"/><a:stretch><a:fillRect/></a:stretch></xdr:blipFill>'
            .'<xdr:spPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="'.$size.'" cy="'.$size.'"/></a:xfrm><a:prstGeom prst="rect"><a:avLst/></a:prstGeom></xdr:spPr>'
            .'</xdr:pic><xdr:clientData/></xdr:oneCellAnchor>';
    }

    private function patchWorksheet(ZipArchive $zip): void
    {
        $sheetName = 'xl/worksheets/sheet1.xml';
        $sheet = $zip->getFromName($sheetName);
        if (! is_string($sheet)) {
            return;
        }

        $insert = '';
        if ($this->hyperlinks !== []) {
            $links = '';
            foreach ($this->hyperlinks as $i => $link) {
                $links .= '<hyperlink ref="'.$this->xml($link['ref']).'" r:id="rIdLink'.($i + 1).'"/>';
            }
            $insert .= '<hyperlinks>'.$links.'</hyperlinks>';
        }
        if ($this->pictures !== []) {
            $insert .= '<drawing r:id="rIdDrawing1"/>';
        }
        if ($insert !== '' && str_contains($sheet, '<legacyDrawing')) {
            $sheet = str_replace('<legacyDrawing', $insert.'<legacyDrawing', $sheet);
        }

        if ($this->pictureCounts !== []) {
            $cols = '<cols>';
            $counts = $this->pictureCounts;
            ksort($counts);
            foreach ($counts as $column => $count) {
                $index = $column + 1;
                $cols .= '<col min="'.$index.'" max="'.$index.'" width="'.$this->imageColumnWidth($count).'" customWidth="true"/>';
            }
            $cols .= '</cols>';
            $sheet = preg_replace('/<sheetData>/', $cols.'<sheetData>', $sheet, 1) ?? $sheet;
        }

        $zip->addFromString($sheetName, $sheet);
        $this->patchRelationships($zip);
        $this->patchContentTypes($zip);
    }

    private function patchRelationships(ZipArchive $zip): void
    {
        $name = 'xl/worksheets/_rels/sheet1.xml.rels';
        $rels = $zip->getFromName($name);
        if (! is_string($rels)) {
            return;
        }

        $extra = '';
        if ($this->pictures !== []) {
            $extra .= '<Relationship Id="rIdDrawing1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/drawing" Target="../drawings/drawing1.xml"/>';
        }
        foreach ($this->hyperlinks as $i => $link) {
            $extra .= '<Relationship Id="rIdLink'.($i + 1).'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/hyperlink" Target="'.$this->xml($link['url']).'" TargetMode="External"/>';
        }
        if ($extra === '') {
            return;
        }

        $rels = str_replace('</Relationships>', $extra.'</Relationships>', $rels);
        $zip->addFromString($name, $rels);
    }

    private function patchContentTypes(ZipArchive $zip): void
    {
        if ($this->pictures === []) {
            return;
        }

        $name = '[Content_Types].xml';
        $types = $zip->getFromName($name);
        if (! is_string($types)) {
            return;
        }

        $extra = '';
        if (! str_contains($types, 'Extension="jpeg"')) {
            $extra .= '<Default Extension="jpeg" ContentType="image/jpeg"/>';
        }
        $extra .= '<Override PartName="/xl/drawings/drawing1.xml" ContentType="application/vnd.openxmlformats-officedocument.drawing+xml"/>';
        $types = str_replace('</Types>', $extra.'</Types>', $types);
        $zip->addFromString($name, $types);
    }

    private function xml(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    private function reset(): void
    {
        $label = translate('Link');
        $this->linkLabel = is_string($label) && $label !== '' ? $label : 'Link';
        $this->imageCache = [];
        $this->embedded = 0;
        $this->pictures = [];
        $this->hyperlinks = [];
        $this->pictureCounts = [];
        $this->headerStyle = (new Style())->setFontBold();
        $this->linkStyle = (new Style())->setFontColor(Color::BLUE)->setFontUnderline();
        $this->linkWrapStyle = (new Style())->setFontColor(Color::BLUE)->setFontUnderline()->setShouldWrapText();
        $this->wrapStyle = (new Style())->setShouldWrapText();
        $this->imageRowStyle = (new Style())->setCellVerticalAlignment(CellVerticalAlignment::TOP);
    }
}
