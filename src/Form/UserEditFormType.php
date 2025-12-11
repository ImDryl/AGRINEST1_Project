<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class UserEditFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isAdmin = $options['is_admin'] ?? false;
        
        $builder
            ->add('username', TextType::class, [
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Enter username'
                ]
            ])
            ->add('email', EmailType::class, [
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Enter email address'
                ]
            ]);

        // Only show roles field if user is admin
        if ($isAdmin) {
            $builder->add('roles', ChoiceType::class, [
                'choices' => [
                    'Admin' => 'ROLE_ADMIN',
                    'Staff' => 'ROLE_STAFF',
                ],
                'multiple' => true,
                'expanded' => true,
                'required' => false,
                'attr' => [
                    'class' => 'role-checkboxes'
                ]
            ]);
        }

        // Password is optional for edit
        $builder->add('plainPassword', RepeatedType::class, [
            'type' => PasswordType::class,
            'required' => false,
            'mapped' => false,
            'first_options' => [
                'label' => 'New Password (leave blank to keep current)',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Enter new password (optional)'
                ],
                'constraints' => [
                    new Length([
                        'min' => 6,
                        'minMessage' => 'Your password should be at least {{ limit }} characters',
                        'max' => 4096,
                    ]),
                ],
            ],
            'second_options' => [
                'label' => 'Confirm New Password',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Confirm new password'
                ],
                'constraints' => [
                    new Length([
                        'min' => 6,
                        'minMessage' => 'Your password should be at least {{ limit }} characters',
                        'max' => 4096,
                    ]),
                ],
            ],
            'invalid_message' => 'The password fields must match.',
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'is_admin' => false,
        ]);
    }
}

