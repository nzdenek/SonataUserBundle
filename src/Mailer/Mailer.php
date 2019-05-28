<?php

declare(strict_types=1);

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\UserBundle\Mailer;

use FOS\UserBundle\Mailer\MailerInterface;
use FOS\UserBundle\Model\UserInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class Mailer implements MailerInterface
{
    /**
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;

    /**
     * @var \Twig\Environment
     */
    private $twig;

    /**
     * @var \Swift_Mailer
     */
    private $mailer;

    /**
     * @var array
     */
    private $fromEmail;

    /**
     * @var string
     */
    private $emailTemplate;

    public function __construct(
        UrlGeneratorInterface $urlGenerator,
        \Twig\Environment $twig,
        \Swift_Mailer $mailer,
        array $fromEmail,
        string $emailTemplate
    ) {
        $this->urlGenerator = $urlGenerator;
        $this->twig = $twig;
        $this->mailer = $mailer;
        $this->fromEmail = $fromEmail;
        $this->emailTemplate = $emailTemplate;
    }

    public function sendResettingEmailMessage(UserInterface $user): void
    {
        $route = 'sonata_user_admin_resetting_reset';
        $url = $this->urlGenerator->generate($route, [
            'token' => $user->getConfirmationToken(),
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        $context = [
            'user' => $user,
            'confirmationUrl' => $url,
        ];
        $template = $this->twig->load($this->emailTemplate);
        $subject = $template->renderBlock('subject', $context);
        $plainBody = $template->renderBlock('body_text', $context);
        $htmlBody = '';
        if ($template->hasBlock('body_html', $context)) {
            $htmlBody = $template->renderBlock('body_html', $context);
        }

        $message = (new \Swift_Message())
            ->setSubject($subject)
            ->setFrom($this->fromEmail)
            ->setTo((string) $user->getEmail())
        ;
        if (empty($htmlBody)) {
            $message->setBody($plainBody);
        } else {
            $message->setBody($htmlBody, 'text/html');
            $message->addPart($plainBody, 'text/plain');
        }

        $this->mailer->send($message);
    }

    public function sendConfirmationEmailMessage(UserInterface $user): void
    {
        throw new \LogicException('This method is not implemented.');
    }
}
