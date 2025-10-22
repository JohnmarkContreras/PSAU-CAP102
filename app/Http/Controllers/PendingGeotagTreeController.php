<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\PendingGeotagTree;
use App\Services\GeotagApprovalService;
use App\User;
use App\Notifications\GeotagStatusChanged;
use App\Services\CarbonTrackingService;
use App\Helpers\ActivityLogHelper;
use Illuminate\Support\Facades\Auth;
class PendingGeotagTreeController extends Controller
{
    protected $approvalService;
    protected $carbonService;

    public function __construct(GeotagApprovalService $approvalService, CarbonTrackingService $carbonService)
    {
        $this->approvalService = $approvalService;
        $this->carbonService   = $carbonService;
    }


    public function store(Request $request)
    {
        try {
            logger()->info('Mobile Request Received:', [
                'has_file' => $request->hasFile('filename'),
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
            ]);
            
            $validated = $request->validate([
                'filename' => 'required|image|max:10240', // 10MB max
                'latitude' => 'required|numeric',
                'longitude' => 'required|numeric',
                'code' => 'required|string|unique:pending_geotag_trees,code',
                'dbh' => 'nullable|numeric',
                'height' => 'nullable|numeric',
                'age' => 'nullable|numeric',
                'canopy_diameter' => 'nullable|numeric',
                'tree_type_id' => 'required|exists:tree_types,id',
                'taken_at' => 'nullable|date',
            ]);

            $path = $request->file('filename')->store('pending_tree_images', 'public');

            $pending = PendingGeotagTree::create([
                'image_path' => $path,
                'latitude' => $validated['latitude'],
                'longitude' => $validated['longitude'],
                'code' => $validated['code'],
                'dbh' => $validated['dbh'] ?? null,
                'height' => $validated['height'] ?? null,
                'age' => $validated['age'] ?? null,
                'canopy_diameter' => $validated['canopy_diameter'] ?? null,
                'taken_at' => $validated['taken_at'] ?? null,
                'tree_type_id' => $validated['tree_type_id'],
                'user_id' => auth()->id(),
            ]);

            // Notify admins and superadmins
            $admins = User::query()->role(['admin', 'superadmin'])->get();
            if ($admins->count()) {
                foreach ($admins as $admin) {
                    $admin->notify(new GeotagStatusChanged('pending', $pending->id));
                }
            }

            return redirect()->back()
                ->with('success', '🌳 Tree added to pending trees successfully!');

        } catch (\Illuminate\Validation\ValidationException $e) {
            logger()->error('Validation failed:', $e->errors());
            
            $errors = $e->errors();
            
            // Handle specific validation errors
            if (isset($errors['code']) && strpos($errors['code'][0], 'unique') !== false) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', '⚠️ Tree Code "' . $request->code . '" already exists. Please use a different code.');
            }
            
            // Build friendly error message for other validation errors
            $errorMessages = [];
            foreach ($errors as $field => $messages) {
                $fieldLabel = ucfirst(str_replace('_', ' ', $field));
                $errorMessages[] = $fieldLabel . ': ' . $messages[0];
            }
            
            return redirect()->back()
                ->withInput()
                ->with('error', '❌ ' . implode(' | ', $errorMessages));

        } catch (\Illuminate\Database\QueryException $e) {
            logger()->error('Database error: ' . $e->getMessage());
            
            // Handle duplicate entry error
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', '⚠️ Tree Code "' . $request->code . '" already exists. Please use a different code.');
            }
            
            return redirect()->back()
                ->withInput()
                ->with('error', '❌ Database error occurred. Please try again.');

        } catch (\Exception $e) {
            logger()->error('Store failed: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            
            return redirect()->back()
                ->withInput()
                ->with('error', '❌ An unexpected error occurred. Please try again.');
        }
    }

    public function index(Request $request)
    {
        $pending = PendingGeotagTree::where('status', 'pending')->get();

        if ($request->ajax()) {
            return view('partials.table', compact('pending'))->render();
        }
        return view('pending_geotags.index', compact('pending'));
    }

// In your Controller
    public function approve($id)
    {
        try {
            $result = $this->approvalService->approve($id);

            if (is_array($result) && isset($result['duplicate'])) {
                return redirect()->back()->with('warning', "Tree code '{$result['code']}' already exists!");
            }

            if ($result === null) {
                return redirect()->back()->with('info', 'Geotag approved, but no tree data was found for this code.');
            }

            return redirect()->back()->with('success', 'Geotag approved successfully!');

        } catch (\Exception $e) {
            \Log::error('Approval Error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            ActivityLogHelper::log('User approved geotag', ['fields' => $request->only(['name', 'email'])], 'user_actions', $user);
            return redirect()->back()->with('error', $e->getMessage());
        }
    }


    // Reject a geotag with optional reason
    public function reject(Request $request, $id)
    {
        $request->validate([
            'rejection_reason' => 'nullable|string|max:255',
        ]);
        $user = Auth::user();
        $this->approvalService->rejectGeotag($id, $request->input('rejection_reason'));
        ActivityLogHelper::log('User rejected geotag', ['fields' => $request->only(['name', 'email'])], 'user_actions', $user);
        return redirect()->back()->with('status', 'Geotag rejected successfully.');
    }
}
