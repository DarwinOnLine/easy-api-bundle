<?php

namespace EasyApiBundle\EventListener;

use EasyApiBundle\Services\User\Tracking;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTAuthenticatedEvent;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class JWTAuthenticatedListener
{
    /**
     * JWTAuthenticatedListener constructor.
     *
     * @param ParameterBagInterface $parameterBag
     * @param Tracking              $tracking
     * @param RequestStack          $requestStack
     */
    public function __construct(private readonly ParameterBagInterface $parameterBag, private readonly Tracking $tracking, private readonly RequestStack $requestStack)
    {
    }

    /**
     * @param JWTAuthenticatedEvent $event
     *
     * @throws \Exception
     */
    public function onJWTAuthenticated(JWTAuthenticatedEvent $event): void
    {
        if ($this->parameterBag->get(Tracking::TRACKING_ENABLE_PARAMETER)) {
            $this->tracking->updateLastAction(
                $event->getToken()->getUser(),
                $this->requestStack->getCurrentRequest(),
                $event->getToken()->getCredentials()
            );
        }
    }
}
