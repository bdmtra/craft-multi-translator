<?php

namespace digitalpulsebe\craftmultitranslator\services;

use craft\helpers\App;
use digitalpulsebe\craftmultitranslator\MultiTranslator;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

class OpenAiService extends ApiService
{
    protected ?Client $_client = null;

    public function getName(): string
    {
        return 'ChatGPT (Open AI)';
    }

    public function isConnected(): bool
    {
        try {
            return $this->getClient()->get('https://api.openai.com/v1/models')->getStatusCode() == 200;
        } catch (\Throwable $exception) {
            return false;
        }
    }

    public function getClient()
    {
        if (!$this->_client) {
            $apiKey = App::parseEnv($this->getProviderSettings()->getOpenAiKey());
            $this->_client = new Client([
                'headers' => [
                    'Authorization' => "Bearer $apiKey",
                    'Content-Type' => "application/json",
                ],
				'http_errors' => true,
                'timeout' => 30
            ]);
        }

        return $this->_client;
    }

    public function translate(string $sourceLocale = null, string $targetLocale = null, string $text = null): ?string
    {
        if (empty($text)) {
            return null;
        }

        $sourceLanguage = $this->getLanguage($sourceLocale);
        $targetLanguage = $this->getLanguage($targetLocale);

        $prompt = ($sourceLanguage) ? "Translate the following text from $sourceLanguage " : 'Translate the following text ';
        $prompt .= "to $targetLanguage.";
        $addToPrompt = $this->getProviderSettings()->getSetting('addToPrompt') ?  $this->getProviderSettings()->getSetting('addToPrompt') : $this->getProviderSettings()->getAddToPrompt();
        if (!empty($addToPrompt)) {
            $prompt .= $addToPrompt.'.';
        } 
        $prompt .= "Keep html, dont add dot at the end if it is not there in original text. Only answer with the translated text. If you can not translate it, just return the text i've provided you(that is important!). Text: " . $text;

        // Log the prompt
        MultiTranslator::log([
            'type' => 'prompt',
            'source_locale' => $sourceLocale,
            'target_locale' => $targetLocale,
            'content' => $prompt
        ]);

        $body = [
            'model' => $this->getProviderSettings()->getOpenAiModel(),
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt,
                ],
            ],
            'temperature' => floatval($this->getProviderSettings()->getOpenAiTemperature()),
        ];

        // Maximum number of retry attempts
        $maxRetries = 3;
        $retryCount = 0;
        $retryDelay = 60; // Initial delay in seconds (1 minute)

        while ($retryCount <= $maxRetries) {
            try {
                $response = $this->getClient()->post('https://api.openai.com/v1/chat/completions', ['json' => $body]);

                if ($response->getStatusCode() < 300) {
                    $contents = $response->getBody()->getContents();
                    $contents = json_decode($contents);

                    foreach ($contents->choices as $choice) {
                        $responseContent = $choice->message->content;
                        
                        // Log the response
                        MultiTranslator::log([
                            'type' => 'response',
                            'source_locale' => $sourceLocale,
                            'target_locale' => $targetLocale,
                            'content' => $responseContent
                        ]);
                        
                        return $responseContent;
                    }
                }
                
                // If we got here without returning, there was an issue but not a 429 error
                // Break out of the retry loop
                break;
                
            } catch (ClientException $e) {
                // Check if it's a rate limit error (429)
                if ($e->getResponse()->getStatusCode() === 429) {
                    $retryCount++;
                    
                    // Log the rate limit error
                    MultiTranslator::log([
                        'type' => 'error',
                        'message' => 'Rate limit reached. Retry attempt: ' . $retryCount . ' of ' . $maxRetries,
                        'source_locale' => $sourceLocale,
                        'target_locale' => $targetLocale
                    ]);
                    
                    if ($retryCount <= $maxRetries) {
                        // Wait before retrying (exponential backoff)
                        $waitTime = $retryDelay * pow(2, $retryCount - 1);
                        sleep($waitTime);
                        continue;
                    }
                }
                
                // For other client errors, log and break out of the retry loop
                MultiTranslator::error([
                    'message' => 'API error: ' . $e->getMessage(),
                    'code' => $e->getCode(),
                    'source_locale' => $sourceLocale,
                    'target_locale' => $targetLocale
                ]);
                
                break;
            } catch (\Exception $e) {
                // For any other exceptions, log and break out of the retry loop
                MultiTranslator::error([
                    'message' => 'Exception: ' . $e->getMessage(),
                    'code' => $e->getCode(),
                    'source_locale' => $sourceLocale,
                    'target_locale' => $targetLocale
                ]);
                
                break;
            }
        }

        return null;
    }

    /**
     * @return string|null full language name for given locale
     */
    public function getLanguage(string $locale = null): ?string
    {
        if (empty($locale)) {
            return null;
        }
        return locale_get_display_language($locale, 'en');
    }
}
