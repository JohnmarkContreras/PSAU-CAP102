<?php

namespace App\Imports;

use App\Harvest;
use App\Tree;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Illuminate\Support\Str;

class HarvestsImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        // Normalize tree code: force 'Sl' prefix casing (Sl00-00 / SL10-10 -> Sl10-10)
        $raw = trim($row['code'] ?? $row['tree_code'] ?? '');
        $treeCode = preg_replace_callback('/^([A-Za-z]{2})(.*)$/', function ($m) {
            $prefix = $m[1];
            $rest = $m[2];
            // Force prefix to 'Sl' specifically
            return 'Sl' . $rest;
        }, $raw);
        $treeCode = trim($treeCode);

        // Make sure tree exists
        $tree = \App\TreeCode::whereRaw('LOWER(code) = ?', [mb_strtolower($treeCode)])->first();
        if (!$tree) {
            throw new \Exception("Tree with code {$treeCode} not found");
        }

        // Prevent duplicate harvest (same tree + date)
        $existing = Harvest::where('code', $tree->code)
            ->whereDate('harvest_date', $row['harvest_date'])
            ->first();

        if ($existing) {
            return null; // skip duplicates
        }

        return new Harvest([
        'code' => $tree->code,
        'harvest_date' => $this->transformDate($row['harvest_date']),
        'harvest_weight_kg' => $row['harvest_weight_kg'],
        'quality' => $row['quality'] ?? null,
        'notes' => $row['notes'] ?? null,
    ]);
    }
    private function transformDate($value)
    {
        try {
            if (is_numeric($value)) {
                return Date::excelToDateTimeObject($value)->format('Y-m-d');
            } else {
                return \Carbon\Carbon::parse($value)->format('Y-m-d');
            }
        } catch (\Exception $e) {
            return null; // or throw error if you prefer
        }
    }
}
