<?php

namespace C33s\AttachmentBundle\Model;

use C33s\AttachmentBundle\Model\om\BaseAttachmentLinkQuery;
use C33s\AttachmentBundle\Attachment\AttachableObjectInterface;

class AttachmentLinkQuery extends BaseAttachmentLinkQuery
{
    /**
     *
     * @param AttachableObjectInterface $object
     *
     * @return AttachmentLinkQuery
     */
    public function filterByAttachableObject(AttachableObjectInterface $object)
    {
        return $this
            ->filterByModelName($object->getAttachableClassName())
            ->filterByModelId($object->getAttachableId())
        ;
    }
}
