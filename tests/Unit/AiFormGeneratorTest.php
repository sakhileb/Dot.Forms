<?php

namespace Tests\Unit;

use App\Services\Ai\AiFormGenerator;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiFormGeneratorTest extends TestCase
{
    public function test_ai_form_generator_parses_openai_response(): void
    {
        config()->set('services.openai.key', 'test-key');

        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode([
                                'title' => 'Event Registration',
                                'description' => 'Register attendees for the event.',
                                'fields' => [
                                    [
                                        'type' => 'text',
                                        'label' => 'Full Name',
                                        'placeholder' => 'Jane Doe',
                                        'required' => true,
                                        'options' => [],
                                        'helper_text' => null,
                                    ],
                                ],
                            ]),
                        ],
                    ],
                ],
            ], 200),
        ]);

        $service = new AiFormGenerator;
        $result = $service->generate('Event registration form');

        $this->assertSame('Event Registration', $result['title']);
        $this->assertCount(1, $result['fields']);
        $this->assertSame('Full Name', $result['fields'][0]['label']);
        $this->assertTrue($result['fields'][0]['required']);
    }
}
