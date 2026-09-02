<?php

namespace App\Tests\Unit;

use App\Entity\SiteSettings;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

final class SiteSettingsExitPasswordTest extends TestCase
{
    public function testExitPasswordCanBeConfiguredAndRead(): void
    {
        $settings = new SiteSettings();

        self::assertFalse($settings->isExitPasswordConfigured());

        $settings->setExitPassword('2468');

        self::assertTrue($settings->isExitPasswordConfigured());
        self::assertSame('2468', $settings->getExitPassword());
    }

    public function testBlankAdminFieldDoesNotRequestPasswordReplacement(): void
    {
        $settings = new SiteSettings();
        $settings->setPlainExitPassword('');

        self::assertNull($settings->getPlainExitPassword());
    }

    public function testNewExitPasswordMustContainAtLeastFourCharacters(): void
    {
        $settings = new SiteSettings();
        $settings->setPlainExitPassword('123');

        $violations = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator()
            ->validate($settings);

        self::assertCount(1, $violations);
    }
}
