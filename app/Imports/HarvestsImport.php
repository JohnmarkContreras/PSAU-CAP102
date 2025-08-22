<?php

namespace App\Imports;

use App\Harvest;
use App\Tree;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;

class HarvestsImport implements ToModel, WithHeadingRow, SkipsEmptyRows
{
    // Accepts either "tree_code" (TM001) or legacy "tree_id" (1) and normalizes to TMxxx
    protected function normalizeTreeCode($value)
    {
        $v = trim((string) $value);
        if (preg_match('/^TM\d+$/i', $v)) {
            return strtoupper($v);
        }
        if (ctype_digit($v)) {
            return 'TM' . str_pad($v, 3, '0', STR_PAD_LEFT);
        }
        // try lookup by existing trees table (maybe name column)
        $tree = Tree::where('code', $v)->first();
        return $tree ? $tree->code : null;
    }

    public function model(array $row)
    {
        
        $treeCode = $this->normalizeTreeCode($row['code'] ?? $row['id'] ?? null);
        if (!$treeCode) return null; // skip if cannot resolve

        // Optional: ensure tree exists
        $tree = Tree::where('code', $treeCode)->first();
        if (!Tree::where('code', $treeCode)->exists()) return null;

        return new Harvest([
            'tree_id'           => $tree->id,
            'code'         => $treeCode,
            'harvest_date'      => $row['harvest_date'],
            'harvest_weight_kg' => $row['harvest_weight_kg'] ?? $row['quantity'] ?? null,
            'quality'           => $row['quality'] ?? null,
            'notes'             => $row['notes'] ?? null,
        ]);
    }
}
