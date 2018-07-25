<?php

namespace EnMarche\MailerBundle\Client\PayloadFactory;

use EnMarche\MailerBundle\Client\MailRequestInterface;
use EnMarche\MailerBundle\Client\PayloadFactoryInterface;
use EnMarche\MailerBundle\Entity\Address;
use EnMarche\MailerBundle\Entity\RecipientVars;
use Psr\Http\Message\ResponseInterface;

class MailjetPayloadFactory implements PayloadFactoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function createRequestPayload(MailRequestInterface $mailRequest): array
    {
        $payload = [
            'MJ-TemplateID' => $mailRequest->getTemplateName(),
            'MJ-TemplateLanguage' => true,
        ];

        $templateVars = $mailRequest->getTemplateVars();

        if ($ccRecipients = $mailRequest->getCcRecipients()) {
            // Transactional
            if ($mailRequest->getCampaign()) {
                throw new \LogicException(\sprintf('A campaign mail request (id: %d, campaign id:  %s) cannot have CC recipients.', $mailRequest->getId(), $mailRequest->getCampaign()));
            }

            $recipientsVars = $mailRequest->getRecipientVars();

            if ($mailRequest->getRecipientsCount() > 1) {
                throw new \LogicException(\sprintf('The mail request (id: %d) has no campaign but more than one recipient.', $mailRequest->getId()));
            }

            foreach ($recipientsVars as $recipient) {
                $payload['To'] = $this->formatAddress($recipient->getAddress());
                $payload['Vars'] = $recipient->getTemplateVars();
            }

            $payload['Cc'] = \implode(', ', \array_map([$this, 'formatAddress'], $ccRecipients));

            if ($bccRecipients = $mailRequest->getBccRecipients()) {
                $payload['Bcc'] = \implode(', ', \array_map([$this, 'formatAddress'], $bccRecipients));
            }
        } else {
            // Campaign
            foreach ($mailRequest->getRecipientVars() as $recipient) {
                $payload['Recipients'] = $this->createRecipient($recipient, $templateVars);
            }
        }

        if ($replyTo = $mailRequest->getReplyTo()) {
            $payload['Headers']['Reply-To'] = $this->formatAddress($replyTo);
        }

        return $payload;
    }

    /**
     * {@inheritdoc}
     */
    public function createResponsePayload(ResponseInterface $response): array
    {
        return \GuzzleHttp\json_decode($response->getBody());
    }

    private function createRecipient(RecipientVars $recipientVars, array $templateVars): array
    {
        $address = $recipientVars->getAddress();

        $recipient = [
            'Email' => $address->getEmail(),
        ];

        if ($name = $address->getName()) {
            $recipient['Name'] = $name;
        }

        if ($vars = \array_merge($templateVars, $recipientVars->getTemplateVars())) {
            $recipient['Vars'] = $vars;
        }

        return $recipient;
    }

    private function formatAddress(Address $address): string
    {
        if ($name = $address->getName()) {
            return \sprintf('"%s" <%s>', $this->fixMailjetParsing($name), $address->getEmail());
        }

        return \sprintf('<%s>', $address->getEmail());
    }

    private function fixMailjetParsing(?string $string): string
    {
        return \str_replace(',', '', $string);
    }
}
