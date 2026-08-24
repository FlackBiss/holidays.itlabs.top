<?php

namespace App\Form;

use App\Entity\MapPlacePhoto;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Vich\UploaderBundle\Form\Type\VichImageType;

final class MapPlacePhotoType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('file', VichImageType::class, [
                'label' => 'Фотография',
                'required' => false,
                'allow_delete' => true,
                'download_uri' => true,
                'help' => 'JPG, PNG, WEBP или SVG до 25 МБ.',
            ])
            ->add('priority', IntegerType::class, [
                'label' => 'Порядок показа',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => MapPlacePhoto::class]);
    }
}
