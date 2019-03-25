<?php

namespace App\Form;

use Doctrine\DBAL\Types\TextType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class UserAlertForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name')
            ->add('triggerDataCenter', CheckboxType::class)
            ->add('triggerOption', CheckboxType::class)
            ->add('triggerValue', TextType::class)
            ->add('triggerHq', CheckboxType::class)
            ->add('triggerNq', CheckboxType::class)
            ->add('notifiedViaEmail', CheckboxType::class)
            ->add('notifiedViaDiscord', CheckboxType::class)
            ->add('save', SubmitType::class)
        ;
    }
}
