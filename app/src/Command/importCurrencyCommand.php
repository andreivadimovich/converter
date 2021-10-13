<?php

namespace App\Command;

use Doctrine\DBAL\Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use \GuzzleHttp\Client;
use App\Entity\ExchangeRate;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\CurrencyImport;

class importCurrencyCommand extends Command
{
    protected static $defaultName = 'import-currency';
    protected static $defaultDescription = 'Getting currency from data sources (xml, json)';

    /**
     * Источники данных.
     * (в 0 и 1 индексах должны находиться заданные по умолчанию значения)
     * @var array|array[]
     */
    protected CONST IMPORT_SOURCE_OPTIONS = [
        0 => [
            'type' => 'xml',
            'url' => 'https://www.ecb.europa.eu/stats/eurofxref/eurofxref-daily.xml',
            'nodeName' => 'Cube',
            'elementsName' => [
                'label' => 'currency',
                'value' => 'rate'
            ]
        ],
        1 => [
            'type' => 'json',
            'url' => 'http://api.coindesk.com/v1/bpi/historical/close.json',
            'nodeName' => 'bpi',
            'onlyLastElement' => true
        ],
    ];

    private $em;

    /**
     * @param EntityManagerInterface $em
     */
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
        parent::__construct();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $importService = new CurrencyImport();
        $io->info("Import currencies started");
        $data = $this->getData($input, $output);
        if (isset($data['error']) && !empty($data['error'])) {
            $io->error($data['error']);
            return Command::FAILURE;
        }

        $data = $importService->convertBtcToEur($data);
        $result = $importService->rebuildData($data);
        $state = $this->save($result);
        if (isset($state['insert']) && isset($state['update'])) {
            $insert = $state['insert'];
            $update = $state['update'];
            $io->success("Success! Insert: {$insert} . Update: {$update} rows.");
            return Command::SUCCESS;
        } elseif (isset($state['error'])) {
            $error = $state['error'];
            $io->error("Error: {$error}");
            return Command::FAILURE;
        }
    }


    /**
     * Получение данных из источников
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return array
     */
    private function getData(InputInterface $input, OutputInterface $output): array
    {
        $io = new SymfonyStyle($input, $output);
        $importService = new CurrencyImport();
        $result = [];
        foreach (self::IMPORT_SOURCE_OPTIONS as $key => $source) {
            $url = $source['url'];
            $data = $this->requestCreate('GET', $url);
            if (isset($data['body']) && !empty($data['body'])) {
                $io->info("Request to {$url} success, rebuild data ... ");
                if ($source['type'] === 'xml') {
                    $xml = $importService->fromXML($data['body'], $source['nodeName'], $source['elementsName']);
                    if (isset($xml['error']) && !empty($xml['error'])) {
                        $result['error'] = 'Check data source options in $importSourcesOptions[' . $key . '] element. 
                            XML error: ' . $xml['error'];
                        return $result;
                    }

                    $result[] = $xml;
                    unset($xml);
                }

                // BTC с api.coindesk.com (в USD)
                if ($source['type'] === 'json' && $source['onlyLastElement'] === true) {
                    $json = $importService->fromLastRowJSON($data['body'], $source['nodeName']);
                    if (isset($json['error']) && !empty($json['error'])) {
                        $result['error'] = 'Check data source options in $importSourcesOptions[' . $key . '] element. 
                            JSON error code = ' . $json['error'];
                        return $result;
                    }

                    $result['0'][] = $json;
                    unset($json);
                }

                // тут можно добавить условия для новых источников (результат писать в $result[])

            } else {
                $statusError = $data['status'];
                $io->error("Error! Request to {$url} failed. Status = {$statusError}");
            }
        }
        return $result ?: ['error' => 'Empty data'];
    }


    /**
     * @param string $method
     * @param string $url
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function requestCreate(string $method = 'GET', string $url): array
    {
        $result = [];
        $client = new \GuzzleHttp\Client();
        $response = $client->request($method, $url);
        $status = $response->getStatusCode();
        $body = $response->getBody()->getContents();
        if ($status == '200' && !empty($body)) {
            $result['body'] = $body;
        } else {
            $result = [
                'error' => true,
                'status' => $status,
            ];
        }

        return $result;
    }


    /**
     * Добавление/обновление записей в БД
     * @param array $data
     * @return array|int[]
     */
    private function save(array $data): array
    {
        try {
            $batchSize = 20;
            $repository = $this->em->getRepository(ExchangeRate::class);
            $counterInsert = 0;
            $counterUpdate = 0;
            $i = 0;
            foreach ($data as $code => $value) {
                $i++;
                $exist = $repository->findOneBy(['code' => $code]);
                if ($exist) {
                    $exist->setValue($value);
                    $this->em->persist($exist);
                    $counterUpdate++;
                } else {
                    $exchangeRate = new ExchangeRate();
                    $exchangeRate->setCode($code);
                    $exchangeRate->setValue($value);
                    $this->em->persist($exchangeRate);
                    $counterInsert++;
                }

                if (($i / $batchSize) == 0) {
                    $this->em->flush();
                    $this->em->clear();
                }
            }

            $this->em->flush();
            $this->em->clear();

            return [
                'insert' => $counterInsert,
                'update' => $counterUpdate
            ];
        } catch (Exception $e) {
            return [
                'error' => $e->getMessage()
            ];
        }
    }
}
