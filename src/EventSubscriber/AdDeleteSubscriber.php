<?php

namespace App\EventSubscriber;

use ApiPlatform\Core\EventListener\EventPriorities;
use App\Entity\Ad;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Class AdDeleteSubscriber.
 * 
 * @author Mickael Nambinintsoa <mickael.nambinintsoa07081999@gmail.com>
 */
class AdDeleteSubscriber implements EventSubscriberInterface
{
    /** @var EntityManagerInterface */
    private $entityManager;

    /**
     * AdDeleteSubscriber constructor.
     *
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function deleteAdWithBookings(ViewEvent $event)
    {
        $ad = $event->getControllerResult();
        $method = $event->getRequest()->getMethod();

        if ($ad instanceof Ad && $method == Request::METHOD_DELETE) {
            if (count($ad->getBookings()) > 0) {
                throw new BadRequestHttpException("Cannot deleting Ad because it has one or many booking(s) !");
            } else {
                $this->entityManager->remove($ad);
                $this->entityManager->flush();
            }
        }
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::VIEW => ['deleteAdWithBookings', EventPriorities::PRE_WRITE],
        ];
    }
}
