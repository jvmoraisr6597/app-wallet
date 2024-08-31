<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Asset;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use Illuminate\Support\Facades\Redis;

class AssetController extends Controller
{
    // Método para listar todos os ativos
    public function index()
    {
        $assets = Asset::all();
        return response()->json($assets);
    }

    // Método para inserir um novo ativo
    public function store(Request $request)
    {
        $asset = Asset::create($request->all());
        return response()->json($asset, 201);
    }

    // Método para exibir um ativo específico
    public function show($id)
    {
        $asset = Asset::findOrFail($id);
        return response()->json($asset);
    }

    // Método para atualizar um ativo
    public function update(Request $request, $id)
    {
        $asset = Asset::findOrFail($id);
        $asset->update($request->all());
        return response()->json($asset, 200);
    }

    // Método para excluir um ativo
    public function destroy($id)
    {
        Asset::findOrFail($id)->delete();
        return response()->json(null, 204);
    }

    public function calcularDadosUsuario($userId)
    {
        // Pegar todos os ativos do usuário com o ID fornecido
        $ativosDoUsuario = Asset::where('user_id', $userId)->orderBy('order_date')->get();
        // Inicializar um array para armazenar os resultados
        $resultado = [
            "resume" => [
                "total_gain" => 0,
                "total_invest" => 0,
                "total_dividend" => 0
            ]
        ];

        // Iterar sobre os ativos do usuário
        foreach ($ativosDoUsuario as $ativo) {
            $codigo = $ativo->code;

            // Se o código ainda não estiver presente no array resultado, inicialize com os dados do ativo
            if (!isset($resultado[$codigo])) {
                // Verificar se o current_price já está definido
                if (!isset($ativo->current_price) || $ativo->updated_at < Carbon::now()->subHour()) {
                    // Fazer a solicitação para obter o preço de mercado
                    $response = Http::get("https://brapi.dev/api/quote/{$codigo}?token=5fScJMYeRVYD9LMB55gX7e");
                    // Verificar se a solicitação foi bem-sucedida
                    if ($response->successful()) {
                        $data = $response->json();
                        $data = $data['results'][0];
                        // Verificar se o campo 'regularMarketPrice' está presente nos dados
                        if (isset($data['regularMarketPrice'])) {
                            // Atualizar o campo 'current_price' do ativo com o preço de mercado
                            $ativo->current_price = $data['regularMarketPrice'];
                            $ativo->updated_at = Carbon::now();
                            // Salvar o current_price no banco
                            $ativo->save();
                        }
                    }
                }
                // Adicionar o ativo ao array resultado
                $resultado[$codigo] = $ativo;

                $dividends = round($this->calculateOneDividend($ativo->code, $ativo->order_date) * $ativo->quantity, 2);
                $resultado[$codigo]['dividends'] += $dividends;
                $gain = $this->calculateGain($ativo->original_price, $resultado[$codigo]->current_price, $ativo->quantity);
                $resultado[$codigo]['gain'] = round($resultado[$codigo]['gain'] + $gain, 2);
                $resultado['resume']['total_dividend'] += $dividends;
                $resultado['resume']['total_gain'] += $resultado[$codigo]['gain'];
                $resultado['resume']['total_invest'] += $ativo->original_price * $ativo->quantity;
            } else {
                if ($ativo->order_type == 'buy') {
                    $newPrice = $this->calculatePrice($resultado[$codigo]->original_price, $resultado[$codigo]->quantity, $ativo->original_price, $ativo->quantity);
                    $resultado[$codigo]->quantity += $ativo->quantity;
                    $resultado[$codigo]->original_price = $newPrice;
                    $gain = $this->calculateGain($ativo->original_price, $resultado[$codigo]->current_price, $ativo->quantity);
                    $resultado[$codigo]['gain'] = round($resultado[$codigo]['gain'] + $gain, 2);
                    $dividends = round($this->calculateOneDividend($ativo->code, $ativo->order_date) * $ativo->quantity, 2);
                    $resultado[$codigo]['dividends'] += $dividends;
                    $resultado['resume']['total_dividend'] += $dividends;
                    $resultado['resume']['total_gain'] += $gain;
                    $resultado['resume']['total_invest'] += $ativo->original_price * $ativo->quantity;
                } elseif ($ativo->order_type == 'sell') {
                    $resultado['resume']['total_invest'] -= $ativo->original_price * $ativo->quantity;
                    // Se for do tipo 'sell', decresça a quantidade, mas não altere o preço original
                    $resultado[$codigo]->quantity -= $ativo->quantity;
                }
            }
        }

        //$resultado = $this->calculateDividends($resultado);

        $resultado['resume']['total_gain'] = round($resultado['resume']['total_gain'], 2);
        $resultado['resume']['total_invest'] = round($resultado['resume']['total_invest'], 2);
        $resultado['resume']['total_dividend'] = round($resultado['resume']['total_dividend'], 2);
        return $resultado;
    }

