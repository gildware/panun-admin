<?php

namespace Tests\Unit;

use Modules\ProviderManagement\Support\ProviderPhoneUpdateNormalizer;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ProviderPhoneUpdateNormalizerTest extends TestCase
{
    public static function alignmentCases(): array
    {
        return [
            'company stale company hidden contact updated' => [
                'company',
                '+919090909090',
                '+919090909090',
                '+919090909090',
                '+919353294014',
                '+919353294014',
            ],
            'company both updated independently' => [
                'company',
                '+919090909090',
                '+919090909090',
                '+911111111111',
                '+919353294014',
                '+911111111111',
            ],
            'company numbers already differ' => [
                'company',
                '+911111111111',
                '+919090909090',
                '+911111111111',
                '+919353294014',
                '+911111111111',
            ],
            'individual ignored' => [
                'individual',
                '+919090909090',
                '+919090909090',
                '+919090909090',
                '+919353294014',
                '+919090909090',
            ],
        ];
    }

    #[DataProvider('alignmentCases')]
    public function test_align_company_phone_with_contact_when_paired(
        string $type,
        string $oldCompany,
        string $oldContact,
        string $newCompany,
        string $newContact,
        string $expected
    ): void {
        $this->assertSame(
            $expected,
            ProviderPhoneUpdateNormalizer::alignCompanyPhoneWithContactIfPreviouslyPaired(
                $type,
                $oldCompany,
                $oldContact,
                $newCompany,
                $newContact
            )
        );
    }
}
