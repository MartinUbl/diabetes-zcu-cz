<?php

namespace App\Services;

use Nette;

class Mailer
{
    /** @var Nette\Mail\Mailer */
    private $netteMailer;
    
    /** @var Nette\Bridges\ApplicationLatte\TemplateFactory */
    private $templateFactory;

    /** @var string */
    private $noreplySource = "noreply@local.domain";
    
    public function __construct(string $fromNoreply, Nette\Mail\Mailer $netteMailer, Nette\Bridges\ApplicationLatte\TemplateFactory $templateFactory) {
        $this->noreplySource = $fromNoreply;
        $this->netteMailer = $netteMailer;
        $this->templateFactory = $templateFactory;
    }
    
    /**
     * Sends a templated e-mail
     * @param string|array $to
     * @param string $subject
     * @param string $bodyTemplateName
     * @param array $parameters
     * @param string $from
     */
    public function sendMail($to, $subject, $bodyTemplateName, $parameters = [], $from = null)
    {
        $mail = new Nette\Mail\Message;
        
        if (is_array($to))
        {
            foreach ($to as $dest)
                $mail->addTo($dest);
        }
        else
            $mail->addTo($to);
        
        if (!$parameters)
            $parameters = [];
        
        $template = $this->templateFactory->createTemplate();
        
        if (!str_ends_with($bodyTemplateName, '.latte'))
                $bodyTemplateName .= '.latte';
        
        $mail->setFrom(empty($from) ? $this->noreplySource : $from)
             ->setSubject($subject)
             ->setHtmlBody($template->renderToString(__DIR__.'/templates/Mail/'.$bodyTemplateName, $parameters));
        
        $this->netteMailer->send($mail);
    }
}
