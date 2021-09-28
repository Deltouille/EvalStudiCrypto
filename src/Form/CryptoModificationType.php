<?php

namespace App\Form;

use App\Entity\Cryptocurrency;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
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
            ->add('quantity', NumberType::class, array(
                'required' => true,
                'attr' => array(
                    'placeholder' => 'Insérez un montant a enlever (exemple : 0.01 bitcoin)',
                )
            ))
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