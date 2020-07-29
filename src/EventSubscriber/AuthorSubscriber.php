<?php

namespace App\EventSubscriber;

use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use ApiPlatform\Core\EventListener\EventPriorities;
use App\Entity\Ad;
use App\Entity\Booking;
use App\Entity\Comment;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Class AuthorSubscriber.
 * 
 * @author Mickael Nambinintsoa <mickael.nambinintsoa07081999@gmail.com>
 */
class AuthorSubscriber implements EventSubscriberInterface
{
    /** @var TokenStorageInterface */
    private $tokenStorage;

    /**
     * AuthorSubscriber constructor.
     *
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    public function getUserFromToken(ViewEvent $event)
    {
        $entity = $event->getControllerResult();
        $method = $event->getRequest()->getMethod();

        if (($entity instanceof Ad || $entity instanceof Booking || $entity instanceof Comment)
            && ($method == Request::METHOD_POST || $method == Request::METHOD_PUT)) {
            $author = $this->tokenStorage->getToken()->getUser();
            $entity->setAuthor($author);
        }
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::VIEW => ['getUserFromToken', EventPriorities::PRE_WRITE],
        ];
    }
}
