<?php

namespace C33s\AttachmentBundle\Model;

use C33s\AttachmentBundle\Model\om\BaseAttachmentQuery;
use C33s\AttachmentBundle\Attachment\AttachableObjectInterface;

use \Criteria;

class AttachmentQuery extends BaseAttachmentQuery
{
    /**
     * Filter attachments for the given object, using the soft relation table.
     *
     * @param AttachableObjectInterface $object
     *
     * @return AttachmentQuery
     */
    public function filterAllAttachmentsForObject(AttachableObjectInterface $object)
    {
        return $this
            ->joinAttachmentLink()
            ->useAttachmentLinkQuery()
                ->filterByAttachableObject($object)
            ->endUse()
        ;
    }
    
    /**
     * Filter attachments for the given object, only returning those that are not linked to
     * a specific field name.
     *
     * @param AttachableObjectInterface $object
     *
     * @return AttachmentQuery
     */
    public function filterGeneralAttachmentsForObject(AttachableObjectInterface $object)
    {
        return $this
            ->filterAllAttachmentsForObject($object)
            ->useAttachmentLinkQuery()
                ->filterByModelField(null, Criteria::ISNULL)
            ->endUse()
        ;
    }
    
    /**
     * Filter attachments for the given object, only returning those that are linked to
     * the given field name
     *
     * @param AttachableObjectInterface $object
     * @param string $fieldName
     *
     * @return AttachmentQuery
     */
    public function filterSpecificAttachmentsForObject(AttachableObjectInterface $object, $fieldName)
    {
        return $this
            ->filterAllAttachmentsForObject($object)
            ->useAttachmentLinkQuery()
                ->filterByModelField($fieldName)
            ->endUse()
        ;
    }
}
