<?php

namespace App\Form;

use App\Entity\ServiceQrLink;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Vich\UploaderBundle\Form\Type\VichImageType;

final class ServiceQrLinkType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, ['label' => 'Название'])
            ->add('description', TextareaType::class, ['label' => 'Описание', 'required' => false])
            ->add('file', VichImageType::class, ['label' => 'QR-код', 'required' => false, 'allow_delete' => true, 'download_uri' => true])
            ->add('priority', IntegerType::class, ['label' => 'Порядок отображения']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => ServiceQrLink::class]);
    }
}
