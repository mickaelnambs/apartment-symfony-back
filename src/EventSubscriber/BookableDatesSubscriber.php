<?php

namespace App\EventSubscriber;

use App\Entity\Booking;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use ApiPlatform\Core\EventListener\EventPriorities;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Class BookableDatesSubscriber.
 * 
 * @author Mickael Nambinintsoa <mickael.nambinintsoa07081999@gmail.com>
 */
class BookableDatesSubscriber implements EventSubscriberInterface
{
    /** @var EntityManagerInterface */
    private $entityManager;

    /**
     * BookableDatesSubscriber constructor.
     *
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }
    
    public function isBookableDate(ViewEvent $event)
    {
        $booking = $event->getControllerResult();
        $method = $event->getRequest()->getMethod();

        if ($booking instanceof Booking && ($method == Request::METHOD_POST || $method == Request::METHOD_PUT)) {
            if (!$booking->isBookableDates()) {
                return new BadRequestHttpException("Cannot persist your booking because this date's already taken !");
            } else {
                $this->entityManager->persist($booking);
                $this->entityManager->flush();
            }
        }
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::VIEW => ['isBookableDate', EventPriorities::PRE_WRITE],
        ];
    }
}
