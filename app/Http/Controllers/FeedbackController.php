<?php

namespace App\Http\Controllers;
use App\Notifications\FeedbackStatusUpdated;
use Illuminate\Http\Request;
use App\Notifications\FeedbackSubmitted;
use Spatie\Permission\Models\Role;
use App\Feedback;
use App\User;

class FeedbackController extends Controller
{
    public function create()
    {
        return view('feedback.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'type' => 'required|string',
            'rating' => 'nullable|integer|min:1|max:5',
            'message' => 'required|string|max:1000',
        ]);

        // Create the feedback
        $feedback = Feedback::create([
            'user_id' => auth()->id(),
            'type' => $request->type,
            'rating' => $request->rating,
            'message' => $request->message,
            'status' => 'pending', // optional: mark new feedback as pending
        ]);

        // Notify all admins & superadmins
        $admins = User::role(['admin', 'superadmin'])->get();
        foreach ($admins as $admin) {
            $admin->notify(new FeedbackSubmitted($feedback));
        }

        return redirect()->back()->with('success', 'Thank you for your feedback!');
    }


    public function index()
    {
        $feedbacks = Feedback::latest()->get();
        return view('feedback.index', compact('feedbacks'));
    }

    public function updateStatus(Request $request, Feedback $feedback)
    {
        $request->validate([
            'status' => 'required|string|in:New,In Review,Resolved',
        ]);

        $feedback->update(['status' => $request->status]);

        if ($feedback->user) {
            $feedback->user->notify(new FeedbackStatusUpdated($feedback));
        }

        return redirect()->back()->with('success', 'Feedback status updated.');
    }

}
