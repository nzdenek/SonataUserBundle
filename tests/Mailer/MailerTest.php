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

namespace Sonata\UserBundle\Tests\DependencyInjection;

use FOS\UserBundle\Model\UserInterface;
use PHPUnit\Framework\TestCase;
use Sonata\UserBundle\Mailer\Mailer;
use Symfony\Component\Routing\RouterInterface;

class MailerTest extends TestCase
{
    /**
     * @var RouterInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $router;

    /**
     * @var \Twig\Environment|\PHPUnit_Framework_MockObject_MockObject
     */
    private $twig;

    /**
     * @var \Swift_Mailer|\PHPUnit_Framework_MockObject_MockObject
     */
    private $mailer;

    /**
     * @var array
     */
    private $emailFrom;

    /**
     * @var string
     */
    private $emailTemplate;

    public function setUp(): void
    {
        $this->router = $this->createMock(RouterInterface::class);
        $this->twig = $this->createMock(\Twig\Environment::class);
        $this->mailer = $this->createMock(\Swift_Mailer::class);
        $this->emailFrom = ['noreply@sonata-project.org'];
        $this->emailTemplate = 'foo';
    }

    public function testSendConfirmationEmailMessage(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('This method is not implemented.');

        $user = $this->createMock(UserInterface::class);

        $this->getMailer()->sendConfirmationEmailMessage($user);
    }

    /**
     * @dataProvider emailTemplateData
     */
    public function testSendResettingEmailMessage($subject, $plain, $hasHtml,
        $html, $type, $body): void
    {
        $user = $this->createMock(UserInterface::class);
        $user->expects($this->any())
            ->method('getConfirmationToken')
            ->willReturn('user-token');
        $user->expects($this->any())
            ->method('getEmail')
            ->willReturn('user@sonata-project.org');

        $this->router->expects($this->once())
            ->method('generate')
            ->with('sonata_user_admin_resetting_reset', ['token' => 'user-token'])
            ->willReturn('/foo');

        $context = ['user' => $user, 'confirmationUrl' => '/foo'];
        $map = [
            ['subject', $context, [], true, $subject],
            ['body_text', $context, [], true, $plain],
            ['body_html', $context, [], true, $html],
        ];
        $template = $this->createMock(\Twig\Template::class);
        $template->expects($this->any())
            ->method('renderBlock')
            ->will($this->returnValueMap($map));
        $template->expects($this->once())
            ->method('hasBlock')
            ->with('body_html', $context)
            ->willReturn($hasHtml);

        $this->twig->expects($this->once())
            ->method('load')
            ->with($this->emailTemplate)
            ->willReturn($template);

        $this->mailer->expects($this->once())
            ->method('send')
            ->willReturnCallback(function (\Swift_Message $message) use ($subject, $plain, $hasHtml, $html, $type, $body): void {
                $this->assertSame($subject, $message->getSubject());
                $this->assertSame($body, $message->getBody());
                $this->assertSame($type, $message->getContentType());
                $this->assertArrayHasKey($this->emailFrom[0], $message->getFrom());
                $this->assertArrayHasKey('user@sonata-project.org', $message->getTo());
            });

        $this->getMailer()->sendResettingEmailMessage($user);
    }

    public function emailTemplateData()
    {
        return [
            [
                'Subject',
                'Plain text',
                true,
                '<p>HTML text</p>',
                'multipart/alternative',
                '<p>HTML text</p>',
            ],
            [
                'Subject',
                'Plain text',
                false,
                '',
                'text/plain',
                'Plain text',
            ],
        ];
    }

    private function getMailer(): Mailer
    {
        return new Mailer($this->router, $this->twig, $this->mailer,
            $this->emailFrom, $this->emailTemplate);
    }
}
