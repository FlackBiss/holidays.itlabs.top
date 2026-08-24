<?php

namespace App\Controller;

use App\Entity\MapPlace;
use App\Entity\SiteSettings;
use Doctrine\ORM\EntityManagerInterface;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Routing\Attribute\Route;

final readonly class NavigationQrController
{
    public function __construct(private EntityManagerInterface $em, private string $mobileMapUrl = '') {}

    #[Route('/api/navigation/qr/{placeId}', name: 'api_navigation_qr', methods: ['GET'], requirements: ['placeId' => '\\d+'])]
    public function __invoke(int $placeId): Response
    {
        $place=$this->em->getRepository(MapPlace::class)->find($placeId);
        if (!$place instanceof MapPlace) throw new NotFoundHttpException('Объект не найден.');
        if (!$place->isRouteAvailable()) throw new UnprocessableEntityHttpException('Маршрут к этому объекту ещё не расчерчен.');
        $settings=$this->em->getRepository(SiteSettings::class)->findOneBy(['code'=>'main']);
        $base=$settings?->mobileMapUrl ?: $this->mobileMapUrl;
        $url=rtrim($base,'?&').'?'.http_build_query(['destination'=>$placeId,'map'=>$place->plan?->getId()]);
        $qr=new QrCode(data:$url, encoding:new Encoding('UTF-8'), errorCorrectionLevel:ErrorCorrectionLevel::High, size:360, margin:12, roundBlockSizeMode:RoundBlockSizeMode::Margin);
        $png=(new PngWriter())->write($qr)->getString();
        return new Response($png,200,['Content-Type'=>'image/png','Cache-Control'=>'public, max-age=3600']);
    }
}
