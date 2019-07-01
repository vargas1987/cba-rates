<?php

namespace CbrRates\Command;

use CbrRates\AbstractCommand;
use CbrRates\Entity\BillingCurrency;
use CbrRates\Entity\BillingCurrencyRate;
use CbrRates\Repository\BillingCurrencyRateRepository;
use GuzzleHttp\Exception\ClientException;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class GetCurrenciesRatesCommand
 * @package CbrRates\Command
 */
class GetCurrenciesRatesCommand extends AbstractCommand
{
    /**
     * @return void
     */
    protected function configure()
    {
        $date = new \DateTime();

        $this
            ->setName('currency:get-rates')
            ->setDescription('Загрузка котировок валют')
            ->setDefinition(
                new InputDefinition([
                    new InputOption(
                        'date',
                        'd',
                        InputOption::VALUE_OPTIONAL,
                        'Дата (ГГГГ-ММ-ДД)',
                        $date->format('Y-m-d')
                    ),
                ])
            )
        ;
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $date = new \DateTime($input->getOption('date'));

        $output->writeln('GETTING CURRENCIES RATES');
        $output->writeln('============================');

        if (!$date) {
            $output->writeln('Wrong date format.');
            $output->writeln('END');

            return;
        }

        $output->writeln('Date: '.$date->format('Y-m-d'));

        try {
            $currencies = $this->getCbrService()->getCurrencies();
            $rates      = $this->getCbrService()->getRates($date);
        } catch (ClientException $exception) {
            $this->getLogger()->error('Client does not available', ['exception' => $exception]);
        } catch (\Exception $exception) {
            $output->writeln('An unexpected error has been occurred.');
            $output->writeln('END');
            $this->getLogger()->error($exception->getMessage(), ['exception' => $exception]);

            return;
        }

        $homeCurrency = $this->getEm()->getRepository('CbrRates:BillingCurrency')->findOneBy([
            'charCode' => BillingCurrency::CODE_RUB,
        ]);

        if (!$homeCurrency) {
            $homeCurrency = new BillingCurrency();

            $homeCurrency
                ->setCharCode('RUB')
                ->setNumCode(643)
                ->setName('Российский рубль')
            ;

            $this->getEm()->persist($homeCurrency);

            $output->writeln('Currency RUB added.');
        }
        /** @var BillingCurrencyRateRepository $currencyRateRepo */
        $currencyRateRepo = $this->getEm()->getRepository('CbrRates:BillingCurrencyRate');

        $this->getTransactionService()->beginTransaction();
        foreach ($rates as $iterator => $rate) {
            $charCode = $rate['CharCode'];

            $currency = $this->getEm()->getRepository('CbrRates:BillingCurrency')->findOneBy([
                'charCode' => $charCode,
            ]);

            if (!$currency) {
                $currency = $this->addCurrency($charCode, $currencies);
                $output->writeln('Currency '.$charCode.' added.');
            }

            if ($currency) {
                $rateValue = $currencyRateRepo->getRate($homeCurrency, $currency, $date);

                if ($rateValue) {
                    $output->writeln('Rate for RUB / '.$charCode.' already exists.');
                } else {
                    $rateEntity = new BillingCurrencyRate();

                    $nominal = $this->getInversedRateNominal($rate['Nominal'], (float) str_replace(',', '.', $rate['Value']));
                    $value   = $this->getInversedRateValue($rate['Nominal'], (float) str_replace(',', '.', $rate['Value']));

                    $rateEntity
                        ->setCurrencyFrom($homeCurrency)
                        ->setCurrencyTo($currency)
                        ->setDate($date)
                        ->setNominal($nominal)
                        ->setValue($value)
                    ;

                    $this->getEm()->persist($rateEntity);
                    $output->writeln('Rate for RUB / '.$charCode.' added: '.$rateEntity->getNormalizedValue());
                }

                $rateValue = $currencyRateRepo->getRate($currency, $homeCurrency, $date);

                if ($rateValue) {
                    $output->writeln('Rate for '.$charCode.' / RUB already exists.');
                } else {
                    $rateEntity = new BillingCurrencyRate();

                    $rateEntity
                        ->setCurrencyFrom($currency)
                        ->setCurrencyTo($homeCurrency)
                        ->setDate($date)
                        ->setNominal($rate['Nominal'])
                        ->setValue((float) str_replace(',', '.', $rate['Value']))
                    ;

                    $this->getEm()->persist($rateEntity);
                    $output->writeln('Rate for '.$charCode.' / RUB added: '.$rateEntity->getNormalizedValue());
                }
            } else {
                $output->writeln('Currency '.$charCode.' not detected.');
            }

            if ($iterator%20 === 0) {
                try {
                    $this->getEm()->flush();
                    $this->getTransactionService()->commit();
                    $this->getTransactionService()->beginTransaction();
                } catch (\Exception $exception) {
                    $this->getTransactionService()->rollback();
                    $output->writeln('An unexpected error has been occurred.');
                    $output->writeln('CONTINUE...');
                    $this->getLogger()->error($exception->getMessage(), ['exception' => $exception]);
                }
            }
        }

        try {
            $this->getEm()->flush();
            $this->getTransactionService()->commit();
        } catch (\Exception $exception) {
            $this->getTransactionService()->rollback();
            $output->writeln('An unexpected error has been occurred.');
            $this->getLogger()->error($exception->getMessage(), ['exception' => $exception]);
        }
        $output->writeln('END');
    }

    /**
     * @param string $charCode
     * @param array  $currencies
     *
     * @return array
     */
    private function getCurrencyData($charCode, $currencies)
    {
        $currencies = array_filter($currencies, function ($item) use ($charCode) {
            return $item['ISO_Char_Code'] == $charCode;
        });

        return count($currencies) ? reset($currencies) : null;
    }

    /**
     * @param string $charCode
     * @param array  $currencies
     * @return BillingCurrency|null
     */
    private function addCurrency(string $charCode, array $currencies)
    {
        $currencyData = $this->getCurrencyData($charCode, $currencies);

        if (!$currencyData) {
            return null;
        }

        $currency = new BillingCurrency();

        $currency
            ->setCharCode($currencyData['ISO_Char_Code'])
            ->setNumCode($currencyData['ISO_Num_Code'])
            ->setName($currencyData['Name'])
        ;

        $this->getEm()->persist($currency);

        return $currency;

    }

    /**
     * @param integer $nominal
     * @param float   $value
     *
     * @return integer
     */
    private function getInversedRateNominal($nominal, $value)
    {
        while ($nominal > 1) {
            $nominal = $nominal / 10;
            $value = $value / 10;
        }

        $value = 1 / $value;

        while ($value < 10) {
            $nominal = $nominal * 10;
            $value = $value * 10;
        }

        return (integer) $nominal;
    }

    /**
     * @param integer $nominal
     * @param float   $value
     *
     * @return float
     */
    private function getInversedRateValue($nominal, $value)
    {
        while ($nominal > 1) {
            $nominal = $nominal / 10;
            $value = $value / 10;
        }

        $value = 1 / $value;

        while ($value < 10) {
            $nominal = $nominal * 10;
            $value = $value * 10;
        }

        return (float) $value;
    }
}
