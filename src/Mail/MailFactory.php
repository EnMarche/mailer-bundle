<?php

namespace EnMarche\MailerBundle\Mail;

class MailFactory implements MailFactoryInterface
{
    private $app;
    private $cc = [];
    private $bcc = [];

    /**
     * @param string  $app The application key name
     * @param array[] $cc  Each array must contain at least the email, then the name
     * @param array[] $bcc
     */
    public function __construct(string $app, array $cc = [], array $bcc = [])
    {
        $this->app = $app;

        foreach ($cc as $recipient) {
            if (!is_array($recipient)) {
                throw new \InvalidArgumentException(\sprintf('Expected an array, got %s.', \gettype($recipient)));
            }
            $this->cc[] = new Recipient(...$recipient);
        }
        foreach ($bcc as $recipient) {
            if (!is_array($recipient)) {
                throw new \InvalidArgumentException(\sprintf('Expected an array, got %s.', \gettype($recipient)));
            }
            $this->bcc[] = new Recipient(...$recipient);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function createForClass(
        string $mailClass,
        array $to,
        RecipientInterface $replyTo = null,
        array $templateVars = [],
        string $subject = null,
        SenderInterface $sender = null,
        array $ccRecipients = []
    ): MailInterface {
        $builder = MailBuilder::create($mailClass, $this->app)
            ->setToRecipients($to)
        ;

        if ($replyTo) {
            $builder->setReplyTo($replyTo);
        }
        if ($templateVars) {
            $builder->setTemplateVars($templateVars);
        }
        if ($subject) {
            $builder->setSubject($subject);
        }
        if ($this->cc || $ccRecipients) {
            $builder->setCcRecipients(array_merge($this->cc, $ccRecipients));
        }
        if ($this->bcc) {
            $builder->setBccRecipients($this->bcc);
        }
        if ($sender) {
            $builder->setSender($sender);
        }

        return $builder->getMail();
    }
}
