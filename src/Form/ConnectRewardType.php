<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

final class ConnectRewardType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('achievement', TextType::class, ['label' => 'Достижение'])
            ->add('points', IntegerType::class, [
                'label' => 'Количество баллов',
                'attr' => ['min' => 1, 'max' => 5],
                'constraints' => [new Assert\Range(min: 1, max: 5, notInRangeMessage: 'Количество баллов должно быть от 1 до 5.')],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => null]);
    }
}
