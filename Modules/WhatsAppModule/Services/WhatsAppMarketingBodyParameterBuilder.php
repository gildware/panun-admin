<?php

namespace Modules\WhatsAppModule\Services;

use Modules\WhatsAppModule\Entities\WhatsAppMarketingTemplate;

class WhatsAppMarketingBodyParameterBuilder
{
    /**
     * @param  array<string, string>  $variableMapping  keys "1","2" => customer_name|provider_name|category_name|static:...
     * @return array<int, string>
     */
    public function build(
        WhatsAppMarketingTemplate $template,
        array $variableMapping,
        string $recipientName,
        string $categoryName
    ): array {
        $count = max(0, (int) $template->body_parameter_count);
        $out = [];
        for ($i = 1; $i <= $count; $i++) {
            $key = (string) $i;
            $source = $variableMapping[$key] ?? '';
            $out[] = $this->resolve((string) $source, $recipientName, $categoryName);
        }

        return $out;
    }

    private function resolve(string $source, string $recipientName, string $categoryName): string
    {
        return match ($source) {
            'customer_name', 'provider_name' => $recipientName !== '' ? $recipientName : '-',
            'category_name' => $categoryName !== '' ? $categoryName : '-',
            default => str_starts_with($source, 'static:')
                ? substr($source, strlen('static:'))
                : '',
        };
    }
}
