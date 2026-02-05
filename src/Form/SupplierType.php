<?php

namespace App\Form;

use App\Entity\Supplier;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class SupplierType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'attr' => [
                    'maxlength' => 255,
                    'placeholder' => 'Supplier name',
                ],
            ])
            ->add('email', EmailType::class, [
                'attr' => [
                    'pattern' => '^[^@\s]+@gmail\.com$',
                    'placeholder' => 'example@gmail.com',
                    'inputmode' => 'email',
                ],
            ])
            ->add('phone', TextType::class, [
                'attr' => [
                    'pattern' => '^09\\d{9}$',
                    'maxlength' => 11,
                    'inputmode' => 'numeric',
                    'placeholder' => '09XXXXXXXXX',
                ],
            ])
            ->add('address', TextType::class, [
                'attr' => [
                    'placeholder' => 'Complete address',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Supplier::class,
        ]);
    }
}
