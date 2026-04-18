<?php

namespace App\Traits;

trait ActivationClass
{
    public function getDomain(): string
    {
        return str_replace(['http://', 'https://', 'www.'], '', url('/'));
    }

    public function getAddonsConfig(): array
    {
        if (file_exists(base_path('config/system-addons.php'))) {
            return include base_path('config/system-addons.php');
        }

        $apps = ['admin_panel', 'provider_app', 'serviceman_app'];
        $appConfig = [];
        foreach ($apps as $app) {
            $appConfig[$app] = [
                'active' => '1',
                'name' => '',
                'identifier' => '',
                'username' => '',
                'purchase_key' => '',
                'software_id' => '',
                'domain' => '',
                'software_type' => $app == 'admin_panel' ? 'product' : 'addon',
            ];
        }

        return $appConfig;
    }

    /**
     * Build activation payload without remote license verification.
     */
    public function getRequestConfig(
        ?string $username = null,
        ?string $purchaseKey = null,
        ?string $softwareId = null,
        ?string $softwareType = null,
        ?string $name = null,
        ?string $identifier = null
    ): array {
        return [
            'active' => true,
            'name' => $name,
            'identifier' => $identifier,
            'username' => trim((string) $username),
            'purchase_key' => $purchaseKey,
            'software_id' => $softwareId ?? (defined('SOFTWARE_ID') ? SOFTWARE_ID : ''),
            'domain' => $this->getDomain(),
            'software_type' => $softwareType,
            'errors' => [],
        ];
    }

    public function updateActivationConfig($app, array $response): void
    {
        $config = $this->getAddonsConfig();
        $config[$app] = $response;
        $configContents = '<?php return '.var_export($config, true).';';
        file_put_contents(base_path('config/system-addons.php'), $configContents);
    }
}
