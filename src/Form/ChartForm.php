<?php
namespace CbrRates\Form;

use CbrRates\Entity\BillingCurrency;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class ProjectPackageChartForm
 */
class ChartForm extends AbstractType
{
    /** @var  EntityManagerInterface */
    protected $em;

    /**
     * ChartForm constructor.
     * @param EntityManagerInterface $em
     */
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
            ->add('lowerDate', DateType::class, [
                'data' => new \DateTime('first day of -2 month'),
                'required' => false,
                'widget' => 'single_text',
                'format' => 'yyyy-MM',
                'html5' => false,
                'label' => 'Начиная с',
                'attr' => [
                    'data-behaviour' => 'datepicker',
                ],
            ])
            ->add('upperDate', DateType::class, [
                'data' => new \DateTime('last day of this month'),
                'required' => false,
                'widget' => 'single_text',
                'format' => 'yyyy-MM',
                'html5' => false,
                'label' => 'Заканчивая',
                'attr' => [
                    'data-behaviour' => 'datepicker',
                ],
            ])
            ->add('currency', EntityType::class, [
                'required' => false,
                'label' => false,
                'class' => BillingCurrency::class,
                'choice_value' => 'charCode',
                'choice_label' => function (BillingCurrency $currency) {
                    return $currency->getName();
                },
                'data' => $this->em->getReference(BillingCurrency::class, BillingCurrency::CODE_EUR),
            ]);

        $builder->addModelTransformer(new CallbackTransformer(
            function ($data) {
                return $data;
            },
            function ($data) {
                /** @var \DateTime $lowerDate */
                if ($lowerDate = $data['lowerDate']) {
                    $data['lowerDate'] = $lowerDate->modify('first day of this month 00:00:00');
                }
                /** @var \DateTime $upperDate */
                if ($upperDate = $data['upperDate']) {
                    $data['upperDate'] = $upperDate->modify('last day of this month 23:59:59');
                }

                return $data;
            }
        ));
    }
}
