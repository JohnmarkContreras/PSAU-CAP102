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
        // Normalize tree code (e.g., TM001)
        $treeCode = strtoupper(trim($row['code'] ?? $row['tree_code'] ?? ''));

        // Make sure tree exists
        $tree = Tree::where('code', $treeCode)->first();
        if (!$tree) {
            throw new \Exception("Tree with code {$treeCode} not found");
        }

        // Prevent duplicate harvest (same tree + date)
        $existing = Harvest::where('code', $treeCode)
            ->whereDate('harvest_date', $row['harvest_date'])
            ->first();

        if ($existing) {
            return null; // skip duplicates
        }

        return new Harvest([
        'code' => strtoupper(trim($row['code'])),
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
