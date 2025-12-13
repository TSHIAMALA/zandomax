<?php

namespace App\EventListener;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpFoundation\Response;

class ExceptionListener
{
    public function onKernelException(ExceptionEvent $event): void
    {
        // Only handle API requests
        $request = $event->getRequest();
        if (strpos($request->getPathInfo(), '/api') !== 0) {
            return;
        }

        $exception = $event->getThrowable();
        $message = $exception->getMessage();
        $code = Response::HTTP_INTERNAL_SERVER_ERROR;

        if ($exception instanceof HttpExceptionInterface) {
            $code = $exception->getStatusCode();
        }

        $data = [
            'status' => $code,
            'error' => $message,
        ];

        // In dev mode, add trace
        if ($_ENV['APP_ENV'] === 'dev') {
            $data['trace'] = $exception->getTraceAsString();
        }

        $response = new JsonResponse($data, $code);
        $event->setResponse($response);
    }
}
