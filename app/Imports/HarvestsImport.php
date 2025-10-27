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
    try {
        $rawCode = trim($row['code'] ?? $row['tree_code'] ?? '');
        if (empty($rawCode)) return null;

        $treeCode = strtoupper(str_replace(' ', '', $rawCode));
        $tree = TreeCode::whereRaw('UPPER(code) = ?', [$treeCode])->first();
        if (!$tree) return null;

        $harvestDate = $this->transformDate($row['harvest_date'] ?? $row['date'] ?? null);
        if (!$harvestDate) return null;

        $existing = Harvest::where('code', $tree->code)
            ->whereDate('harvest_date', $harvestDate)
            ->first();

        if ($existing) return null;

        return new Harvest([
            'code' => $tree->code,
            'harvest_date' => $harvestDate,
            'harvest_weight_kg' => $row['weight'] ?? $row['harvest_weight_kg'] ?? null,
            'quality' => strtoupper(trim($row['quality'] ?? '')),
            'notes' => $row['notes'] ?? null,
            'created_by' => auth()->id(),
        ]);
    } catch (\Throwable $e) {
        \Log::error('Harvest import failed', [
            'row' => $row,
            'error' => $e->getMessage(),
        ]);
        return null; // skip bad row
    }
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
