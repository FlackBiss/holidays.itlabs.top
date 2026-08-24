<?php

namespace App\Controller;

use App\ApiResource\NavigationRequest;
use App\Entity\MapNode;
use App\Entity\MapPlace;
use App\Service\NavigationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

final readonly class LegacyNavigationController
{
    public function __construct(private NavigationService $navigation, private EntityManagerInterface $em) {}

    #[Route('/api/nodes/navigate', name: 'api_legacy_nodes_navigate', methods: ['GET'], priority: 200)]
    public function navigate(Request $request): JsonResponse
    {
        $from = $request->query->getInt('from');
        $to = $request->query->getInt('to');
        if ($from < 1 || $to < 1) throw new BadRequestHttpException('from и to обязательны.');
        return new JsonResponse($this->navigation->buildNodePath($from, $to, $request->query->getInt('routeType') === 2));
    }

    #[Route('/api/nodes/qr-code', name: 'api_legacy_nodes_qr', methods: ['GET'], priority: 200)]
    public function qr(Request $request): JsonResponse
    {
        $from = $request->query->getInt('from');
        $to = $request->query->getInt('to');
        if ($from < 1 || $to < 1) throw new BadRequestHttpException('from и to обязательны.');
        $targetNode = $this->em->getRepository(MapNode::class)->find($to);
        $place = $targetNode instanceof MapNode ? $this->em->getRepository(MapPlace::class)->findOneBy(['node' => $targetNode, 'active' => true]) : null;
        if (!$place instanceof MapPlace) throw new NotFoundHttpException('Для конечной точки не найден объект карты.');
        $input = new NavigationRequest();
        $input->fromNodeId = $from;
        $input->destinationPlaceId = $place->getId();
        $input->accessible = $request->query->getInt('routeType') === 2;
        $route = $this->navigation->buildRoute($input);
        return new JsonResponse(['success' => true, 'file' => $route->mobileUrl, 'mobileUrl' => $route->mobileUrl, 'qr_code' => $route->qrCodeUrl, 'points' => $route->points]);
    }
}
