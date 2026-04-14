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

final class HomepageController extends AbstractController
{
    #[Route('/', name: 'app_homepage', methods: ['GET', 'POST'])]
    public function index(Request $request, MailerInterface $mailer): Response
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('app_admin_dashboard');
        }
        if ($this->isGranted('ROLE_STAFF')) {
            return $this->redirectToRoute('app_staff_dashboard');
        }

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
                ->subject('[Homepage Contact] ' . (string) $data['subject'])
                ->text(
                    "New homepage inquiry:\n\n" .
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

                return $this->redirect($this->generateUrl('app_homepage') . '#contact');
            } catch (TransportExceptionInterface) {
                $this->addFlash('error', 'Message could not be sent right now. Please try again shortly.');
            }
        }

        return $this->render('homepage/index.html.twig', [
            'controller_name' => 'HomepageController',
            'contactForm' => $form->createView(),
        ]);
    }
}
