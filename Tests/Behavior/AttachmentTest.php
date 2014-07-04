<?php

use c33s\AttachmentBundle\Model\Attachment;

class AttachmentTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Make sure the bootstrap, generating the Attachment classes with their behaviors, works.
     */
    public function testBuildAttachmentModels()
    {
        $attachment = new Attachment();
    }
}
