<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;
use DateTime;
use App\Models\Asset;
use Illuminate\Support\Facades\Redis;

class ExtractTextCommand extends Command
{
    protected $signature = 'extract:text';
    protected $description = 'Extract text from a website';

    public function handle()
    {
        $actions = Asset::select('code', 'asset_type')
        ->distinct()
        ->get();
        $all_content = [];
        foreach ($actions as $action) {
            $type = $action->asset_type == "fii" ? "fundos-imobiliarios" : "acoes";
            $url = "https://statusinvest.com.br/$type/$action->code"; // URL do site que você deseja extrair
            print($url);
            $client = new Client([
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
                ],
            ]);
            $response = $client->request('GET', $url);
            $html = $response->getBody()->getContents();

            $crawler = new Crawler($html);
            $content = [];
            $css_route = $action->asset_type == "fii" ? '.list-content tbody tr' : '.list-content div:nth-child(2) table tbody tr';
            
            $crawler->filter($css_route)->each(function ($node) use (&$content) {  
                $node->filter('i')->each(function (Crawler $iNode) {
                    $iNode->getNode(0)->parentNode->removeChild($iNode->getNode(0));
                });
                
                $text = strip_tags($node->text());

                // Extrai a data e o valor após "Rendimento"
                $matches = explode(" ", $text);
                if (count($matches) === 5) {
                    $matches = [
                        $matches[1] = $matches[0] . " " .$matches[1],
                        $matches[2],
                        $matches[3],
                        $matches[4]
                    ];
                }
                if (count($matches) === 4) {
                    $firstDate = $matches[1];
                    $formattedDate = DateTime::createFromFormat('d/m/Y', $firstDate)->format('Y-m-d');
                    $value = $matches[2];
                    $content[$formattedDate][] = $this->formatTextInJson($matches);
                    
                }
            });
        
            // Converter array para JSON
            $all_content[$action->code] = $content; 
        }
        print(json_encode($all_content));
        Redis::del("dividends");
        Redis::sadd("dividends", json_encode($all_content));
    }

    protected function formatTextInJson($fields) {
        foreach ($fields as $key => $field) {
            if (strpos($field, "/")) {
                $fields[$key] = DateTime::createFromFormat('d/m/Y', $field)->format('Y-m-d');
                continue;
            }
        }
        if ($fields[0] == "JCP") {
            $val = (float) str_replace(",", ".", $fields[3]);
            $fields[3] = $val - (($val * 15)/100);
        }
        return $fields;
    }
}
