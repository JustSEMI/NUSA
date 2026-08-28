<?php

namespace App\Services;

use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileAttachmentService
{
    protected DocumentParserService $parser;

    /**
     * Allowed extensions.
     */
    protected array $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp'];
    protected array $documentExtensions = [
        'pdf', 'docx', 'doc', 'xlsx', 'xls', 'csv', 'tsv', 'json', 'txt', 'md',
        'markdown', 'log', 'html', 'xml', 'yaml', 'yml', 'sql', 'php', 'js',
        'jsx', 'ts', 'tsx', 'vue', 'py', 'java', 'c', 'cpp', 'h', 'cs', 'go',
        'rs', 'rb', 'sh', 'bash', 'ini', 'conf', 'env'
    ];

    public function __construct(DocumentParserService $parser)
    {
        $this->parser = $parser;
    }

    /**
     * Handle upload from an UploadedFile instance.
     */
    public function processUploadedFile(UploadedFile $file, int $userId): array
    {
        $originalName = $file->getClientOriginalName();
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'bin');
        $mimeType = $file->getMimeType() ?: 'application/octet-stream';
        $size = $file->getSize();

        $subDir = "attachments/{$userId}/" . date('Y/m');
        $filename = time() . '_' . Str::random(8) . '.' . $extension;
        $path = $file->storeAs($subDir, $filename, 'public');

        return $this->buildAttachmentResponse(
            $originalName, $filename, $mimeType, $extension, $size, $path, $userId
        );
    }

    /**
     * Handle upload from a Base64 string (e.g. pasted clipboard image).
     */
    public function processBase64File(string $base64String, int $userId, ?string $customName = null): array
    {
        // Extract mime type and payload: "data:image/png;base64,iVBOR..."
        $mimeType = 'image/png';
        $extension = 'png';

        if (preg_match('/^data:([^;]+);base64,(.*)$/s', $base64String, $matches)) {
            $mimeType = $matches[1];
            $payload = base64_decode($matches[2]);
            $extension = $this->mimeToExtension($mimeType);
        } else {
            $payload = base64_decode($base64String);
        }

        $size = strlen($payload);
        $originalName = $customName ?: 'pasted_image_' . date('Ymd_His') . '.' . $extension;

        $subDir = "attachments/{$userId}/" . date('Y/m');
        $filename = time() . '_' . Str::random(8) . '.' . $extension;
        $path = "{$subDir}/{$filename}";

        Storage::disk('public')->put($path, $payload);

        return $this->buildAttachmentResponse(
            $originalName, $filename, $mimeType, $extension, $size, $path, $userId
        );
    }

    /**
     * Build attachment response array (common logic for file uploads).
     */
    protected function buildAttachmentResponse(
        string $originalName,
        string $filename,
        string $mimeType,
        string $extension,
        int $size,
        string $path,
        int $userId
    ): array {
        $fullPath = Storage::disk('public')->path($path);
        $url = Storage::disk('public')->url($path);

        $isImage = in_array($extension, $this->imageExtensions) || str_starts_with($mimeType, 'image/');

        $extractedText = null;
        if (!$isImage) {
            $extractedText = $this->parser->extractText($fullPath, $extension, $mimeType);
        }

        return [
            'id' => 'att_' . Str::random(16),
            'name' => $originalName,
            'original_name' => $originalName,
            'file_name' => $filename,
            'mime_type' => $mimeType,
            'extension' => $extension,
            'size' => $size,
            'size_formatted' => $this->formatSize($size),
            'path' => $path,
            'url' => $url,
            'is_image' => $isImage,
            'extracted_text' => $extractedText,
        ];
    }

    /**
     * Get base64 representation of a stored image file for AI multimodal request.
     */
    public function getImageBase64(string $path): ?array
    {
        try {
            if (!Storage::disk('public')->exists($path)) {
                return null;
            }

            $content = Storage::disk('public')->get($path);
            $mimeType = Storage::disk('public')->mimeType($path) ?: 'image/jpeg';
            
            // Normalize mime type for Anthropic API: image/jpeg, image/png, image/gif, image/webp
            if ($mimeType === 'image/jpg') {
                $mimeType = 'image/jpeg';
            }

            return [
                'media_type' => $mimeType,
                'data' => base64_encode($content),
            ];
        } catch (Exception $e) {
            Log::warning('Failed to get image base64: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Delete stored attachment file.
     */
    public function deleteAttachmentFile(string $path): bool
    {
        try {
            if (Storage::disk('public')->exists($path)) {
                return Storage::disk('public')->delete($path);
            }
            return true;
        } catch (Exception $e) {
            Log::warning('Failed to delete attachment: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Format bytes into readable format (KB, MB).
     */
    public function formatSize(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 1) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 0) . ' KB';
        }
        return $bytes . ' B';
    }

    /**
     * Convert MIME type to file extension.
     */
    protected function mimeToExtension(string $mime): string
    {
        $map = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'image/svg+xml' => 'svg',
            'application/pdf' => 'pdf',
            'text/plain' => 'txt',
            'application/json' => 'json',
            'text/csv' => 'csv',
        ];

        return $map[$mime] ?? 'bin';
    }
}
