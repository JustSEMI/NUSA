<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Log;
use ZipArchive;

class DocumentParserService
{
    /**
     * Maximum characters to extract per document to avoid overwhelming LLM token limit.
     */
    protected int $maxExtractLength = 50000;

    /**
     * Extract readable text from an uploaded document.
     */
    public function extractText(string $filePath, string $extension, string $mimeType): ?string
    {
        $ext = strtolower($extension);

        try {
            switch ($ext) {
                case 'pdf':
                    return $this->extractFromPdf($filePath);

                case 'docx':
                    return $this->extractFromDocx($filePath);

                case 'doc':
                    return $this->extractFromDoc($filePath);

                case 'xlsx':
                    return $this->extractFromXlsx($filePath);

                case 'csv':
                case 'tsv':
                    return $this->extractFromCsv($filePath);

                case 'json':
                    return $this->extractFromJson($filePath);

                case 'txt':
                case 'md':
                case 'markdown':
                case 'log':
                case 'html':
                case 'xml':
                case 'yaml':
                case 'yml':
                case 'sql':
                case 'php':
                case 'js':
                case 'jsx':
                case 'ts':
                case 'tsx':
                case 'vue':
                case 'py':
                case 'java':
                case 'c':
                case 'cpp':
                case 'h':
                case 'cs':
                case 'go':
                case 'rs':
                case 'rb':
                case 'sh':
                case 'bash':
                case 'ini':
                case 'conf':
                case 'env':
                    return $this->extractFromPlainText($filePath);

                default:
                    // If MIME type indicates text, attempt plain text extraction
                    if (str_starts_with($mimeType, 'text/')) {
                        return $this->extractFromPlainText($filePath);
                    }
                    return null;
            }
        } catch (Exception $e) {
            Log::warning('Document extraction failed', [
                'file' => $filePath,
                'extension' => $extension,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Extract text from PDF using Smalot PDF Parser with fallback.
     */
    protected function extractFromPdf(string $filePath): string
    {
        if (class_exists(\Smalot\PdfParser\Parser::class)) {
            try {
                $parser = new \Smalot\PdfParser\Parser();
                $pdf = $parser->parseFile($filePath);
                $text = $pdf->getText();

                if (!empty(trim($text))) {
                    return $this->truncateText($this->cleanUtf8($text));
                }
            } catch (Exception $e) {
                Log::info('Smalot parser failed, trying fallback stream extractor: ' . $e->getMessage());
            }
        }

        // Fallback plain text stream search for PDF
        return $this->extractPdfFallback($filePath);
    }

    /**
     * Fallback PDF stream text extractor.
     */
    protected function extractPdfFallback(string $filePath): string
    {
        $content = file_get_contents($filePath);
        if ($content === false) {
            return '';
        }

        // Search for stream blocks and text operators
        $text = '';
        if (preg_match_all('/stream\r?\n(.*?)\r?\nendstream/s', $content, $matches)) {
            foreach ($matches[1] as $stream) {
                $uncompressed = @gzuncompress($stream);
                if ($uncompressed !== false) {
                    if (preg_match_all('/\((.*?)\)\s*T[jJ]/s', $uncompressed, $textMatches)) {
                        $text .= implode(' ', $textMatches[1]) . "\n";
                    } elseif (preg_match_all('/\[(.*?)\]\s*TJ/s', $uncompressed, $textMatches)) {
                        foreach ($textMatches[1] as $tm) {
                            if (preg_match_all('/\((.*?)\)/', $tm, $subMatches)) {
                                $text .= implode('', $subMatches[1]);
                            }
                        }
                        $text .= "\n";
                    }
                }
            }
        }

        return $this->truncateText($this->cleanUtf8($text));
    }

    /**
     * Extract text from DOCX (Office Open XML).
     */
    protected function extractFromDocx(string $filePath): string
    {
        $zip = new ZipArchive();
        if ($zip->open($filePath) !== true) {
            return '';
        }

        $contentXml = $zip->getFromName('word/document.xml');
        $zip->close();

        if (!$contentXml) {
            return '';
        }

        // Replace paragraph and break tags with newlines
        $contentXml = str_replace(['</w:p>', '<w:br/>', '<w:br></w:br>', '<w:cr/>'], "\n", $contentXml);
        $contentXml = str_replace(['<w:tab/>', '<w:tab></w:tab>'], "\t", $contentXml);

        $text = strip_tags($contentXml);
        // Normalize multiple empty lines
        $text = preg_replace('/\n{3,}/', "\n\n", $text);

        return $this->truncateText($this->cleanUtf8($text));
    }

    /**
     * Extract text from legacy DOC binary file.
     */
    protected function extractFromDoc(string $filePath): string
    {
        $content = file_get_contents($filePath);
        if ($content === false) {
            return '';
        }

        // Strip non-printable ASCII
        $text = preg_replace('/[^\x20-\x7E\t\r\n]/', ' ', $content);
        $text = preg_replace('/\s{2,}/', ' ', $text);

        return $this->truncateText($this->cleanUtf8($text));
    }

    /**
     * Extract text/table data from XLSX.
     */
    protected function extractFromXlsx(string $filePath): string
    {
        $zip = new ZipArchive();
        if ($zip->open($filePath) !== true) {
            return '';
        }

        // Load shared strings
        $sharedStrings = [];
        $sharedStringsXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($sharedStringsXml) {
            $xml = @simplexml_load_string($sharedStringsXml);
            if ($xml) {
                foreach ($xml->si as $si) {
                    $sharedStrings[] = (string) ($si->t ?? $si->r->t ?? '');
                }
            }
        }

        // Read sheet1.xml
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        if (!$sheetXml) {
            return '';
        }

        $xml = @simplexml_load_string($sheetXml);
        if (!$xml || !isset($xml->sheetData->row)) {
            return '';
        }

        $rows = [];
        foreach ($xml->sheetData->row as $row) {
            $rowCells = [];
            foreach ($row->c as $c) {
                $val = (string) $c->v;
                $type = (string) $c['t'];

                if ($type === 's' && isset($sharedStrings[(int) $val])) {
                    $val = $sharedStrings[(int) $val];
                }
                $rowCells[] = trim($val);
            }
            if (!empty(array_filter($rowCells))) {
                $rows[] = implode("\t| ", $rowCells);
            }
        }

        $text = implode("\n", $rows);
        return $this->truncateText($this->cleanUtf8($text));
    }

    /**
     * Extract text from CSV / TSV file.
     */
    protected function extractFromCsv(string $filePath): string
    {
        $content = file_get_contents($filePath);
        if ($content === false) {
            return '';
        }

        return $this->truncateText($this->cleanUtf8($content));
    }

    /**
     * Extract text from JSON file with pretty print if possible.
     */
    protected function extractFromJson(string $filePath): string
    {
        $content = file_get_contents($filePath);
        if ($content === false) {
            return '';
        }

        $decoded = json_decode($content, true);
        if ($decoded !== null) {
            $content = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        return $this->truncateText($this->cleanUtf8($content));
    }

    /**
     * Extract text from standard plain text / code files.
     */
    protected function extractFromPlainText(string $filePath): string
    {
        $content = file_get_contents($filePath);
        if ($content === false) {
            return '';
        }

        return $this->truncateText($this->cleanUtf8($content));
    }

    /**
     * Ensure UTF-8 cleanliness.
     */
    protected function cleanUtf8(string $text): string
    {
        if (!mb_check_encoding($text, 'UTF-8')) {
            $encoding = mb_detect_encoding($text, ['UTF-8', 'ISO-8859-1', 'Windows-1252', 'ASCII'], true);
            if ($encoding) {
                $text = mb_convert_encoding($text, 'UTF-8', $encoding);
            } else {
                $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');
            }
        }

        return trim($text);
    }

    /**
     * Truncate text if it exceeds maximum limit, adding truncation notice.
     */
    protected function truncateText(string $text): string
    {
        if (mb_strlen($text) > $this->maxExtractLength) {
            $truncated = mb_substr($text, 0, $this->maxExtractLength);
            return $truncated . "\n\n[... Teks dokumen dipotong karena melebihi batas maksimum 50.000 karakter ...]";
        }

        return $text;
    }
}
