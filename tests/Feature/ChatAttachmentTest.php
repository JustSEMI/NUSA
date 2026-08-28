<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\ChatSession;
use App\Models\ChatMessage;
use App\Services\DocumentParserService;
use App\Services\FileAttachmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ChatAttachmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_document_parser_extracts_plain_text_and_json(): void
    {
        $parser = new DocumentParserService();

        // Test plain text
        $tempTxt = tempnam(sys_get_temp_dir(), 'test_') . '.txt';
        file_put_contents($tempTxt, "Halo dunia! Ini adalah dokumen teks.");
        $extractedTxt = $parser->extractText($tempTxt, 'txt', 'text/plain');
        $this->assertEquals("Halo dunia! Ini adalah dokumen teks.", $extractedTxt);
        @unlink($tempTxt);

        // Test JSON
        $tempJson = tempnam(sys_get_temp_dir(), 'test_') . '.json';
        file_put_contents($tempJson, json_encode(['nama' => 'NUSA', 'versi' => '2.0']));
        $extractedJson = $parser->extractText($tempJson, 'json', 'application/json');
        $this->assertStringContainsString('NUSA', $extractedJson);
        $this->assertStringContainsString('2.0', $extractedJson);
        @unlink($tempJson);
    }

    public function test_file_attachment_service_processes_uploaded_file(): void
    {
        $parser = new DocumentParserService();
        $service = new FileAttachmentService($parser);

        $file = UploadedFile::fake()->create('laporan.txt', 10, 'text/plain');
        file_put_contents($file->getPathname(), "Isi laporan keuangan tahunan.");

        $attachment = $service->processUploadedFile($file, 1);

        $this->assertEquals('laporan.txt', $attachment['name']);
        $this->assertFalse($attachment['is_image']);
        $this->assertEquals('Isi laporan keuangan tahunan.', $attachment['extracted_text']);
        $this->assertTrue(Storage::disk('public')->exists($attachment['path']));
    }

    public function test_file_attachment_service_processes_base64_pasted_image(): void
    {
        $parser = new DocumentParserService();
        $service = new FileAttachmentService($parser);

        // 1x1 transparent PNG base64
        $base64Png = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

        $attachment = $service->processBase64File($base64Png, 1, 'screenshot.png');

        $this->assertEquals('screenshot.png', $attachment['name']);
        $this->assertTrue($attachment['is_image']);
        $this->assertTrue(Storage::disk('public')->exists($attachment['path']));

        $base64Data = $service->getImageBase64($attachment['path']);
        $this->assertNotNull($base64Data);
        $this->assertEquals('image/png', $base64Data['media_type']);
        $this->assertNotEmpty($base64Data['data']);
    }

    public function test_upload_attachment_api_endpoint(): void
    {
        $user = User::factory()->create();

        $file = UploadedFile::fake()->image('test_screenshot.png', 100, 100);

        $response = $this->actingAs($user)->postJson('/api/chat/upload', [
            'file' => $file,
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'attachments' => [
                '*' => ['id', 'name', 'path', 'url', 'is_image']
            ],
        ]);
    }

    public function test_chat_message_saves_with_attachments(): void
    {
        $user = User::factory()->create();

        $session = ChatSession::create([
            'user_id' => $user->id,
            'title' => 'Test Sesi',
            'model_used' => 'qwen-3.5-flash',
        ]);

        $message = ChatMessage::create([
            'chat_session_id' => $session->id,
            'role' => 'user',
            'content' => 'Jelaskan gambar ini',
            'attachments' => [
                [
                    'id' => 'att_123',
                    'name' => 'gambar.png',
                    'path' => 'attachments/1/gambar.png',
                    'url' => '/storage/attachments/1/gambar.png',
                    'is_image' => true,
                ]
            ],
            'model_used' => 'qwen-3.5-flash',
        ]);

        $this->assertIsArray($message->fresh()->attachments);
        $this->assertEquals('gambar.png', $message->fresh()->attachments[0]['name']);
    }
}
