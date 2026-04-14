<?php

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

class EmailVerificationService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MailerInterface $mailer,
        private string $mailerDsn,
        private string $fromEmail,
        private string $fromName,
    ) {
    }

    /**
     * Generate a unique verification token
     */
    public function generateVerificationToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Send verification email to user
     *
     * @throws \Symfony\Component\Mailer\Exception\TransportExceptionInterface
     */
    public function sendVerificationEmail(User $user, string $verificationUrl): void
    {
        if (str_starts_with($this->mailerDsn, 'null://')) {
            throw new \RuntimeException(
                'Mailer is not configured: set MAILER_SMTP_USER and MAILER_SMTP_PASSWORD (Brevo SMTP page) ' .
                'and set MAILER_SMTP_USER / MAILER_SMTP_PASSWORD (and MAILER_DSN empty) for Brevo SMTP.'
            );
        }

        $email = (new TemplatedEmail())
            ->from(new Address($this->fromEmail, $this->fromName))
            ->to(new Address((string) $user->getEmail()))
            ->subject('AGRINEST - Please verify your email address')
            ->htmlTemplate('emails/verification.html.twig')
            ->context([
                'user' => $user,
                'verificationUrl' => $verificationUrl,
            ]);

        $this->mailer->send($email);
    }

    /**
     * Verify a token and mark user as verified
     */
    public function verifyToken(string $token): ?User
    {
        $user = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['verificationToken' => $token]);

        if (!$user) {
            return null;
        }

        $user->setIsVerified(true);
        $user->setVerificationToken(null);

        $this->entityManager->flush();

        return $user;
    }

    /**
     * Check if a user needs verification
     */
    public function needsVerification(User $user): bool
    {
        return !$user->isVerified();
    }
}
