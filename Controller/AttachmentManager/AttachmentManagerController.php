<?php

namespace c33s\AttachmentBundle\Controller\AttachmentManager;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

/**
 * Description of AttachmentManagerController
 *
 * @author david
 */
class AttachmentManagerController extends Controller
{
    //put your code here
    public function indexAction()
    {
        return $this->render('c33sAttachmentBundle:AttachmentManager:index.html.twig');
    }
}
