<?php

namespace App\EventListener;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Validator\Exception\ValidationFailedException;

#[AsEventListener(event: KernelEvents::EXCEPTION)]
final class ApiExceptionListener
{
    public function __construct(#[Autowire('%kernel.debug%')] private readonly bool $debug) {}

    public function __invoke(ExceptionEvent $event): void
    {
        if (!str_starts_with($event->getRequest()->getPathInfo(), '/api')) {
            return;
        }

        $e = $event->getThrowable();
        $isHttp = $e instanceof HttpExceptionInterface;
        $status = $isHttp ? $e->getStatusCode() : 500;

        $data = ['error' => match (true) {
            $status === 404 && str_contains($e->getMessage(), 'object not found') => 'Recurso não encontrado.',
            $status >= 500 && !$this->debug => 'Erro interno no servidor.',
            default => $e->getMessage(),
        }];

        if ($e->getPrevious() instanceof ValidationFailedException) {
            $data = ['error' => 'Dados inválidos.', 'violations' => []];
            foreach ($e->getPrevious()->getViolations() as $v) {
                $field = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $v->getPropertyPath()));
                $data['violations'][$field][] = $v->getMessage();
            }
        }

        $event->setResponse(new JsonResponse($data, $status, $isHttp ? $e->getHeaders() : []));
    }
}