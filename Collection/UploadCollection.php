<?php

namespace c33s\AttachmentBundle\Collection;

/**
 * This is a special implementation of PropelCollection to allow compatibility with
 * Avocode\FormExtensionsBundle\Form\EventListener\CollectionUploadSubscriber, which
 * usually handles Doctrine collections.
 *
 * If this collection holds propel objects that implement Avocode\FormExtensionsBundle\Form\Model\UploadCollectionFileInterface,
 * everything will work out of the box with the collection_upload form type.
 *
 * @author david
 */
class UploadCollection extends \PropelCollection
{
    /**
     * Add item to the collection
     *
     * @param mixed $item
     */
    public function add($item)
    {
        $this->append($item);
    }
    
    /**
     * Remove object from the collection
     *
     * @param mixed $element
     */
    public function removeElement($element)
    {
        $key = array_search($element, $this->getArrayCopy());
        
        if (false !== $key)
        {
            $this->remove($key);
        }
    }
    
    /**
     * Overwritten to prevent cloning of the collection elements, which would prevent
     * saving the actual form data.
     */
    public function __clone()
    {
    }
}
