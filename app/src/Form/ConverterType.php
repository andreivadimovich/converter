<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Entity\ExchangeRate;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ConverterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $data): void
    {
        $choiseOptions = ['class' => ExchangeRate::class, 'choice_label' => 'code', 'choice_value' => 'value', 'mapped' => false,
            'row_attr' => ['style' => 'width: 200px']];
        $builder
            ->add('from', EntityType::class, $choiseOptions)
            ->add('to', EntityType::class, $choiseOptions)
            ->add('amount', null, ['required' => true, 'mapped' => false, 'label' => false,
                'attr' => ['placeholder' => 'amount', 'autocomplete' => 'off'], 'row_attr' => ['style' => 'width: 200px']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ExchangeRate::class,
        ]);
    }
}
