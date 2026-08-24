<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

final class PublicTransportScheduleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('stopName', TextType::class, ['label' => 'Остановка отправления'])
            ->add('days', TextType::class, ['label' => 'Дни отправления', 'help' => 'Например: будни, выходные или ежедневно'])
            ->add('times', CollectionType::class, [
                'label' => 'Время отправления',
                'entry_type' => TextType::class,
                'entry_options' => ['label' => false, 'attr' => ['placeholder' => '06:00'], 'constraints' => [new Assert\Regex(pattern: '/^(?:[01]\d|2[0-3]):[0-5]\d$/', message: 'Введите время в формате ЧЧ:ММ.')]],
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => null]);
    }
}
