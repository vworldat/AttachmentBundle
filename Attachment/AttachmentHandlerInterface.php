<?php

namespace c33s\AttachmentBundle\Attachment;

use Symfony\Component\HttpFoundation\File\File;

interface AttachmentHandlerInterface
{
    public function storeAndAttachFile(File $file, AttachableObjectInterface $object, $fieldName = null, $deleteAfterCopy = null);
    
}
