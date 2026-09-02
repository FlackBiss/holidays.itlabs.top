<?php

namespace App\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
#[AdminRoute('/map-tracer', name: 'map_tracer', allowedDashboards: [DashboardController::class])]
final class MapTracerController extends AbstractController
{
    public function __construct(
        #[Autowire('%env(MAP_TRACER_URL)%')]
        private readonly string $tracerUrl,
    ) {
    }

    #[AdminRoute('/', name: 'index')]
    public function index(): Response
    {
        return $this->render('admin/map_tracer.html.twig', [
            'tracer_url' => $this->tracerUrl,
        ]);
    }
}
