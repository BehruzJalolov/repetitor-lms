<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SubscriptionController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->input('status', 'pending');
        abort_unless(in_array($status, ['pending', 'approved', 'rejected', 'all'], true), 404);

        $subscriptions = Subscription::with(['user', 'course'])
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        $counts = Subscription::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return view('admin.subscriptions.index', compact('subscriptions', 'status', 'counts'));
    }

    public function receipt(Subscription $subscription)
    {
        abort_unless(Storage::disk('local')->exists($subscription->receipt_image), 404);

        return Storage::disk('local')->response($subscription->receipt_image);
    }

    public function approve(Request $request, Subscription $subscription): RedirectResponse
    {
        $data = $request->validate([
            'duration' => ['required', 'in:0,1,3,6,12'],
        ]);

        $months = (int) $data['duration'];

        $subscription->update([
            'status' => 'approved',
            'admin_comment' => null,
            // 0 = cheksiz
            'expires_at' => $months > 0 ? now()->addMonths($months) : null,
        ]);

        return back()->with('success', "{$subscription->user->name} uchun \"{$subscription->course->title}\" kursi ochildi.");
    }

    public function reject(Request $request, Subscription $subscription): RedirectResponse
    {
        $data = $request->validate([
            'admin_comment' => ['required', 'string', 'min:3', 'max:500'],
        ]);

        $subscription->update([
            'status' => 'rejected',
            'admin_comment' => $data['admin_comment'],
            'expires_at' => null,
        ]);

        return back()->with('success', 'Chek rad etildi.');
    }
}
