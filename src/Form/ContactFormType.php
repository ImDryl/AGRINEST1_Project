<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class ContactFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Full name',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Enter your full name',
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Please enter your name.']),
                    new Length(['max' => 100]),
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email address',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'you@example.com',
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Please enter your email.']),
                    new Email(['message' => 'Please enter a valid email address.']),
                ],
            ])
            ->add('subject', TextType::class, [
                'label' => 'Subject',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'How can we help?',
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Please enter a subject.']),
                    new Length(['max' => 150]),
                ],
            ])
            ->add('message', TextareaType::class, [
                'label' => 'Your message',
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 6,
                    'placeholder' => 'Share your question, concern, or feedback...',
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Please enter your message.']),
                    new Length([
                        'min' => 10,
                        'minMessage' => 'Your message should be at least {{ limit }} characters.',
                        'max' => 3000,
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
