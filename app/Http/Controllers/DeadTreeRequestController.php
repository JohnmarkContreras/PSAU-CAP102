<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\DeadTreeRequest;
use App\DeadTree;
use App\Notifications\DeadTreeApprovalRequest;
use App\Notifications\DeadTreeApproved;
use App\Notifications\DeadTreeRejected;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\User;
use App\Tree;

class DeadTreeRequestController extends Controller
{
    public function index()
    {
        $requests = DeadTreeRequest::with('tree', 'user')->where('status', 'pending')->latest('submitted_at')->paginate(10);
        return view('trees.dead-tree-requests.index', compact('requests'));
    }

    public function show($id)
    {
        $request = DeadTreeRequest::with('tree', 'user')->findOrFail($id);
        return view('trees.dead-tree-requests.show', compact('request'));
    }

    public function create(Request $request)
    {
        $tree = Tree::where('code', $request->tree_code)->firstOrFail();
        return view('trees.dead-tree-requests.create', compact('tree'));
    }

    public function store(Request $request)
    {
        $path = $request->file('image')->store('dead_tree_images', 'public');

        $deadRequest = DeadTreeRequest::create([
            'tree_code' => $request->tree_code,
            'reason' => $request->reason,
            'image_path' => $path,
            'submitted_by' => Auth::id(),
        ]);

        User::role(['admin', 'superadmin'])->each(function ($admin) use ($deadRequest) {
            $admin->notify(new DeadTreeApprovalRequest($deadRequest));
        });

        return redirect()->back()->with('success', 'Request submitted. Awaiting admin approval.');
    }

    public function approve($id)
    {
        $request = DeadTreeRequest::findOrFail($id);

        DeadTree::create([
            'tree_code' => $request->tree_code,
            'reason' => $request->reason,
            'image_path' => $request->image_path,
        ]);

        $request->update(['status' => 'approved']);
        $request->user->notify(new DeadTreeApproved($request));

        return redirect()->back()->with('success', 'Request approved.');
    }

    public function reject(Request $request, $id)
    {
        $deadRequest = DeadTreeRequest::findOrFail($id);

        $deadRequest->update([
            'status' => 'rejected',
            'rejection_reason' => $request->reason,
        ]);

        $deadRequest->user->notify(new DeadTreeRejected($deadRequest));

        return redirect()->back()->with('info', 'Request rejected and user notified.');
    }

}
