<?php

namespace Database\Seeders;

use App\Models\AIStatus;
use App\Models\ChatMessage;
use App\Models\ChatSession;
use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Seed Accounts
        $admin = User::updateOrCreate(
            ['email' => 'admin@nusa.ai'],
            [
                'name' => 'NUSA Admin',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ]
        );

        $testUser = User::updateOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'User Demo',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ]
        );

        // 2. Seed AI Model Statuses
        $models = [
            [
                'model_name' => 'qwen-3.8-flash',
                'is_online' => true,
                'response_time_ms' => 75,
                'last_check_at' => now(),
                'last_error' => null,
            ],
            [
                'model_name' => 'qwen-3.8-max',
                'is_online' => true,
                'response_time_ms' => 95,
                'last_check_at' => now(),
                'last_error' => null,
            ],
            [
                'model_name' => 'qwen-3.5-flash',
                'is_online' => true,
                'response_time_ms' => 92,
                'last_check_at' => now(),
                'last_error' => null,
            ],
            [
                'model_name' => 'qwen-3.5-plus',
                'is_online' => true,
                'response_time_ms' => 125,
                'last_check_at' => now(),
                'last_error' => null,
            ],
        ];

        foreach ($models as $m) {
            AIStatus::updateOrCreate(
                ['model_name' => $m['model_name']],
                $m
            );
        }

        // 3. Seed Sample Chat Sessions & Messages for Admin & Demo User
        foreach ([$admin, $testUser] as $user) {
            // Check if user already has sessions to avoid duplicates
            if ($user->chatSessions()->count() === 0) {
                // Session 1: Arsitektur Laravel & AI
                $session1 = ChatSession::create([
                    'user_id' => $user->id,
                    'title' => 'Arsitektur Web Laravel & AI Assistant',
                    'model_used' => 'qwen-3.5-flash',
                    'system_prompt' => 'Kamu adalah asisten AI profesional bernama NUSA.',
                    'temperature' => 0.7,
                    'is_pinned' => true,
                    'is_archived' => false,
                ]);

                ChatMessage::create([
                    'chat_session_id' => $session1->id,
                    'role' => 'user',
                    'content' => 'Bagaimana cara membangun arsitektur chatbot AI yang responsif dengan Laravel dan Tailwind CSS?',
                    'model_used' => 'qwen-3.5-flash',
                    'tokens_used' => 24,
                    'is_edited' => false,
                    'created_at' => now()->subMinutes(30),
                ]);

                ChatMessage::create([
                    'chat_session_id' => $session1->id,
                    'role' => 'assistant',
                    'content' => "Untuk membangun arsitektur chatbot AI yang modern dan responsif dengan **Laravel** dan **Tailwind CSS**, berikut adalah arsitektur terbaik yang direkomendasikan:\n\n### 1. Pola Service Layer & Repository\nPisahkan logika panggilan AI API ke dalam service khusus:\n\n```php\nclass AiApiService {\n    public function sendMessage(array \$messages, string \$model): array {\n        // Panggilan HTTP API ke LLM Provider\n        return Http::withHeaders([\n            'Authorization' => 'Bearer ' . config('services.ai.api_key'),\n        ])->post(\$baseUrl . '/v1/messages', [\n            'model' => \$model,\n            'messages' => \$messages,\n        ])->json();\n    }\n}\n```\n\n### 2. Fitur Utama NUSA\n- **Live Streaming / Real-time Response**: Memberikan feedback ketikan instan ke pengguna.\n- **Syntax Highlighting**: Kode program diformat rapi dengan Prism.js.\n- **Dark & Light Mode**: Tampilan fleksibel dengan Tailwind CSS.\n\nAda yang ingin didiskusikan lebih lanjut?",
                    'model_used' => 'qwen-3.5-flash',
                    'tokens_used' => 180,
                    'is_edited' => false,
                    'created_at' => now()->subMinutes(29),
                ]);

                // Session 2: Ide Konten Nusantara
                $session2 = ChatSession::create([
                    'user_id' => $user->id,
                    'title' => 'Ide Konten Kuliner Nusantara',
                    'model_used' => 'gemini-3.7-flash',
                    'system_prompt' => 'Bantu ide kreatif marketing.',
                    'temperature' => 0.8,
                    'is_pinned' => false,
                    'is_archived' => false,
                ]);

                ChatMessage::create([
                    'chat_session_id' => $session2->id,
                    'role' => 'user',
                    'content' => 'Buatkan 3 ide caption Instagram untuk mempromosikan masakan rendang autentik Padang.',
                    'model_used' => 'gemini-3.7-flash',
                    'tokens_used' => 20,
                    'is_edited' => false,
                    'created_at' => now()->subHours(2),
                ]);

                ChatMessage::create([
                    'chat_session_id' => $session2->id,
                    'role' => 'assistant',
                    'content' => "Tentu! Berikut 3 variasi caption Instagram yang menarik:\n\n1. **Gaya Storytelling & Autentisitas**\n> *\"Dimasak perlahan selama 8 jam dengan rempah pilihan Minang... Rasa gurih yang meresap hingga serat daging terdalam. Sekali suap, rindu kampung halaman langsung terobati. ✨🍲\"*\n\n2. **Gaya Promo & Call-To-Action**\n> *\"Siap manjain lidah kamu hari ini? Rendang daging empuk dengan bumbu pekat siap dikirim ke meja makanmu! Order sekarang via link di bio! 🔥📦\"*\n\n3. **Gaya Edukasi & Fun Fact**\n> *\"Tahukah kamu? Rendang dinobatkan sebagai salah satu makanan terlezat di dunia! Cobain rendang resep warisan kami hari ini yuk! 🏆🇮🇩\"*",
                    'model_used' => 'gemini-3.7-flash',
                    'tokens_used' => 140,
                    'is_edited' => false,
                    'created_at' => now()->subHours(2)->addMinutes(1),
                ]);
            }

            // 4. Seed User Preferences
            UserPreference::set($user->id, 'dark_mode', 'true');
            UserPreference::set($user->id, 'auto_save', 'true');
            UserPreference::set($user->id, 'streaming', 'true');
            UserPreference::set($user->id, 'default_model', 'qwen-3.5-flash');
        }
    }
}

