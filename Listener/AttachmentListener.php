<?php

namespace c33s\AttachmentBundle\Listener;

use Symfony\Component\EventDispatcher\GenericEvent;
use c33s\AttachmentBundle\Attachment\AttachmentHandler;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAware;
use c33s\AttachmentBundle\Model\Attachment;

class AttachmentListener extends ContainerAware
{
    /**
     * This is called on demand if the AttachmentModel needs the handler service. We use the injected container
     * instead of directly injecting the service, so the service is only initialized on demand.
     *
     * @param GenericEvent $event
     */
    public function fetchAttachmentHandlerService(GenericEvent $event)
    {
        Attachment::setAttachmentHandler($this->container->get('c33s_attachment.handler'));
    }
}
