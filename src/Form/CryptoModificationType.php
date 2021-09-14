<?php

namespace App\Form;

use App\Entity\Cryptocurrency;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class CryptoModificationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', ChoiceType::class, array(
                'placeholder' => 'Sélectionner une crypto',
                'required' => true,
                'disabled' => true,
                'choices' => [
                    'Bitcoin' => 'BTC',
                    'Ethereum' => 'ETH',
                    'Cardano' => 'ADA',
                    'Tether' => 'Tether',
                    'XRP' => 'XRP',
                    'Solana' => 'SOL',
                    'Polkadot' => 'DOT',
                    'Dogecoin' => 'DOGE',
                ]
            ))
            ->add('quantity', TextType::class)
            ->add('submit', SubmitType::class)
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Cryptocurrency::class,
        ]);
    }
}
