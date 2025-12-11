<?php

namespace App\EventListener;

use App\Service\ActivityLogService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\LogoutEvent;

class LogoutListener implements EventSubscriberInterface
{
    public function __construct(
        private ActivityLogService $activityLogService
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LogoutEvent::class => 'onLogout',
        ];
    }

    public function onLogout(LogoutEvent $event): void
    {
        $user = $event->getToken()?->getUser();
        
        if ($user instanceof \App\Entity\User) {
            $this->activityLogService->logLogout($user);
        }
    }
}

