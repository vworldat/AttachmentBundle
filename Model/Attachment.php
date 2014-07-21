<?php

namespace c33s\AttachmentBundle\Model;

use Avocode\FormExtensionsBundle\Form\Model\UploadCollectionFileInterface;
use c33s\AttachmentBundle\Attachment\AttachmentHandlerInterface;
use c33s\AttachmentBundle\Model\om\BaseAttachment;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\HttpFoundation\File\File;
use c33s\AttachmentBundle\Attachment\AttachableObjectInterface;

class Attachment extends BaseAttachment implements UploadCollectionFileInterface
{
    protected static $attachmentHandler;
    
    protected $rememberFile;
    protected $rememberObject;
    protected $rememberName;
    
    /**
     * Get the AttachmentHandler service instance.
     *
     * @return AttachmentHandlerInterface
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
     * @param AttachmentHandlerInterface $attachmentHandler
     */
    public static function setAttachmentHandler(AttachmentHandlerInterface$attachmentHandler)
    {
        static::$attachmentHandler = $attachmentHandler;
    }
    
    /**
     * Check if this object contains new file and object data set by a form upload.
     *
     * @return boolean
     */
    public function hasRememberedNewData()
    {
        return null !== $this->rememberFile && null !== $this->rememberObject;
    }
    
    /**
     * Save uploaded file to storage. This will create another Attachment object, this one will be dropped.
     *
     * @return Attachment   The new Attachment object
     */
    public function saveNewDataToStorage()
    {
        if ($this->hasRememberedNewData())
        {
            return static::getAttachmentHandler()->storeAndAttachFile($this->rememberFile, $this->rememberObject);
        }
    }
    
    /**
     * This is used by the admin generator forms and will be stored in all AttachmentLink references that have been hydrated.
     *
     * @param string $name
     */
    public function setName($name)
    {
        $this->rememberName = $name;
    }
    
    /**
     * Check if there are any pre-loaded AttachmentLink objects.
     *
     * @return boolean
     */
    protected function hasLoadedLinks()
    {
        return null !== $this->collAttachmentLinks && count($this->collAttachmentLinks) > 0;
    }
    
    /**
     * Get the custom name of the first pre-loaded AttachmentLink object.
     *
     * @return string
     */
    public function getName()
    {
        if ($this->hasLoadedLinks())
        {
            return (string) reset($this->collAttachmentLinks)->getCustomName();
        }
        
        return '';
    }
    
    /**
     * Delete any pre-loaded AttachmentLinks
     *
     * @return Attachment
     */
    public function deleteLoadedAttachmentLinks()
    {
        if (!$this->hasLoadedLinks())
        {
            return;
        }
        
        foreach ($this->collAttachmentLinks as $attachmentLink)
        {
            /* @var $attachmentLink AttachmentLink */
            $attachmentLink->delete();
        }
    }
    
    /**
     * Save custom name to all loaded AttachmentLinks if it has been set from the outside.
     *
     * @return Attachment
     */
    public function saveRememberedCustomName()
    {
        if (null === $this->rememberName || !$this->hasLoadedLinks())
        {
            return;
        }
        
        foreach ($this->collAttachmentLinks as $attachmentLink)
        {
            /* @var $attachmentLink AttachmentLink */
            $attachmentLink
                ->setCustomName($this->rememberName)
                ->save()
            ;
        }
        
        return $this;
    }
    
    /**
     * @return File
     */
    public function getFile()
    {
        return static::getAttachmentHandler()->getFile($this->getFileKey());
    }
    
    /**
     * Set uploaded file. This is used by UploadCollectionFileInterface.
     *
     * @var $file Uploaded file
     */
    public function setFile(File $file)
    {
        $this->rememberFile = $file;
    }
    
    /**
     * Return file size in bytes
     *
     * @return integer
     */
    public function getSize()
    {
        return $this->getFileSize();
    }
    
    /**
     * Set governing entity. This is used by UploadCollectionFileInterface.
     *
     * @var $parent Governing entity
     */
    public function setParent($object)
    {
        $this->rememberObject = $object;
    }
    
    /**
     * Get file web path (used by upload form types)
     *
     * @return string
     */
    public function getFileWebPath()
    {
        return static::getAttachmentHandler()->getFileUrl($this->getFileKey());
    }
    
    /**
     * Return true if file thumbnail should be generated
     *
     * @return boolean
     */
    public function getPreview()
    {
        return true;
    }
    
    /**
     * Get the AttachmentLink for a specific attachable object and field name.
     *  
     * @param AttachableObjectInterface $object
     * @param string $fieldName
     * 
     * @return AttachmentLink
     */
    public function getLinkForObject(AttachableObjectInterface $object, $fieldName = null)
    {
        return AttachmentLinkQuery::create()
            ->filterByAttachment($this)
            ->filterByAttachableObject($object)
            ->filterByModelField($fieldName)
            ->findOne()
        ;
    }
}
