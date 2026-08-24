<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Attribute\Route;

final class ApiDocsRedirectController
{
    #[Route('/api', name: 'api_docs_redirect', methods: ['GET'], priority: 100)]
    public function __invoke(): RedirectResponse { return new RedirectResponse('/api/docs.html'); }
}