    public function rebalanceUserWallet($userId, Request $request)
    {
        $valorInteiro = 1000;
        $dados = $this->calcularDadosUsuario($userId);
    
        // Inicializar arrays para categorização
        $actions = [];
        $fiis = [];
    
        // Separar os dados por asset_type
        foreach ($dados as $key => $item) {
            if ($key == "resume") continue;
            if ($item['asset_type'] === 'action') {
                $actions[$key] = $item;
            } elseif ($item['asset_type'] === 'fii') {
                $fiis[$key] = $item;
            }
        }
    
        // Calcular o valor total das ações (current_price * quantity)
        $totalValorActions = array_reduce($actions, function ($carry, $action) {
            return $carry + $action['current_price'] * $action['quantity'];
        }, 0);
    
        // Calcular o valor total dos FIIs (current_price * quantity)
        $totalValorFiis = array_reduce($fiis, function ($carry, $fii) {
            return $carry + $fii['current_price'] * $fii['quantity'];
        }, 0);
    
        // Calcular a pontuação para actions e fiis
        $this->calcularPontuacao($actions);
        $this->calcularPontuacao($fiis);
    
        // Determinar a distribuição inicial entre actions e fiis
        $totalAssets = $totalValorActions + $totalValorFiis;
        $percentualActions = $totalValorActions / $totalAssets;
        $percentualFiis = $totalValorFiis / $totalAssets;
    
        $valorParaActions = $valorInteiro * $percentualActions;
        $valorParaFiis = $valorInteiro * $percentualFiis;

        // Ordenar actions e fiis por pontuação decrescente
        uasort($actions, function ($a, $b) {
            return $b['pontuacao'] <=> $a['pontuacao'];
        });
    
        uasort($fiis, function ($a, $b) {
            return $b['pontuacao'] <=> $a['pontuacao'];
        });
    
        // Distribuir o valor alocado entre os items de actions
        $saldoActions = $valorParaActions;
        $countActions = count($actions);
        foreach ($actions as $key => $action) {
            // Calcular quanto pode ser alocado para este item
            if ($saldoActions <= 0) {
                $actions[$key]['valor_dedicado'] = 0;
                continue;
            }
    
            $pontuacaoPercent = $action['pontuacao'] / $this->factorial(count($actions));
            $actions[$key]['percent'] = $pontuacaoPercent;
            $valorParaDedicar = $valorParaActions * $pontuacaoPercent;
    
            // Verificar se ultrapassa o saldo restante disponível
            $valorParaDedicar = min($valorParaDedicar, $saldoActions);

            $valorParaDedicar = round($valorParaDedicar, 2);
    
            // Atualizar o item com o valor a ser dedicado
            $actions[$key]['valor_dedicado'] = $valorParaDedicar;
    
            // Deduzir o valor alocado deste item do saldo restante
            $saldoActions -= $valorParaDedicar;
    
            // Reduzir o número de ações restantes para alocar
            $countActions--;
        }
    
        // Distribuir o valor alocado entre os items de fiis
        $saldoFiis = $valorParaFiis;
        $countFiis = count($fiis);
        foreach ($fiis as $key => $fii) {
            // Calcular quanto pode ser alocado para este item
            if ($saldoFiis <= 0) {
                $fiis[$key]['valor_dedicado'] = 0;
                continue;
            }
    
            $pontuacaoPercent = $fii['pontuacao'] / $this->factorial(count($fiis));
            $fiis[$key]['percent'] = $this->factorial(count($fiis));
            $valorParaDedicar = $valorParaFiis * $pontuacaoPercent;
            $valorParaDedicar = round($valorParaDedicar, 2);
            $resto = fmod($valorParaDedicar, $fii['current_price']);
            if ($resto >= $fii['current_price'] / 2) {
                $valorParaDedicar += $resto;
            } else {
                $valorParaDedicar -= $resto;
            }
            
    
            // Verificar se ultrapassa o saldo restante disponível
            $valorParaDedicar = min($valorParaDedicar, $saldoFiis);
            
            $valorParaDedicar = round($valorParaDedicar, 2);

            // Atualizar o item com o valor a ser dedicado
            $fiis[$key]['valor_dedicado'] = $valorParaDedicar;
    
            // Deduzir o valor alocado deste item do saldo restante
            $saldoFiis -= $valorParaDedicar;
    
            // Reduzir o número de FIIs restantes para alocar
            $countFiis--;
        }
    
        // Montar o JSON de retorno
        $retorno = [
            'actions' => array_values($actions), // array_values para reindexar o array
            'fiis' => array_values($fiis), // array_values para reindexar o array
        ];
    
        return response()->json($retorno);
    }
    
