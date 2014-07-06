<?php

use c33s\AttachmentBundle\Model\Attachment;
use Symfony\Component\HttpFoundation\File\File;

class AttachmentTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Make sure the bootstrap, generating the Attachment classes with their behaviors, works.
     */
    public function testBuildAttachmentModels()
    {
        $attachment = new Attachment();
        
        $this->assertInstanceOf('c33s\\AttachmentBundle\\Model\\Attachment', $attachment);
    }
    
    public function testHasRememberedNewData()
    {
        $attachment = new Attachment();
        
        // initially there is nothing remembered
        $this->assertFalse($attachment->hasRememberedNewData());
        
        $attachment->setFile(new File(__FILE__));
        
        // still nothing after setting a file only
        $this->assertFalse($attachment->hasRememberedNewData());
        
        $attachment->setParent($this->getMock('c33s\\AttachmentBundle\\Attachment\\AttachableObjectInterface'));
        
        // remember after setting both file and object
        $this->assertTrue($attachment->hasRememberedNewData());
    }
    
    public function testHandlerIsCalledAfterSaveNewData()
    {
        $handler = $this->getMockBuilder('c33s\\AttachmentBundle\\Attachment\\AttachmentHandler')
            ->disableOriginalConstructor()
            ->getMock()
        ;
        
        Attachment::setAttachmentHandler($handler);
        
        $file = new File(__FILE__);
        $object = $this->getMock('c33s\\AttachmentBundle\\Attachment\\AttachableObjectInterface');
        
        $attachment = new Attachment();
        $attachment->setFile($file);
        $attachment->setParent($object);
        
        $handler->expects($this->once())
            ->method('storeAndAttachFile')
            ->with($file, $object)
        ;
        
        $attachment->saveNewDataToStorage();
    }
}
