<?php

namespace App\Tests\Unit;

use App\Controller\ExitPasswordController;
use App\Entity\SiteSettings;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class ExitPasswordControllerTest extends TestCase
{
    public function testReturnsConfiguredExitPassword(): void
    {
        $settings = new SiteSettings();
        $settings->setExitPassword('2468');

        $repository = $this->createMock(EntityRepository::class);
        $repository->method('findOneBy')->willReturn($settings);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->with(SiteSettings::class)->willReturn($repository);

        $controller = new ExitPasswordController($em);

        $response = $controller();

        self::assertSame(['exitPassword' => '2468'], json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }
}
