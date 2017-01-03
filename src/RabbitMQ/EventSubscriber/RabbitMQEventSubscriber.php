<?php

namespace CultuurNet\ProjectAanvraag\RabbitMQ\EventSubscriber;

use CultuurNet\ProjectAanvraag\Core\MessageAttemptedInterface;
use CultuurNet\ProjectAanvraag\Entity\Project;
use CultuurNet\ProjectAanvraag\Project\Event\ProjectEvent;
use CultuurNet\ProjectAanvraag\RabbitMQ\DelayableMessageInterface;
use Psr\Log\LoggerInterface;
use SimpleBus\Message\Bus\Middleware\MessageBusSupportingMiddleware;
use SimpleBus\RabbitMQBundleBridge\Event\Events;
use SimpleBus\RabbitMQBundleBridge\Event\MessageConsumptionFailed;
use SimpleBus\Serialization\Envelope\Envelope;
use SimpleBus\Serialization\Envelope\Serializer\MessageInEnvelopSerializer;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscribes to the RabbitMQ events.
 */
class RabbitMQEventSubscriber implements EventSubscriberInterface
{
    /**
     * @var MessageBusSupportingMiddleware
     */
    protected $eventBus;

    /**
     * @var MessageInEnvelopSerializer
     */
    protected $messageInEnvelopeSerializer;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var array
     */
    protected $config;

    /**
     * RabbitMQEventSubscriber constructor.
     * @param MessageBusSupportingMiddleware $eventBus
     * @param MessageInEnvelopSerializer $messageInEnvelopeSerializer
     * @param LoggerInterface $logger
     * @param array $config
     */
    public function __construct(MessageBusSupportingMiddleware $eventBus, MessageInEnvelopSerializer $messageInEnvelopeSerializer, LoggerInterface $logger, array $config)
    {
        $this->eventBus = $eventBus;
        $this->messageInEnvelopeSerializer = $messageInEnvelopeSerializer;
        $this->logger = $logger;
        $this->config = $config;
    }

    /**
     * Message consumption failed.
     * @param MessageConsumptionFailed $event
     */
    public function onConsumptionFailed(MessageConsumptionFailed $event)
    {
        /**
         * Unwrap the envelope and get the message
         * @var Envelope $envelope
         */
        $eventMessage = $event->message();
        $envelope = $this->messageInEnvelopeSerializer->unwrapAndDeserialize($eventMessage->body);
        $message = $envelope->message();

        // Requeue failed events
        if ($message instanceof MessageAttemptedInterface && $message instanceof DelayableMessageInterface) {
            /** @var ProjectEvent $message */
            // Increase the attempts counter of the message
            $message->attempt();

            // Allow the message to fail 5 times, then log it
            if ($message->getAttempts() < 5) {
                // Retry the command with delay
                $message->setDelay(!empty($this->config['failed_message_delay']) ? $this->config['failed_message_delay'] : 3600000);
                $this->eventBus->handle($message);
            } else {
                // Only log failed attempts for project events
                if ($message instanceof ProjectEvent) {
                    $this->logger->error('Message: ' . $eventMessage->body);
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [Events::MESSAGE_CONSUMPTION_FAILED => 'onConsumptionFailed'];
    }
}
