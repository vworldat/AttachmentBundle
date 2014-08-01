<?php

namespace C33s\AttachmentBundle\Attachment;

/**
 * Objects which want to allow attachments must implement this interface
 *
 * @author david
 */
interface AttachableObjectInterface
{
    /**
     * Get a class name (including namespace) to identify attachable objects of the same type.
     *
     * @return string
     */
    public function getAttachableClassName();
    
    /**
     * Get a numeric ID referencing the given object. Usually this is something like $this->getId().
     *
     * @return int
     */
    public function getAttachableId();
    
    /**
     * Get a list of field names that are used to directly link attachments via key. These fields
     * should hold a key string each. 128 bytes varchar is recommended.
     *
     * example:
     * return array(
     *     'Logo',
     *     'Icon',
     * );
     * matching $this->getLogo() and setLogo($logoHash) et cetera.
     *
     * @return array
     */
    public function getAttachableFieldNames();
    
    /**
     * Get all attachments related to this object.
     *
     * @return \PropelCollection[Attachment]
     */
    public function getAllAttachments();
    
    /**
     * Get attachments related to this object but not referenced by a specific field.
     *
     * @return \PropelCollection[Attachment]
     */
    public function getGeneralAttachments();
    
    /**
     * Get attachments related to this object and linked to a specific field. This field does not have to exist.
     *
     * @param string $fieldName
     *
     * @return \PropelCollection[Attachment]
     */
    public function getSpecificAttachments($fieldName);
}
