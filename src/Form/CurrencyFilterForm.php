<?php
namespace CbrRates\Form;

use CbrRates\Entity\BillingCurrency;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Choice;

/**
 * Class CurrencyFilterForm
 */
class CurrencyFilterForm extends AbstractType
{
    /** @var  EntityManagerInterface */
    protected $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     * @return void
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('currency', EntityType::class, [
                'required' => false,
                'label' => false,
                'class' => BillingCurrency::class,
                'choice_value' => 'charCode',
                'choice_label' => function (BillingCurrency $currency) {
                    return $currency->getName();
                },
                'data' => $this->em->getReference(BillingCurrency::class, BillingCurrency::CODE_EUR),
            ])
            ->add('rateLowerDate', DateType::class, [
                'required' => false,
                'label' => false,
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'html5' => false,
                'attr' => [
                    'data-behaviour' => 'datepicker',
                    'placeholder' => 'Начиная с',
                ],
            ])
            ->add('rateUpperDate', DateType::class, [
                'required' => false,
                'label' => false,
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'html5' => false,
                'attr' => [
                    'data-behaviour' => 'datepicker',
                    'placeholder' => 'Заканчивая',
                ],
            ])
            ->add('currencySort', HiddenType::class, [
                'required' => false,
                'label' => false,
                'constraints' => [
                    new Choice([
                        'choices' => [
                            'DESC',
                            'ASC',
                        ],
                    ]),
                ],
                'attr' => [
                    'data-behaviour' => 'currencySort',
                ],
            ])
            ->add('rateDateSort', HiddenType::class, [
                'required' => false,
                'label' => false,
                'constraints' => [
                    new Choice([
                        'choices' => [
                            'DESC',
                            'ASC',
                        ],
                    ]),
                ],
                'attr' => [
                    'data-behaviour' => 'rateDateSort',
                ],
            ])
        ;
    }
}
