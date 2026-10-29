<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Teacher;
use App\Models\TeacherResult;
use App\Models\User;
use App\Support\Phone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TeacherController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->isTeacher()) {
            abort_unless($user->hasPermission('manage_results') && $user->teacher_id, 403);

            return redirect()->route('admin.teachers.show', $user->teacher_id);
        }

        $teachers = Teacher::with('account')
            ->withCount(['results', 'courses'])
            ->orderBy('position')
            ->orderByDesc('id')
            ->paginate(12);

        return view('admin.teachers.index', compact('teachers'));
    }

    public function create(Request $request)
    {
        abort_unless($request->user()->isSuperAdmin(), 403);

        return view('admin.teachers.form', [
            'teacher' => new Teacher(['is_active' => true, 'experience_years' => 5]),
            'account' => null,
            'availablePermissions' => User::TEACHER_PERMISSIONS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isSuperAdmin(), 403);

        $data = $this->validated($request);
        $data['slug'] = $this->uniqueSlug($data['name']);
        $data['position'] = ((int) Teacher::max('position')) + 1;

        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo')->store('teachers', 'public');
        }

        $teacher = Teacher::create($data);

        $this->syncTeacherAccount($request, $teacher);

        return redirect()->route('admin.teachers.show', $teacher)
            ->with('success', 'Ustoz va uning tizimga kirish hisobi saqlandi!');
    }

    public function show(Request $request, Teacher $teacher)
    {
        $this->authorizeTeacherAccess($request, $teacher);

        $teacher->load(['results', 'account']);

        return view('admin.teachers.show', compact('teacher'));
    }

    public function edit(Request $request, Teacher $teacher)
    {
        $this->authorizeTeacherAccess($request, $teacher);

        $teacher->load('account');

        return view('admin.teachers.form', [
            'teacher' => $teacher,
            'account' => $teacher->account,
            'availablePermissions' => User::TEACHER_PERMISSIONS,
        ]);
    }

    public function update(Request $request, Teacher $teacher): RedirectResponse
    {
        $this->authorizeTeacherAccess($request, $teacher);

        $data = $this->validated($request, $teacher);

        if ($request->hasFile('photo')) {
            if ($teacher->photo) {
                Storage::disk('public')->delete($teacher->photo);
            }
            $data['photo'] = $request->file('photo')->store('teachers', 'public');
        } elseif ($request->boolean('remove_photo')) {
            if ($teacher->photo) {
                Storage::disk('public')->delete($teacher->photo);
            }
            $data['photo'] = null;
        }

        $teacher->update($data);

        if ($request->user()->isSuperAdmin()) {
            $this->syncTeacherAccount($request, $teacher);
        }

        return redirect()->route('admin.teachers.show', $teacher)
            ->with('success', 'Ustoz ma\'lumotlari va ruxsatlari yangilandi.');
    }

    public function destroy(Request $request, Teacher $teacher): RedirectResponse
    {
        abort_unless($request->user()->isSuperAdmin(), 403);

        foreach ($teacher->results as $result) {
            if ($result->student_photo) {
                Storage::disk('public')->delete($result->student_photo);
            }
            if ($result->certificate_image) {
                Storage::disk('public')->delete($result->certificate_image);
            }
        }

        if ($teacher->photo) {
            Storage::disk('public')->delete($teacher->photo);
        }

        if ($teacher->account) {
            $teacher->account->delete();
        }

        $teacher->delete();

        return redirect()->route('admin.teachers.index')
            ->with('success', 'Ustoz o\'chirildi.');
    }

    /**
     * Ustozning yuqori ball olgan o'quvchisi va sertifikatini qo'shish
     */
    public function storeResult(Request $request, Teacher $teacher): RedirectResponse
    {
        $this->authorizeTeacherAccess($request, $teacher);

        $data = $request->validate([
            'student_name' => ['required', 'string', 'max:120'],
            'score' => ['required', 'string', 'max:100'],
            'achievement' => ['nullable', 'string', 'max:255'],
            'testimonial' => ['nullable', 'string', 'max:1000'],
            'student_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
            'certificate_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], [
            'student_name.required' => 'O\'quvchi ism-familiyasini kiriting.',
            'score.required' => 'Olgan bali yoki sertifikat darajasini kiriting (masalan: IELTS 8.0 yoki 189.0 ball).',
            'student_photo.image' => 'O\'quvchi rasmi rasm formatida bo\'lishi kerak.',
            'certificate_image.image' => 'Sertifikat rasmi rasm formatida bo\'lishi kerak.',
        ]);

        if ($request->hasFile('student_photo')) {
            $data['student_photo'] = $request->file('student_photo')->store('teacher-results/students', 'public');
        }

        if ($request->hasFile('certificate_image')) {
            $data['certificate_image'] = $request->file('certificate_image')->store('teacher-results/certificates', 'public');
        }

        $data['position'] = ((int) $teacher->results()->max('position')) + 1;
        $teacher->results()->create($data);

        return redirect()->route('admin.teachers.show', $teacher)
            ->with('success', 'O\'quvchi natijasi va sertifikati qo\'shildi!');
    }

    public function destroyResult(Request $request, Teacher $teacher, TeacherResult $result): RedirectResponse
    {
        $this->authorizeTeacherAccess($request, $teacher);
        abort_unless($result->teacher_id === $teacher->id, 404);

        if ($result->student_photo) {
            Storage::disk('public')->delete($result->student_photo);
        }
        if ($result->certificate_image) {
            Storage::disk('public')->delete($result->certificate_image);
        }

        $result->delete();

        return redirect()->route('admin.teachers.show', $teacher)
            ->with('success', 'Natija o\'chirildi.');
    }

    private function authorizeTeacherAccess(Request $request, Teacher $teacher): void
    {
        $user = $request->user();

        if ($user->isSuperAdmin()) {
            return;
        }

        abort_unless(
            $user->isTeacher() && $user->hasPermission('manage_results') && $user->teacher_id === $teacher->id,
            403
        );
    }

    private function validated(Request $request, ?Teacher $teacher = null): array
    {
        $accountId = $teacher?->account?->id;

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'subject' => ['required', 'string', 'max:120'],
            'experience_years' => ['required', 'integer', 'min:0', 'max:60'],
            'headline' => ['nullable', 'string', 'max:200'],
            'bio' => ['nullable', 'string', 'max:5000'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => [
                'nullable',
                'email',
                'max:150',
                Rule::unique('users', 'email')->ignore($accountId),
            ],
            'telegram' => ['nullable', 'string', 'max:100'],
            'work_hours' => ['nullable', 'string', 'max:150'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
            'login_password' => ['nullable', 'string', 'min:6', 'max:100'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::in(array_keys(User::TEACHER_PERMISSIONS))],
        ], [
            'name.required' => 'Ustozning ism va familiyasini kiriting.',
            'subject.required' => 'Dars beradigan fani / yo\'nalishini kiriting.',
            'email.unique' => 'Ushbu email manzil boshqa foydalanuvchi tomonidan band qilingan.',
            'login_password.min' => 'Ustoz paroli kamida 6 ta belgidan iborat bo\'lsin.',
        ]);

        $data['is_active'] = $request->boolean('is_active');
        unset($data['photo'], $data['login_password'], $data['permissions']);

        return $data;
    }

    private function syncTeacherAccount(Request $request, Teacher $teacher): void
    {
        $email = $teacher->email ? mb_strtolower(trim($teacher->email)) : null;
        $rawPhone = $teacher->phone ? (Phone::normalize($teacher->phone) ?? trim($teacher->phone)) : null;
        $password = trim((string) $request->input('login_password', ''));
        $permissions = array_values(array_intersect(
            (array) $request->input('permissions', []),
            array_keys(User::TEACHER_PERMISSIONS)
        ));

        $account = $teacher->account;

        if (! $account && $email) {
            $account = User::where('email', $email)->where('role', '!=', 'admin')->first();
        }

        // Agar ustoz uchun email va parol berilgan bo'lsa (yoki oldindan akkaunti bo'lsa), tizimga kirish hisobini yaratamiz/yangilaymiz
        if (! $account && $password === '') {
            return;
        }

        if (! $account) {
            $account = new User;
            $account->phone = $rawPhone && ! User::where('phone', $rawPhone)->exists()
                ? $rawPhone
                : '+99800'.str_pad((string) $teacher->id, 7, '0', STR_PAD_LEFT);
        } elseif ($rawPhone && ! User::where('phone', $rawPhone)->where('id', '!=', $account->id)->exists()) {
            $account->phone = $rawPhone;
        }

        $account->name = $teacher->name;
        if ($email) {
            $account->email = $email;
        }
        $account->role = 'teacher';
        $account->teacher_id = $teacher->id;
        $account->permissions = $permissions;

        if ($password !== '') {
            $account->password = $password;
        }

        $account->save();
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'ustoz';
        $slug = $base;
        $i = 2;

        while (Teacher::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
