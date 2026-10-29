<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LeadController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->input('status', 'all');
        abort_unless(in_array($status, ['all', 'new', 'contacted', 'enrolled'], true), 404);

        $leads = Lead::with('course')
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $counts = Lead::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return view('admin.leads.index', compact('leads', 'status', 'counts'));
    }

    public function updateStatus(Request $request, Lead $lead): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:new,contacted,enrolled'],
        ]);

        $lead->update(['status' => $data['status']]);

        return back()->with('success', 'Murojaat holati yangilandi.');
    }

    public function destroy(Lead $lead): RedirectResponse
    {
        $lead->delete();

        return back()->with('success', 'Murojaat o\'chirildi.');
    }
}
