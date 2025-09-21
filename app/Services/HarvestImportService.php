<?php

namespace App\Services;

use App\Imports\HarvestsImport;
use Maatwebsite\Excel\Facades\Excel;

class HarvestImportService
{
    public function import($file)
    {
        Excel::import(new HarvestsImport, $file);
    }
}