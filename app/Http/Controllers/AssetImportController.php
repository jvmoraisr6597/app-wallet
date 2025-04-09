<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Asset;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class AssetImportController extends Controller
{
    public function import(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|mimes:csv,txt'
        ]);

        $file = $request->file('csv_file');
        $data = array_map('str_getcsv', file($file));

        if (empty($data) || count($data) < 2) {
            return back()->withErrors(['csv_file' => 'Arquivo CSV está vazio ou inválido.']);
        }

        $header = array_map('trim', $data[0]);
        unset($data[0]); // remove header

        $inserted = 0;
        foreach ($data as $index => $row) {
            // Pula linhas vazias ou com número de colunas incorreto
            if (count(array_filter($row)) === 0 || count($row) !== count($header)) {
                continue;
            }

            $row = array_combine($header, $row);

            try {
                $originalPrice = str_replace(',', '.', $row['Valor unitário']);
                $quantity = (int) $row['Quantidade'];
                $orderType = strtoupper($row['Ação']) === 'COMPRA' ? 'buy' : 'sell';
                $assetType = strlen($row['Código']) > 5 ? 'fii' : 'action';

                Asset::create([
                    'user_id' => Auth::id() ?? 1,
                    'name' => $row['Código'],
                    'code' => $row['Código'],
                    'asset_type' => $assetType,
                    'order_type' => $orderType,
                    'original_price' => $originalPrice,
                    'current_price' => null,
                    'quantity' => $quantity,
                    'order_date' => \Carbon\Carbon::createFromFormat('d/m/Y', $row['Data'])->format('Y-m-d'),
                    'sale_date' => null,
                ]);
                $inserted++;
            } catch (\Exception $e) {
                \Log::error("Erro ao importar linha $index: " . $e->getMessage());
                continue;
            }
        }

        return redirect()->route('import-assets')->with('success', "$inserted ativos importados com sucesso!");
    }

}
