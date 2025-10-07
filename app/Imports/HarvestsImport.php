<?php

namespace App\Imports;

use App\Harvest;
use App\TreeCode;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Carbon\Carbon;

class HarvestsImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        // Normalize and clean the tree code (e.g. "SOUR101", "Sweet202", "semi_sweet303")
        $rawCode = trim($row['code'] ?? $row['tree_code'] ?? '');
        if (empty($rawCode)) {
            throw new \Exception("Missing tree code in row: " . json_encode($row));
        }

        // Standardize format: uppercase and remove spaces (e.g. "Sweet 101" → "SWEET101")
        $treeCode = strtoupper(str_replace(' ', '', $rawCode));

        // Ensure tree code exists in database
        $tree = TreeCode::whereRaw('UPPER(code) = ?', [$treeCode])->first();
        if (!$tree) {
            throw new \Exception("Tree with code {$treeCode} not found");
        }

        // Parse and normalize the date (from DD/MM/YYYY)
        $harvestDate = $this->transformDate($row['harvest_date'] ?? $row['date'] ?? null);
        if (!$harvestDate) {
            throw new \Exception("Invalid or missing harvest date for {$treeCode}");
        }

        // Check if harvest already exists (same tree and date)
        $existing = Harvest::where('code', $tree->code)
            ->whereDate('harvest_date', $harvestDate)
            ->first();

        if ($existing) {
            // Skip duplicates silently
            return null;
        }

        // Create new Harvest record
        return new Harvest([
            'code' => $tree->code,
            'harvest_date' => $harvestDate,
            'harvest_weight_kg' => $row['weight'] ?? $row['harvest_weight_kg'] ?? null,
            'quality' => strtoupper(trim($row['quality'] ?? '')), // optional (A, B, C)
            'notes' => $row['notes'] ?? null, // optional
        ]);
    }

    /**
     * Convert Excel or text date into Y-m-d format
     */
    private function transformDate($value)
    {
        if (empty($value)) return null;

        try {
            if (is_numeric($value)) {
                return Date::excelToDateTimeObject($value)->format('Y-m-d');
            }

            // Handle common date formats (e.g. "10/03/2025", "03-10-2025", "2025-10-03")
            $value = str_replace('/', '-', $value);
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }
}
