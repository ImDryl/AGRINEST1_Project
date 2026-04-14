<?php

namespace App\Controller;

use App\Form\ContactFormType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;

final class StaticPagesController extends AbstractController
{
    #[Route('/about', name: 'app_about', methods: ['GET'])]
    public function about(): Response
    {
        return $this->render('homepage/about.html.twig');
    }

    #[Route('/contact', name: 'app_contact', methods: ['GET', 'POST'])]
    public function contact(Request $request, MailerInterface $mailer): Response
    {
        $form = $this->createForm(ContactFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $fromEmail = (string) ($_ENV['MAILER_FROM_EMAIL'] ?? 'no-reply@agrinest.local');
            $fromName = (string) ($_ENV['MAILER_FROM_NAME'] ?? 'AgriNest');

            $email = (new Email())
                ->from(new Address($fromEmail, $fromName))
                ->to(new Address($fromEmail, $fromName))
                ->replyTo(new Address((string) $data['email'], (string) $data['name']))
                ->subject('[Contact Form] ' . (string) $data['subject'])
                ->text(
                    "New contact form submission:\n\n" .
                    'Name: ' . (string) $data['name'] . "\n" .
                    'Email: ' . (string) $data['email'] . "\n" .
                    'Subject: ' . (string) $data['subject'] . "\n\n" .
                    "Message:\n" . (string) $data['message']
                );

            $acknowledgementEmail = (new Email())
                ->from(new Address($fromEmail, $fromName))
                ->to(new Address((string) $data['email'], (string) $data['name']))
                ->subject('AgriNest: We received your feedback')
                ->text(
                    "Hi " . (string) $data['name'] . ",\n\n" .
                    "Thank you for contacting AgriNest. We received your message and our team will review it shortly.\n\n" .
                    "Your submitted subject: " . (string) $data['subject'] . "\n\n" .
                    "If you need to send more details, just reply to this email.\n\n" .
                    "Regards,\n" .
                    $fromName
                );

            try {
                $mailer->send($email);
                $mailer->send($acknowledgementEmail);
                $this->addFlash('success', 'Thanks for reaching out. We received your message and will reply soon.');

                return $this->redirectToRoute('app_contact');
            } catch (TransportExceptionInterface) {
                $this->addFlash('error', 'Message could not be sent right now. Please try again shortly.');
            }
        }

        return $this->render('homepage/contact.html.twig', [
            'contactForm' => $form->createView(),
        ]);
    }

    #[Route('/sell', name: 'app_sell', methods: ['GET'])]
    public function sell(): Response
    {
        return $this->render('homepage/sell.html.twig');
    }
}

