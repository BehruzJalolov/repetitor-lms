<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Services\TelegramNotificationService;
use App\Support\Phone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LeadController extends Controller
{
    public function store(Request $request, TelegramNotificationService $telegram): RedirectResponse
    {
        $request->merge([
            'phone' => Phone::normalize($request->input('phone')),
        ]);

        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'phone' => ['required', 'string'],
            'course_id' => ['nullable', 'exists:courses,id'],
            'message' => ['nullable', 'string', 'max:300'],
        ], [
            'name.required' => 'Ismingizni kiriting.',
            'phone.required' => 'To\'g\'ri telefon raqam kiriting (+998 90 123 45 67).',
        ]);

        $lead = Lead::create($data);
        $lead->load('course');

        $courseTitle = $lead->course?->title ?? 'Umumiy konsultatsiya';
        $telegram->send(
            "📞 <b>Yangi murojaat (Bepul konsultatsiya)!</b>\n\n"
            ."👤 <b>Ism:</b> {$lead->name}\n"
            ."📱 <b>Telefon:</b> {$lead->phone}\n"
            ."📚 <b>Yo'nalish:</b> {$courseTitle}"
            .($lead->message ? "\n💬 <b>Izoh:</b> {$lead->message}" : '')
        );

        return back()->with('success', 'Murojaatingiz qabul qilindi! Tez orada mutaxassisimiz siz bilan bog\'lanadi.');
    }
}