    private function factorial($number){ 
        $factorial = 0; 
        for ($i = 1; $i <= $number; $i++){ 
        $factorial += $i; 
        } 
        return $factorial * 2; 
    } 

    private function calcularPontuacao(&$ativos)
    {
        // Ordenar os ativos por menor gain (mais negativo primeiro) e menor current_price * quantity
        uasort($ativos, function($a, $b) {
            // Primeiro critério: menor gain (mais negativo recebe maior pontuação)
            if ($a['gain'] != $b['gain']) {
                return $a['gain'] < $b['gain'] ? -1 : 1;
            } else {
                // Segundo critério: menor current_price * quantity
                $valorTotalA = $a['current_price'] * $a['quantity'];
                $valorTotalB = $b['current_price'] * $b['quantity'];
                return $valorTotalA < $valorTotalB ? -1 : 1;
            }
        });

        $i = count($ativos);

        foreach ($ativos as $ativo) {
            $ativo['pontuacao'] = $i;
            $i--;
        }

        uasort($ativos, function($a, $b) {
            $produtoA = $a['current_price'] * $a['quantity'];
            $produtoB = $b['current_price'] * $b['quantity'];
        
            if ($produtoA == $produtoB) {
                return $a['gain'] < $b['gain'] ? -1 : 1;
            }
            return ($produtoA < $produtoB) ? -1 : 1;
        });

        $j = count($ativos);

        foreach ($ativos as $ativo) {
            $ativo['pontuacao'] += $j;
            $j--;
        }
    }
    

    protected function calculatePrice($price1, $qty1, $price2, $qty2)
    {
        $totalPrice1 = $price1 * $qty1;
        $totalPrice2 = $price2 * $qty2;

        return round(($totalPrice1 + $totalPrice2)/($qty1+$qty2), 2);
    }

    protected function calculateGain($price1, $price2, $quantity) {
        $price1 = floatval($price1);
        $price2 = floatval($price2);

        // Calcular o ganho por ativo
        $gainPerAsset = $price2 - $price1;
        
        return round(($gainPerAsset) * $quantity, 2);
    }

    protected function calculateDividends($resultado) {
        $total_dividend = 0;
        foreach($resultado as $key => $value) {
            if ($key == "resume") {
                continue;
            }
            $dividend = $this->calculateOneDividend($value["code"], $value["order_date"]) * $value["quantity"];
            $resultado[$key]["dividend"] = $dividend; 
            $total_dividend += $dividend;
        }
        $resultado["resume"]["total_dividends"] = round($total_dividend, 2);
        return $resultado;
    }

    protected function calculateOneDividend($code, $order_date) {
        $dividends = json_decode(Redis::smembers("dividends")[0], true);

        if (!isset($dividends[$code])) {
            return 0.00;
        }

        $dividends = $dividends[$code];

        $total = 0;
        foreach ($dividends as $date => $entries) {
            if (strtotime($date) >= strtotime($order_date)) {
                foreach ($entries as $entry) {
                    if (strtotime($entry[2]) <= strtotime(date('Y-m-d'))) {
                        $total += (float) str_replace(",", ".", $entry[3]);
                    }
                }
            }
        }

        return round($total, 2);
    }
}
