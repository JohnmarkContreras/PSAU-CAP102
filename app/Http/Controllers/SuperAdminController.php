<?php

namespace App\Http\Controllers;

use App\User;
use App\Http\Requests\CreateAccountRequest;
use App\Services\UserAccountService;
use App\Services\CarbonTrackingService;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
class SuperAdminController extends Controller
{
    private $accountService;
    private $carbonService;

    public function __construct(
        UserAccountService $accountService,
        CarbonTrackingService $carbonService
    ) {
        $this->accountService = $accountService;
        $this->carbonService = $carbonService;
    }

    public function farmData()
    {
        return view('pages.farm-data', ['role' => 'superadmin']);
    }

    public function analytics()
    {
        $chartData = $this->carbonService->generateChartData();
        
        return view('pages.analytics', [
            'role' => 'superadmin',
            'chartData' => $chartData
        ]);
    }

    public function harvestManagement()
    {
        return view('pages.harvest-management', ['role' => 'superadmin']);
    }

    public function accounts()
    {
        $users = \App\User::all();
                
        return view('pages.accounts', compact('users'));
    }

    public function deleteAccount($id)
    {
        if (!$this->accountService->canDeleteUser($id)) {
            return redirect()->back()->withErrors(['error' => 'You cannot delete your own account.']);
        }

        $this->accountService->deleteUser($id);
        
        return redirect()->back()->with('success', 'User deleted successfully.');
    }

    public function createAccount()
    {
        return view('superadmin.create-account', ['role' => 'superadmin']);
    }

    public function storeAccount(CreateAccountRequest $request)
    {
        $this->accountService->createUser($request->validated());
        
        return redirect()->route('create.account')->with('success', 'User account created successfully.');
    }
    //new blade
    public function treeData()
    {
        return view('superadmin.tree_data');
    }

   
    public function getTreeDataJson(Request $request)
    {
        if ($request->ajax()) {
            $data = TreeData::with(['treeCode.treeType', 'treeCode.treeImage'])
                ->select('tree_data.*');

            return DataTables::of($data)
                ->addColumn('tree_code', function ($row) {
                    return $row->treeCode->code ?? 'N/A';
                })
                ->addColumn('tree_type', function ($row) {
                    return optional($row->treeCode->treeType)->name ?? 'N/A';
                })
                ->editColumn('planted_year_only', function ($row) {
                    return $row->planted_year_only ? 'Yes' : 'No';
                })
                ->editColumn('planted_at', function ($row) {
                    return $row->planted_at ? $row->planted_at->format('Y-m-d') : '';
                })
                ->editColumn('dbh', function ($row) {
                    return $row->dbh ?? '';
                })
                ->editColumn('height', function ($row) {
                    return $row->height ?? '';
                })
                ->editColumn('age', function ($row) {
                    return $row->age ?? '';
                })
                ->editColumn('stem_diameter', function ($row) {
                    return $row->stem_diameter ?? '';
                })
                ->editColumn('canopy_diameter', function ($row) {
                    return $row->canopy_diameter ?? '';
                })
                ->editColumn('estimated_biomass_kg', function ($row) {
                    return $row->estimated_biomass_kg ?? '';
                })
                ->editColumn('carbon_stock_kg', function ($row) {
                    return $row->carbon_stock_kg ?? '';
                })
                ->editColumn('annual_sequestration_kgco2', function ($row) {
                    return $row->annual_sequestration_kgco2 ?? '';
                })
                ->editColumn('harvests', function ($row) {
                    return $row->harvests ?? '';
                })
                ->rawColumns([])
                ->make(true);
        }
    }

    public function updateTreeData(Request $request, $id)
    {
        try {
            $treeData = TreeData::findOrFail($id);
            
            $field = $request->input('field');
            $value = $request->input('value');

            // Handle empty string for nullable fields
            if ($value === '' || $value === null) {
                $value = null;
            }

            // Validate based on field type
            $rules = [
                'tree_code_id' => 'required|exists:tree_code,id',
                'dbh' => 'nullable|numeric|min:0|max:999999.99',
                'height' => 'nullable|numeric|min:0|max:999999.99',
                'age' => 'nullable|integer|min:0',
                'planted_at' => 'nullable|date',
                'planted_year_only' => 'required|boolean',
                'stem_diameter' => 'nullable|numeric|min:0|max:999999.99',
                'canopy_diameter' => 'nullable|numeric|min:0|max:999999.99',
                'estimated_biomass_kg' => 'nullable|numeric|min:0|max:999999.99',
                'carbon_stock_kg' => 'nullable|numeric|min:0|max:999999.99',
                'annual_sequestration_kgco2' => 'nullable|numeric|min:0|max:999999.99',
                'harvests' => 'nullable|integer|min:0',
            ];

            $validator = \Validator::make(
                [$field => $value],
                [$field => $rules[$field] ?? 'nullable']
            );

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }

            // Convert boolean strings for planted_year_only
            if ($field === 'planted_year_only') {
                $value = filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
            }

            // Update the field
            $treeData->$field = $value;
            $treeData->save();

            // Prepare display value
            $displayValue = $treeData->$field;
            if ($field === 'planted_year_only') {
                $displayValue = $treeData->$field ? 'Yes' : 'No';
            } elseif ($field === 'planted_at' && $treeData->$field) {
                $displayValue = $treeData->planted_at->format('Y-m-d');
            }

            return response()->json([
                'success' => true,
                'message' => 'Field updated successfully',
                'value' => $displayValue
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating field: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getTreeCodes()
    {
        try {
            $treeCodes = TreeCode::with('treeType')
                ->orderBy('code')
                ->get()
                ->map(function ($treeCode) {
                    return [
                        'id' => $treeCode->id,
                        'code' => $treeCode->code,
                        'display' => $treeCode->code . ($treeCode->treeType ? ' - ' . $treeCode->treeType->name : '')
                    ];
                });

            return response()->json($treeCodes);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error loading tree codes'
            ], 500);
        }
    }
}