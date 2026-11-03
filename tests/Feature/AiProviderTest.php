<?php

namespace Tests\Feature;

use App\Libs\Ai\AiManager;
use App\Libs\Ai\Exceptions\AiException;
use App\Libs\Ai\Providers\ClaudeProvider;
use App\Libs\Ai\Providers\KimiProvider;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiProviderTest extends TestCase
{
    public function test_claude_extracts_validated_structured_data_through_messages_tools(): void
    {
        Http::fake([
            'https://api.anthropic.com/v1/messages' => Http::response([
                'type' => 'message',
                'stop_reason' => 'tool_use',
                'content' => [['type' => 'tool_use', 'id' => 'toolu_1', 'name' => 'extraction', 'input' => ['title' => 'Claude', 'count' => 2]]],
            ]),
        ]);
        $provider = new ClaudeProvider($this->configuration('claude'), $this->app->make(Factory::class));

        $result = $provider->extract('Extract the document.', ['text' => 'Source text'], $this->schema());

        $this->assertSame(['title' => 'Claude', 'count' => 2], $result);
        Http::assertSent(function (Request $request): bool {
            return $request->url() === 'https://api.anthropic.com/v1/messages'
                && $request->hasHeader('x-api-key', 'claude-key')
                && $request->hasHeader('anthropic-version', '2023-06-01')
                && $request['model'] === 'claude-sonnet-4-20250514'
                && $request['tools'][0]['name'] === 'extraction'
                && $request['tools'][0]['input_schema'] === $this->schema()
                && $request['tool_choice'] === ['type' => 'tool', 'name' => 'extraction'];
        });
    }

    public function test_claude_sends_base64_and_remote_images_for_analysis(): void
    {
        Http::fake([
            'https://api.anthropic.com/v1/messages' => Http::response([
                'type' => 'message',
                'stop_reason' => 'tool_use',
                'content' => [['type' => 'tool_use', 'id' => 'toolu_2', 'name' => 'images', 'input' => ['title' => 'Images', 'count' => 2]]],
            ]),
        ]);
        $provider = new ClaudeProvider($this->configuration('claude'), $this->app->make(Factory::class));

        $result = $provider->analyzeImages('Describe the images.', [
            'data:image/png;base64,'.base64_encode('image-content'),
            'https://images.example.com/example.jpg',
        ], $this->schema(), 'images');

        $this->assertSame('Images', $result['title']);
        Http::assertSent(function (Request $request): bool {
            $content = $request['messages'][0]['content'];

            return $content[1]['source'] === ['type' => 'base64', 'media_type' => 'image/png', 'data' => base64_encode('image-content')]
                && $content[2]['source'] === ['type' => 'url', 'url' => 'https://images.example.com/example.jpg'];
        });
    }

    public function test_kimi_extracts_validated_structured_data_through_chat_completion_tools(): void
    {
        Http::fake([
            'https://api.moonshot.cn/v1/chat/completions' => Http::response([
                'choices' => [[
                    'finish_reason' => 'tool_calls',
                    'message' => ['tool_calls' => [[
                        'id' => 'call_1',
                        'type' => 'function',
                        'function' => ['name' => 'extraction', 'arguments' => json_encode(['title' => 'Kimi', 'count' => 3])],
                    ]]],
                ]],
            ]),
        ]);
        $provider = new KimiProvider($this->configuration('kimi'), $this->app->make(Factory::class));

        $result = $provider->extract('Extract the document.', ['text' => 'Source text'], $this->schema());

        $this->assertSame(['title' => 'Kimi', 'count' => 3], $result);
        Http::assertSent(function (Request $request): bool {
            return $request->url() === 'https://api.moonshot.cn/v1/chat/completions'
                && $request->hasHeader('Authorization', 'Bearer kimi-key')
                && $request['model'] === 'kimi-k2-0905-preview'
                && $request['tools'][0]['function']['parameters'] === $this->schema()
                && $request['tool_choice']['function']['name'] === 'extraction';
        });
    }

    public function test_kimi_rejects_tool_arguments_that_do_not_match_the_schema(): void
    {
        Http::fake([
            'https://api.moonshot.cn/v1/chat/completions' => Http::response([
                'choices' => [[
                    'finish_reason' => 'tool_calls',
                    'message' => ['tool_calls' => [[
                        'type' => 'function',
                        'function' => ['name' => 'extraction', 'arguments' => json_encode(['title' => 'Kimi'])],
                    ]]],
                ]],
            ]),
        ]);
        $provider = new KimiProvider($this->configuration('kimi'), $this->app->make(Factory::class));

        try {
            $provider->extract('Extract the document.', ['text' => 'Source text'], $this->schema());
            $this->fail('An invalid structured response was accepted.');
        } catch (AiException $exception) {
            $this->assertSame('invalid_response', $exception->errorCode);
        }
    }

    public function test_manager_routes_to_the_selected_provider_and_reports_its_capabilities(): void
    {
        $manager = (new AiManager(['enabled' => true, 'default' => 'claude']))
            ->register('claude', new ClaudeProvider($this->configuration('claude'), $this->app->make(Factory::class)))
            ->register('kimi', new KimiProvider($this->configuration('kimi'), $this->app->make(Factory::class)));

        $this->assertInstanceOf(ClaudeProvider::class, $manager->provider());
        $this->assertSame([
            'provider' => 'claude',
            'enabled' => true,
            'configured' => true,
            'extraction' => true,
            'image_analysis' => true,
            'moderation' => false,
            'image_generation' => false,
        ], $manager->capabilities());
    }

    /** @return array<string, mixed> */
    private function configuration(string $provider): array
    {
        return [
            'api_key' => $provider.'-key',
            'base_url' => $provider === 'claude' ? 'https://api.anthropic.com/v1' : 'https://api.moonshot.cn/v1',
            'version' => '2023-06-01',
            'text_model' => $provider === 'claude' ? 'claude-sonnet-4-20250514' : 'kimi-k2-0905-preview',
            'max_output_tokens' => 1024,
        ];
    }

    /** @return array<string, mixed> */
    private function schema(): array
    {
        return [
            'type' => 'object',
            'properties' => ['title' => ['type' => 'string'], 'count' => ['type' => 'integer']],
            'required' => ['title', 'count'],
            'additionalProperties' => false,
        ];
    }
}
