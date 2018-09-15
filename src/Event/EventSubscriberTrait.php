<?php

namespace yswery\DNS\Event;

trait EventSubscriberTrait
{
    /**
     * @var EventSubscriberInterface[]
     */
    private $subscribers = [];

    public function registerEventSubscriber(EventSubscriberInterface $subscriber): void
    {
        if ($this->validateSubscriber($subscriber)) {
            $this->subscribers[] = $subscriber;
        }
    }

    private function validateSubscriber(EventSubscriberInterface $subscriber): bool
    {
        foreach ($subscriber->getSubscribedEvents() as $method) {
            if (!method_exists($subscriber, $method)) {
                throw new \BadMethodCallException(
                    \sprintf(
                        'Event method %s declared but not implemented in %s',
                        $method,
                        \get_class($subscriber)
                    )
                );
            }
        }

        return true;
    }

    private function event(Event $event): void
    {
        foreach ($this->subscribers as $subscriber) {
            $handlers = $subscriber->getSubscribedEvents();
            $method = $handlers[\get_class($event)] ?? null;

            if ($method && method_exists($subscriber, $method)) {
                $subscriber->$method($event);
            }
        }
    }
}
