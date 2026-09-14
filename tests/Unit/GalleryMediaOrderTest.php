<?php

namespace App\Tests\Unit;

use App\Controller\Admin\GalleryMediaCrudController;
use App\Entity\GalleryMedia;
use Doctrine\ORM\{EntityManagerInterface, EntityRepository};
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGeneratorInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\{Request, RequestStack};
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

final class GalleryMediaOrderTest extends TestCase
{
    public static function orders(): iterable
    {
        yield 'reverse' => [['2', '1'], true, true];
        yield 'duplicate' => [['1', '1'], true, false];
        yield 'missing' => [['1'], true, false];
        yield 'unknown' => [['1', '3'], true, false];
        yield 'csrf' => [['2', '1'], false, false];
    }

    #[DataProvider('orders')]
    public function testSave(array $order, bool $validToken, bool $save): void
    {
        $items = [new GalleryMedia(), new GalleryMedia()];
        foreach ($items as $i => $item) {
            (new \ReflectionProperty(GalleryMedia::class, 'id'))->setValue($item, $i + 1);
            $item->priority = 10 + $i;
        }
        $repository = $this->createMock(EntityRepository::class);
        $repository->method('findBy')->with([], ['priority' => 'ASC', 'id' => 'ASC'])->willReturn($items);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->with(GalleryMedia::class)->willReturn($repository);
        $em->expects($save ? self::once() : self::never())->method('flush');
        $urls = $this->createMock(AdminUrlGeneratorInterface::class);
        foreach (['unsetAll', 'setController', 'setAction'] as $method) $urls->method($method)->willReturnSelf();
        $urls->method('generateUrl')->willReturn('/admin/gallery-media');
        $csrf = $this->createMock(CsrfTokenManagerInterface::class);
        $csrf->method('isTokenValid')->willReturn($validToken);
        $request = Request::create('/admin/gallery-media/reorder', 'POST', ['_token' => 'test', 'order' => $order]);
        $request->setSession(new Session(new MockArraySessionStorage()));
        $stack = new RequestStack();
        $stack->push($request);
        $container = new Container();
        $container->set('request_stack', $stack);
        $container->set('security.csrf.token_manager', $csrf);
        $controller = new GalleryMediaCrudController();
        $controller->setContainer($container);
        if (!$validToken) $this->expectException(AccessDeniedException::class);
        $response = $controller->reorder($request, $em, $urls);
        self::assertSame(302, $response->getStatusCode());
        self::assertSame($save ? [2, 1] : [10, 11], array_map(static fn ($item) => $item->priority, $items));
    }
}
