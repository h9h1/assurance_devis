<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Handles CORS for the /api/* routes consumed by the React frontend.
 *
 * - Responds immediately to OPTIONS preflight requests (so the router
 *   never gets to return 405 Method Not Allowed).
 * - Adds Access-Control-Allow-* headers to every /api response.
 *
 * No extra bundle required — drop this file in src/EventSubscriber/ and
 * Symfony's autoconfigure will register it automatically.
 */
class CorsSubscriber implements EventSubscriberInterface
{
    // Origins allowed to call the API.
    // Add your production domain here when you deploy.
    private const ALLOWED_ORIGINS = [
        'http://localhost:3000',
        'http://127.0.0.1:3000',
    ];

    public static function getSubscribedEvents(): array
    {
        return [
            // Priority 250 — run before the router (priority 32) so OPTIONS
            // never reaches route matching and never gets a 405.
            KernelEvents::REQUEST  => ['onRequest',  250],
            KernelEvents::RESPONSE => ['onResponse',   0],
        ];
    }

    public function onRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        // Only act on /api/* paths
        if (!str_starts_with($request->getPathInfo(), '/api')) {
            return;
        }

        // Respond to preflight immediately — never let the router see it
        if ($request->getMethod() === 'OPTIONS') {
            $response = new Response('', Response::HTTP_NO_CONTENT);
            $this->addCorsHeaders($request->headers->get('Origin', ''), $response);
            $event->setResponse($response);
        }
    }

    public function onResponse(ResponseEvent $event): void
    {
        $request = $event->getRequest();

        if (!str_starts_with($request->getPathInfo(), '/api')) {
            return;
        }

        $this->addCorsHeaders(
            $request->headers->get('Origin', ''),
            $event->getResponse()
        );
    }

    private function addCorsHeaders(string $origin, Response $response): void
    {
        $allowed = in_array($origin, self::ALLOWED_ORIGINS, true)
            ? $origin
            : self::ALLOWED_ORIGINS[0];   // fallback — tighten in prod

        $response->headers->set('Access-Control-Allow-Origin',  $allowed);
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Accept, Authorization, X-Requested-With');
        $response->headers->set('Access-Control-Allow-Credentials', 'true');
        $response->headers->set('Access-Control-Max-Age', '3600');
        $response->headers->set('Vary', 'Origin');
    }
}