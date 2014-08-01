<?php

namespace C33s\AttachmentBundle\Listener;

use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\DependencyInjection\ContainerAware;
use C33s\AttachmentBundle\Model\Attachment;

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
