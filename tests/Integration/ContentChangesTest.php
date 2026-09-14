<?php

namespace App\Tests\Integration;

use App\Controller\ContentChangesController;
use App\Entity\{ContentRevision, GalleryMedia, KioskTerminal, MapArea, MapPlan, User};
use App\EventListener\ContentRevisionListener;
use Doctrine\Common\EventManager;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\{EntityManager, Events, ORMSetup};
use Doctrine\ORM\Mapping\UnderscoreNamingStrategy;
use Doctrine\ORM\Tools\SchemaTool;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class ContentChangesTest extends TestCase
{
    private EntityManager $em;

    protected function setUp(): void
    {
        if (!extension_loaded('pdo_sqlite')) self::markTestSkipped('pdo_sqlite is required for isolated integration tests.');
        $config = ORMSetup::createAttributeMetadataConfiguration([dirname(__DIR__, 2).'/src/Entity'], true);
        $config->setNamingStrategy(new UnderscoreNamingStrategy(CASE_LOWER));
        $events = new EventManager();
        $this->em = new EntityManager(DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]), $config, $events);
        (new SchemaTool($this->em))->createSchema($this->em->getMetadataFactory()->getAllMetadata());
        $this->em->persist(new ContentRevision());
        $this->em->flush();
        $events->addEventListener([Events::onFlush], new ContentRevisionListener());
    }

    protected function tearDown(): void
    {
        if (isset($this->em)) $this->em->getConnection()->close();
    }

    public function testInsertEditReorderAndDeleteChangeVersion(): void
    {
        $version = $this->version();
        $item = new GalleryMedia();
        $this->em->persist($item);
        $this->em->flush();
        self::assertNotSame($version, $this->version());
        $version = $this->version();
        $item->title = 'Updated';
        $this->em->flush();
        self::assertNotSame($version, $this->version());
        $version = $this->version();
        $item->priority = 5;
        $this->em->flush();
        self::assertNotSame($version, $this->version());
        $version = $this->version();
        $this->em->remove($item);
        $this->em->flush();
        self::assertNotSame($version, $this->version());
    }

    public function testEmptyFlushAndUsersDoNotChangeVersion(): void
    {
        $version = $this->version();
        $this->em->flush();
        self::assertSame($version, $this->version());
        $user = (new User())->setUsername('revision-test')->setPassword('unused-test-hash');
        $this->em->persist($user);
        $this->em->flush();
        self::assertSame($version, $this->version());
        $user->setPassword('changed-test-hash');
        $this->em->flush();
        self::assertSame($version, $this->version());
        $this->em->remove($user);
        $this->em->flush();
        self::assertSame($version, $this->version());
    }

    public function testTerminalHeartbeatIsIgnoredButConfigurationIsTracked(): void
    {
        $before = $this->version();
        $terminal = new KioskTerminal();
        $terminal->code = 'revision-test';
        $this->em->persist($terminal);
        $this->em->flush();
        self::assertNotSame($before, $this->version());
        $version = $this->version();
        $terminal->lastSeenAt = new \DateTimeImmutable();
        $this->em->flush();
        self::assertSame($version, $this->version());
        $terminal->lastSeenAt = new \DateTimeImmutable('+1 second');
        $terminal->name = 'Updated terminal';
        $this->em->flush();
        self::assertNotSame($version, $this->version());
    }

    public function testCollectionDeletionChangesVersion(): void
    {
        $plan = new MapPlan();
        $area = new MapArea();
        $area->plan = $plan;
        $area->replacePoints([['x' => 1, 'y' => 1], ['x' => 2, 'y' => 2]]);
        $this->em->persist($plan);
        $this->em->persist($area);
        $this->em->flush();
        $version = $this->version();
        $area->points->clear();
        $this->em->flush();
        self::assertNotSame($version, $this->version());
        self::assertSame(0, (int) $this->em->getConnection()->fetchOne('SELECT COUNT(*) FROM map_area_point'));
    }

    public function testRollbackAlsoRollsBackRevision(): void
    {
        $version = $this->version();
        $connection = $this->em->getConnection();
        $connection->beginTransaction();
        $this->em->persist(new GalleryMedia());
        $this->em->flush();
        self::assertNotSame($version, $this->version());
        $connection->rollBack();
        $this->em->clear();
        self::assertSame($version, $this->version());
        self::assertSame(0, (int) $connection->fetchOne('SELECT COUNT(*) FROM gallery_media'));
    }

    public function testPollingReturnsInitialUnchangedAndChangedStates(): void
    {
        $controller = new ContentChangesController($this->em->getConnection());
        $initial = $controller(Request::create('/api/changes'));
        $payload = json_decode($initial->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame(200, $initial->getStatusCode());
        self::assertTrue($initial->headers->hasCacheControlDirective('no-store'));
        self::assertTrue($payload['changed']);
        self::assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $payload['version']);
        self::assertStringEndsWith('+00:00', $payload['updatedAt']);
        $request = Request::create('/api/changes', 'GET', ['since' => $payload['version']]);
        self::assertFalse(json_decode($controller($request)->getContent(), true)['changed']);
        $this->em->persist(new GalleryMedia());
        $this->em->flush();
        $changed = json_decode($controller($request)->getContent(), true);
        self::assertTrue($changed['changed']);
        self::assertNotSame($payload['version'], $changed['version']);
    }

    #[DataProvider('invalidVersions')]
    public function testRejectsInvalidVersions(mixed $since): void
    {
        $controller = new ContentChangesController($this->em->getConnection());
        $response = $controller(Request::create('/api/changes', 'GET', ['since' => $since]));
        self::assertSame(400, $response->getStatusCode());
        self::assertTrue($response->headers->hasCacheControlDirective('no-store'));
    }

    public static function invalidVersions(): iterable
    {
        yield 'empty' => [''];
        yield 'arbitrary text' => ['yesterday'];
        yield 'array' => [['version']];
        yield 'too long' => [str_repeat('a', 33)];
    }

    private function version(): string
    {
        return $this->em->getConnection()->fetchOne('SELECT version FROM content_revision WHERE id = 1');
    }
}
