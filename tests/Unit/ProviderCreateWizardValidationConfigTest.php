<?php

namespace Tests\Unit;

use Tests\TestCase;

/**
 * Admin provider create wizard intentionally skips client-side jQuery Validate;
 * edit wizard keeps a function-based ignore filter.
 */
class ProviderCreateWizardValidationConfigTest extends TestCase
{
    public function test_create_blade_has_no_jquery_validate_wizard(): void
    {
        $path = base_path('Modules/ProviderManagement/Resources/views/admin/provider/create.blade.php');
        $this->assertFileExists($path);
        $src = (string) file_get_contents($path);

        $this->assertStringNotContainsString('jquery-validation/jquery.validate.min.js', $src);
        $this->assertStringNotContainsString('formWizard.validate(', $src);
        $this->assertStringNotContainsString('providerCreateJqvIgnoreFilter', $src);
        $this->assertStringContainsString('window.refreshProviderCreateStep0ValidationSummary', $src);
    }

    public function test_edit_blade_matches_create_ignore_pattern(): void
    {
        $path = base_path('Modules/ProviderManagement/Resources/views/admin/provider/edit.blade.php');
        $this->assertFileExists($path);
        $src = (string) file_get_contents($path);

        $this->assertStringContainsString('function providerEditJqvIgnoreFilter', $src);
        $this->assertStringContainsString('ignore: providerEditJqvIgnoreFilter', $src);
    }
}
