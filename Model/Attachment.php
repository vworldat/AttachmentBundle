<?php

namespace c33s\AttachmentBundle\Model;

use c33s\AttachmentBundle\Model\om\BaseAttachment;
use Symfony\Component\EventDispatcher\GenericEvent;
use c33s\AttachmentBundle\Attachment\AttachmentHandler;

class Attachment extends BaseAttachment
{
    protected static $attachmentHandler;
    
    /**
     * Get the AttachmentHandler service instance.
     *
     * @return AttachmentHandler
     */
    public static function getAttachmentHandler()
    {
        if (null === static::$attachmentHandler)
        {
            self::getEventDispatcher()->dispatch('propel.fetch_attachment_handler_service', new GenericEvent());
        }
        
        return static::$attachmentHandler;
    }
    
    /**
     * Set/inject the AttachmentHandler service instance.
     *
     * @param AttachmentHandler $attachmentHandler
     */
    public static function setAttachmentHandler($attachmentHandler)
    {
        static::$attachmentHandler = $attachmentHandler;
    }
}
