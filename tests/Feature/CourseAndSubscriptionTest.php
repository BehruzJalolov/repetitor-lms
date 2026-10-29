<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Lead;
use App\Models\Lesson;
use App\Models\LiveStream;
use App\Models\Setting;
use App\Models\Subscription;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CourseAndSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_teachers_showcase_and_profile_results_page_are_accessible(): void
    {
        $teacher = Teacher::create([
            'name' => 'Sardor Karimov',
            'slug' => 'sardor-karimov',
            'subject' => 'Matematika',
            'experience_years' => 9,
            'headline' => 'Milliy sertifikat A+ sohibi',
            'phone' => '+998 90 123 45 67',
            'email' => 'sardor@repetitor.uz',
            'work_hours' => '09:00 – 19:00',
            'is_active' => true,
        ]);

        $teacher->results()->create([
            'student_name' => 'Jasmina Alimova',
            'score' => '189.0 ball',
            'achievement' => 'TATU — Grant',
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Sardor Karimov')
            ->assertSee('Jasmina Alimova');

        $this->get(route('teachers.index'))
            ->assertOk()
            ->assertSee('Sardor Karimov');

        $this->get(route('teachers.show', $teacher))
            ->assertOk()
            ->assertSee('Sardor Karimov')
            ->assertSee('189.0 ball')
            ->assertSee('09:00 – 19:00');
    }

    public function test_student_has_automatic_access_to_free_course_lessons(): void
    {
        $student = User::factory()->create();

        $freeCourse = Course::create([
            'title' => 'Bepul Matematika',
            'slug' => 'bepul-matematika',
            'price' => 0,
            'is_active' => true,
        ]);

        $lesson = Lesson::create([
            'course_id' => $freeCourse->id,
            'title' => '2-dars (is_free false bo\'lsa ham kurs bepul)',
            'youtube_url' => 'https://youtu.be/dQw4w9WgXcQ',
            'position' => 1,
            'is_free' => false,
        ]);

        $this->assertTrue($student->hasAccessTo($freeCourse));

        $response = $this->actingAs($student)->get(route('lessons.show', [$freeCourse, $lesson]));
        $response->assertOk();
        $response->assertSee($lesson->title);
    }

    public function test_hidden_courses_are_excluded_from_my_courses_page(): void
    {
        $student = User::factory()->create();

        $hiddenCourse = Course::create([
            'title' => 'Yashirin Kurs',
            'slug' => 'yashirin-kurs',
            'price' => 150000,
            'is_active' => false,
        ]);

        Subscription::create([
            'user_id' => $student->id,
            'course_id' => $hiddenCourse->id,
            'price' => 150000,
            'receipt_image' => 'receipts/test.jpg',
            'status' => 'approved',
        ]);

        $response = $this->actingAs($student)->get(route('my-courses'));
        $response->assertOk();
        $response->assertDontSee('Yashirin Kurs');
    }

    public function test_admin_dashboard_revenue_preserves_historical_subscription_price(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->create();

        $course = Course::create([
            'title' => 'Fizika Kursi',
            'slug' => 'fizika-kursi',
            'price' => 200000,
            'is_active' => true,
        ]);

        $subscription = Subscription::create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'price' => 200000,
            'receipt_image' => 'receipts/fizika.jpg',
            'status' => 'pending',
        ]);

        // Admin approves subscription when price is 200,000
        $this->actingAs($admin)->post(route('admin.subscriptions.approve', $subscription), [
            'duration' => '6',
        ])->assertRedirect();

        // Later admin increases course price to 350,000
        $course->update(['price' => 350000]);

        // Revenue on dashboard must still be 200,000 (not 350,000)
        $response = $this->actingAs($admin)->get(route('admin.dashboard'));
        $response->assertOk();
        $response->assertViewHas('revenue', 200000);
    }

    public function test_single_session_middleware_logs_out_previous_session_when_new_device_logs_in(): void
    {
        $student = User::factory()->create([
            'phone' => '+998901234567',
            'password' => 'secret1234',
            'current_session_id' => 'old-device-session-id',
        ]);

        // Request from a session whose ID does not match current_session_id is logged out
        $response = $this->actingAs($student)->get(route('my-courses'));

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_lesson_quiz_requires_seventy_percent_to_complete_and_unlocks_certificate(): void
    {
        $student = User::factory()->create();

        $course = Course::create([
            'title' => 'IELTS Intensive',
            'slug' => 'ielts-intensive',
            'price' => 0,
            'is_active' => true,
        ]);

        $lesson = Lesson::create([
            'course_id' => $course->id,
            'title' => 'Reading Strategies',
            'youtube_url' => 'https://youtu.be/dQw4w9WgXcQ',
            'position' => 1,
            'is_free' => true,
        ]);

        $q1 = $lesson->questions()->create([
            'question' => '2 + 2 nechaga teng?',
            'option_a' => '3',
            'option_b' => '4',
            'option_c' => '5',
            'option_d' => '6',
            'correct_option' => 'b',
        ]);

        // Before completing lesson, certificate is blocked
        $this->actingAs($student)
            ->get(route('courses.certificate', $course))
            ->assertRedirect(route('courses.show', $course));

        // Failing quiz (0%) does not mark lesson completed
        $this->actingAs($student)->post(route('lessons.quiz', [$course, $lesson]), [
            'answers' => [$q1->id => 'a'],
        ])->assertRedirect();

        $this->assertDatabaseHas('lesson_user', [
            'user_id' => $student->id,
            'lesson_id' => $lesson->id,
            'is_completed' => false,
            'quiz_score' => 0,
        ]);

        // Passing quiz (100%) marks lesson completed and unlocks certificate
        $this->actingAs($student)->post(route('lessons.quiz', [$course, $lesson]), [
            'answers' => [$q1->id => 'b'],
        ])->assertRedirect();

        $this->assertDatabaseHas('lesson_user', [
            'user_id' => $student->id,
            'lesson_id' => $lesson->id,
            'is_completed' => true,
            'quiz_score' => 100,
        ]);

        $this->actingAs($student)
            ->get(route('courses.certificate', $course))
            ->assertOk()
            ->assertSee('SERTIFIKAT')
            ->assertSee($student->name);
    }

    public function test_visitor_can_submit_consultation_lead(): void
    {
        $this->post(route('leads.store'), [
            'name' => 'Sardorbek',
            'phone' => '90 555 66 77',
            'message' => 'Matematika kursi haqida',
        ])->assertRedirect();

        $this->assertDatabaseHas('leads', [
            'name' => 'Sardorbek',
            'phone' => '+998905556677',
            'status' => 'new',
        ]);
    }

    public function test_admin_can_update_site_settings_and_reflect_on_public_pages(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('admin.settings.edit'))
            ->assertOk()
            ->assertSee('Sayt sozlamalari');

        $this->actingAs($admin)->put(route('admin.settings.update'), [
            'site_name' => 'GrandEdu Academy',
            'site_description' => 'Eng kuchli onlayn akademiya',
            'contact_phone' => '+998 99 777 88 99',
            'telegram_username' => '@grandedu_uz',
            'contact_address' => 'Toshkent, Yunusobod',
            'card_number' => '9860 1234 5678 9012',
            'card_holder' => 'SARDOR KARIMOV',
            'telegram_bot_token' => '',
            'telegram_chat_id' => '',
        ])->assertRedirect();

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('GrandEdu Academy')
            ->assertSee('+998 99 777 88 99')
            ->assertSee('@grandedu_uz');
    }

    public function test_mandatory_telegram_channel_subscription_during_registration(): void
    {
        Setting::setSecret('telegram_bot_token', '123456:TEST_SECRET_TOKEN');
        Setting::setMany([
            'telegram_bot_username' => '@repetitor_test_bot',
            'telegram_username' => '@repetitor_test_channel',
            'require_telegram_subscription' => '1',
        ]);

        // Step 1: Student submits registration form
        $this->post(route('register.store'), [
            'name' => 'Azizbek Toshmatov',
            'phone' => '+998 90 333 44 55',
            'email' => 'azizbek@example.com',
            'password' => 'secret1234',
            'password_confirmation' => 'secret1234',
        ])->assertRedirect(route('register.verify'));

        $pending = session('pending_registration');
        $this->assertNotEmpty($pending['telegram_token']);
        $startToken = $pending['telegram_token'];

        // Step 2: If student has started bot, but is NOT subscribed to channel ('left'), checkTelegram rejects
        Http::fake([
            'api.telegram.org/bot*/getUpdates*' => Http::response([
                'ok' => true,
                'result' => [
                    [
                        'update_id' => 1,
                        'message' => [
                            'from' => ['id' => 777888999],
                            'text' => '/start reg_'.$startToken,
                        ],
                    ],
                ],
            ]),
            'api.telegram.org/bot*/getChatMember*' => Http::response([
                'ok' => true,
                'result' => ['status' => 'left'],
            ]),
            'api.telegram.org/bot*/sendMessage*' => Http::response(['ok' => true]),
        ]);

        $this->post(route('register.telegram.check'))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertFalse(session('pending_registration.channel_verified'));
    }

    public function test_student_completes_registration_after_subscribing_to_telegram_channel(): void
    {
        Setting::setSecret('telegram_bot_token', '123456:TEST_SECRET_TOKEN');
        Setting::setMany([
            'telegram_bot_username' => '@repetitor_test_bot',
            'telegram_username' => '@repetitor_test_channel',
            'require_telegram_subscription' => '1',
        ]);

        $this->post(route('register.store'), [
            'name' => 'Dilnoza Karimova',
            'phone' => '+998 90 444 55 66',
            'email' => 'dilnoza@example.com',
            'password' => 'secret1234',
            'password_confirmation' => 'secret1234',
        ])->assertRedirect(route('register.verify'));

        $startToken = session('pending_registration.telegram_token');
        Cache::put('tg_start:'.$startToken, '555666777', now()->addMinutes(10));

        $sentText = '';
        Http::fake(function ($request) use (&$sentText) {
            if (str_contains($request->url(), 'getChatMember')) {
                return Http::response([
                    'ok' => true,
                    'result' => ['status' => 'member'],
                ]);
            }

            if (str_contains($request->url(), 'sendMessage')) {
                $sentText = (string) ($request['text'] ?? '');

                return Http::response(['ok' => true]);
            }

            return Http::response(['ok' => true]);
        });

        $this->post(route('register.telegram.check'))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertTrue(session('pending_registration.channel_verified'));
        preg_match('/<code>(\d{6})<\/code>/', $sentText, $matches);
        $this->assertNotEmpty($matches[1]);

        $this->post(route('register.verify.store'), [
            'code' => $matches[1],
        ])->assertRedirect(route('courses.index'));

        $this->assertDatabaseHas('users', [
            'email' => 'dilnoza@example.com',
            'telegram_chat_id' => '555666777',
        ]);
    }

    public function test_interactive_telegram_bot_buttons_verify_channel_and_send_code(): void
    {
        Setting::setSecret('telegram_bot_token', '123456:TEST_SECRET_TOKEN');
        Setting::setMany([
            'telegram_bot_username' => '@repetitor_test_bot',
            'telegram_username' => '@repetitor_test_channel',
            'require_telegram_subscription' => '1',
        ]);

        $this->post(route('register.store'), [
            'name' => 'Kamola Usmonova',
            'phone' => '+998 90 111 22 33',
            'email' => 'kamola@example.com',
            'password' => 'secret1234',
            'password_confirmation' => 'secret1234',
        ])->assertRedirect(route('register.verify'));

        $startToken = session('pending_registration.telegram_token');
        $sentMessages = [];

        Http::fake(function ($request) use ($startToken, &$sentMessages) {
            if (str_contains($request->url(), 'getUpdates')) {
                return Http::response([
                    'ok' => true,
                    'result' => [
                        [
                            'update_id' => 101,
                            'message' => [
                                'chat' => ['id' => 999888777],
                                'from' => ['id' => 999888777, 'first_name' => 'Kamola'],
                                'text' => '/start reg_'.$startToken,
                            ],
                        ],
                        [
                            'update_id' => 102,
                            'callback_query' => [
                                'id' => 'cb_1',
                                'from' => ['id' => 999888777],
                                'message' => ['chat' => ['id' => 999888777]],
                                'data' => 'verify_sub:'.$startToken,
                            ],
                        ],
                    ],
                ]);
            }

            if (str_contains($request->url(), 'getChatMember')) {
                return Http::response([
                    'ok' => true,
                    'result' => ['status' => 'member'],
                ]);
            }

            if (str_contains($request->url(), 'sendMessage')) {
                $sentMessages[] = (string) ($request['text'] ?? '');

                return Http::response(['ok' => true]);
            }

            return Http::response(['ok' => true]);
        });

        // Browser polls status -> triggers /start menu + callback_query verification inside Telegram
        $this->getJson(route('register.telegram.poll'))
            ->assertOk()
            ->assertJson([
                'started' => true,
                'verified' => true,
            ]);

        $this->assertCount(2, $sentMessages);
        $this->assertStringContainsString('rasmiy botiga xush kelibsiz', $sentMessages[0]);
        $this->assertStringContainsString('Sizning 6 xonali tasdiqlash kodingiz', $sentMessages[1]);
    }

    public function test_student_chat_blocks_profanity_and_links_and_supports_admin_moderation(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->create(['name' => 'Odobli Talaba']);

        // 1. Clean message is accepted
        $this->actingAs($student)->post(route('chat.store'), [
            'message' => 'Assalomu alaykum! 3-misoldagi logarifm qanday ishlanadi?',
        ])->assertRedirect();

        $this->assertDatabaseHas('chat_messages', [
            'user_id' => $student->id,
            'message' => 'Assalomu alaykum! 3-misoldagi logarifm qanday ishlanadi?',
        ]);

        // 2. Profanity / swear word is blocked
        $this->actingAs($student)->post(route('chat.store'), [
            'message' => 'Sen dalbayob ekansan',
        ])->assertSessionHas('error');

        $this->assertDatabaseMissing('chat_messages', [
            'message' => 'Sen dalbayob ekansan',
        ]);

        // 3. Spam link from student is blocked
        $this->actingAs($student)->post(route('chat.store'), [
            'message' => 'Mening kanalimga kiring https://t.me/spam_kanal',
        ])->assertSessionHas('error');

        // 4. Admin can mute/ban student from chat
        $this->actingAs($admin)->post(route('chat.ban', $student))->assertRedirect();
        $student = $student->fresh();
        $this->assertTrue($student->is_chat_banned);

        $this->actingAs($student)->post(route('chat.store'), [
            'message' => 'Men yana yozmoqchiman',
        ])->assertSessionHas('error');
    }

    public function test_admin_can_upload_direct_video_file_for_lesson_and_student_can_stream_it(): void
    {
        Storage::fake('local');

        $admin = User::factory()->admin()->create();
        $student = User::factory()->create();

        $course = Course::create([
            'title' => 'Video Yuklash Kursi',
            'slug' => 'video-yuklash-kursi',
            'price' => 0,
            'is_active' => true,
        ]);

        $videoFile = UploadedFile::fake()->create('dars1.mp4', 1024, 'video/mp4');

        $this->actingAs($admin)->post(route('admin.courses.lessons.store', $course), [
            'title' => '1-dars: Kompyuterdan yuklangan video',
            'youtube_url' => '',
            'video_file' => $videoFile,
            'is_free' => '1',
        ])->assertRedirect();

        $lesson = $course->lessons()->first();
        $this->assertNotNull($lesson);
        $this->assertNotNull($lesson->video_path);

        $this->actingAs($student)
            ->get(route('lessons.show', [$course, $lesson]))
            ->assertOk()
            ->assertSee(route('lessons.video', [$course, $lesson]))
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_admin_can_create_and_start_live_stream_and_student_can_join(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->create();

        $this->actingAs($admin)->post(route('admin.streams.store'), [
            'title' => 'DTM Matematika Jonli Tahlil',
            'stream_type' => 'jitsi',
            'start_now' => '1',
        ])->assertRedirect();

        $stream = LiveStream::first();
        $this->assertNotNull($stream);
        $this->assertSame('live', $stream->status);

        $this->actingAs($student)
            ->get(route('streams.show', $stream))
            ->assertOk()
            ->assertSee('DTM Matematika Jonli Tahlil')
            ->assertSee('meet.jit.si/'.$stream->room_code);
    }

    public function test_super_admin_creates_teacher_login_and_permissions_and_teacher_logs_in_without_otp(): void
    {
        $superAdmin = User::factory()->admin()->create();

        // 1. Super Admin creates a teacher with email, password, and granular permissions
        $this->actingAs($superAdmin)->post(route('admin.teachers.store'), [
            'name' => 'Nilufar Rustamova',
            'subject' => 'IELTS & CEFR',
            'experience_years' => 7,
            'phone' => '+998 93 555 44 33',
            'email' => 'nilufar@repetitor.uz',
            'login_password' => 'nilufar2026',
            'permissions' => ['manage_courses', 'manage_results'],
            'is_active' => '1',
        ])->assertRedirect();

        $teacher = Teacher::where('email', 'nilufar@repetitor.uz')->firstOrFail();
        $teacherUser = User::where('email', 'nilufar@repetitor.uz')->firstOrFail();

        $this->assertSame('teacher', $teacherUser->role);
        $this->assertSame($teacher->id, $teacherUser->teacher_id);
        $this->assertSame(['manage_courses', 'manage_results'], $teacherUser->permissions);

        // 2. Teacher logs out Super Admin and logs in directly via /kirish with email + password (no OTP!)
        $this->post(route('logout'));

        $this->post(route('login.store'), [
            'phone' => 'nilufar@repetitor.uz',
            'password' => 'nilufar2026',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($teacherUser);

        // 3. Teacher can access granted sections (Courses & Own Teacher Profile)
        $this->get(route('admin.courses.index'))->assertOk();
        $this->get(route('admin.teachers.show', $teacher))->assertOk();

        // 4. Teacher is blocked from ungranted sections (Subscriptions & Site Settings)
        $this->get(route('admin.subscriptions.index'))->assertForbidden();
        $this->get(route('admin.settings.edit'))->assertForbidden();
    }

    public function test_teacher_can_only_manage_own_courses_lessons_and_streams(): void
    {
        $teacherA = Teacher::create([
            'name' => 'Sardor Ustoz',
            'slug' => 'sardor-ustoz',
            'subject' => 'Matematika',
            'experience_years' => 8,
            'is_active' => true,
        ]);

        $teacherB = Teacher::create([
            'name' => 'Nilufar Ustoz',
            'slug' => 'nilufar-ustoz',
            'subject' => 'Ingliz tili',
            'experience_years' => 6,
            'is_active' => true,
        ]);

        $teacherUserA = User::factory()->create([
            'role' => 'teacher',
            'teacher_id' => $teacherA->id,
            'permissions' => ['manage_courses', 'manage_streams'],
        ]);

        $courseA = Course::create([
            'teacher_id' => $teacherA->id,
            'title' => 'Sardor Matematika Kursi',
            'slug' => 'sardor-matematika-kursi',
            'price' => 200000,
            'is_active' => true,
        ]);

        $courseB = Course::create([
            'teacher_id' => $teacherB->id,
            'title' => 'Nilufar IELTS Kursi',
            'slug' => 'nilufar-ielts-kursi',
            'price' => 300000,
            'is_active' => true,
        ]);

        $lessonB = $courseB->lessons()->create([
            'title' => 'IELTS Listening 1-dars',
            'youtube_url' => 'https://www.youtube.com/watch?v=rfscVS0vTik',
            'position' => 1,
            'is_free' => true,
        ]);

        $streamB = LiveStream::create([
            'teacher_id' => $teacherB->id,
            'course_id' => $courseB->id,
            'title' => 'Nilufar Speaking Stream',
            'stream_type' => 'jitsi',
            'room_code' => 'RepetitorLMS_TESTROOM1',
            'status' => 'scheduled',
        ]);

        // Teacher A can view/edit their own course
        $this->actingAs($teacherUserA)->get(route('admin.courses.edit', $courseA))->assertOk();

        // Teacher A is strictly forbidden (403) from viewing/editing/deleting Teacher B's course, lessons, or stream
        $this->actingAs($teacherUserA)->get(route('admin.courses.edit', $courseB))->assertForbidden();
        $this->actingAs($teacherUserA)->delete(route('admin.courses.destroy', $courseB))->assertForbidden();
        $this->actingAs($teacherUserA)->get(route('admin.lessons.edit', $lessonB))->assertForbidden();
        $this->actingAs($teacherUserA)->post(route('admin.streams.end', $streamB))->assertForbidden();
        $this->actingAs($teacherUserA)->delete(route('admin.streams.destroy', $streamB))->assertForbidden();
    }

    public function test_student_cannot_access_unsubscribed_paid_lesson_chat_or_submit_duplicate_receipts(): void
    {
        Storage::fake('local');

        $student = User::factory()->create();
        $course = Course::create([
            'title' => 'Yopiq Premium Kurs',
            'slug' => 'yopiq-premium-kurs',
            'price' => 500000,
            'is_active' => true,
        ]);

        $paidLesson = $course->lessons()->create([
            'title' => 'Yopiq pullik dars',
            'youtube_url' => 'https://www.youtube.com/watch?v=rfscVS0vTik',
            'position' => 1,
            'is_free' => false,
        ]);

        // Unsubscribed student cannot post chat messages to a paid lesson
        $this->actingAs($student)->post(route('chat.store'), [
            'lesson_id' => $paidLesson->id,
            'message' => 'Ruxsatsiz darsga xabar yozish',
        ])->assertForbidden();

        // Student submits first receipt -> accepted (pending)
        $receipt1 = UploadedFile::fake()->image('chek1.jpg');
        $this->actingAs($student)->post(route('subscriptions.store', $course), [
            'receipt' => $receipt1,
        ])->assertRedirect(route('courses.show', $course));

        // Student submits duplicate receipt while first is pending -> blocked
        $receipt2 = UploadedFile::fake()->image('chek2.jpg');
        $this->actingAs($student)->post(route('subscriptions.store', $course), [
            'receipt' => $receipt2,
        ])->assertSessionHas('error');

        $this->assertSame(
            1,
            Subscription::where('user_id', $student->id)->where('course_id', $course->id)->count()
        );
    }

    public function test_teacher_with_manage_subscriptions_and_leads_can_only_access_own_course_records(): void
    {
        Storage::fake('local');

        $teacherA = Teacher::create([
            'name' => 'Sardor Ustoz',
            'slug' => 'sardor-ustoz-sub',
            'subject' => 'Matematika',
            'experience_years' => 8,
            'is_active' => true,
        ]);

        $teacherB = Teacher::create([
            'name' => 'Nilufar Ustoz',
            'slug' => 'nilufar-ustoz-sub',
            'subject' => 'Ingliz tili',
            'experience_years' => 6,
            'is_active' => true,
        ]);

        $teacherUserA = User::factory()->create([
            'role' => 'teacher',
            'teacher_id' => $teacherA->id,
            'permissions' => ['manage_subscriptions', 'manage_leads'],
        ]);

        $student = User::factory()->create();

        $courseA = Course::create([
            'teacher_id' => $teacherA->id,
            'title' => 'Sardor Matematika',
            'slug' => 'sardor-matematika-sub',
            'price' => 250000,
            'is_active' => true,
        ]);

        $courseB = Course::create([
            'teacher_id' => $teacherB->id,
            'title' => 'Nilufar IELTS',
            'slug' => 'nilufar-ielts-sub',
            'price' => 300000,
            'is_active' => true,
        ]);

        $paidLessonB = $courseB->lessons()->create([
            'title' => 'Nilufar yopiq darsi',
            'youtube_url' => 'https://www.youtube.com/watch?v=rfscVS0vTik',
            'position' => 1,
            'is_free' => false,
        ]);

        $subA = Subscription::create([
            'user_id' => $student->id,
            'course_id' => $courseA->id,
            'price' => 250000,
            'receipt_image' => UploadedFile::fake()->image('a.jpg')->store('receipts', 'local'),
            'status' => 'pending',
        ]);

        $subB = Subscription::create([
            'user_id' => $student->id,
            'course_id' => $courseB->id,
            'price' => 300000,
            'receipt_image' => UploadedFile::fake()->image('b.jpg')->store('receipts', 'local'),
            'status' => 'pending',
        ]);

        $leadB = Lead::create([
            'course_id' => $courseB->id,
            'name' => 'Talaba B',
            'phone' => '+998901112233',
            'status' => 'new',
        ]);

        // 1. Teacher A can approve subscription for their own course (Course A)
        $this->actingAs($teacherUserA)->post(route('admin.subscriptions.approve', $subA), [
            'duration' => '1',
        ])->assertRedirect();
        $this->assertSame('approved', $subA->fresh()->status);

        // 2. Teacher A is strictly forbidden (403) from viewing receipt, approving, or rejecting Teacher B's subscription
        $this->actingAs($teacherUserA)->get(route('admin.subscriptions.receipt', $subB))->assertForbidden();
        $this->actingAs($teacherUserA)->post(route('admin.subscriptions.approve', $subB), ['duration' => '1'])->assertForbidden();
        $this->actingAs($teacherUserA)->post(route('admin.subscriptions.reject', $subB), ['admin_comment' => 'Bekor'])->assertForbidden();
        $this->assertSame('pending', $subB->fresh()->status);

        // 3. Teacher A is strictly forbidden (403) from modifying or deleting Teacher B's leads
        $this->actingAs($teacherUserA)->patch(route('admin.leads.status', $leadB), ['status' => 'contacted'])->assertForbidden();
        $this->actingAs($teacherUserA)->delete(route('admin.leads.destroy', $leadB))->assertForbidden();

        // 4. Teacher A does NOT get automatic free access to Teacher B's paid lessons
        $this->actingAs($teacherUserA)->get(route('lessons.show', [$courseB, $paidLessonB]))
            ->assertRedirect(route('courses.show', $courseB));
    }

    public function test_subscription_can_only_be_approved_or_rejected_when_pending(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->create();
        $course = Course::create([
            'title' => 'Parallel Test Kursi',
            'slug' => 'parallel-test-kursi',
            'price' => 200000,
            'is_active' => true,
        ]);

        $subscription = Subscription::create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'price' => 200000,
            'receipt_image' => 'receipts/test.jpg',
            'status' => 'pending',
        ]);

        // 1st approval succeeds
        $this->actingAs($admin)->post(route('admin.subscriptions.approve', $subscription), [
            'duration' => '3',
        ])->assertSessionHas('success');

        $this->assertSame('approved', $subscription->fresh()->status);

        // 2nd duplicate approval on already-approved subscription is blocked inside lockForUpdate()
        $this->actingAs($admin)->post(route('admin.subscriptions.approve', $subscription), [
            'duration' => '12',
        ])->assertSessionHas('error');

        // Rejecting an already-approved subscription is also blocked
        $this->actingAs($admin)->post(route('admin.subscriptions.reject', $subscription), [
            'admin_comment' => 'Xato bosildi',
        ])->assertSessionHas('error');

        $this->assertSame('approved', $subscription->fresh()->status);
    }

    public function test_single_device_session_invalidates_previous_session_on_second_login(): void
    {
        $student = User::factory()->create([
            'phone' => '+998907778899',
            'password' => 'secret12345',
            'current_session_id' => 'old_device_session_id_123',
        ]);

        // When student makes a request while their session ID doesn't match current_session_id, EnsureSingleSession logs them out
        $this->actingAs($student)
            ->get(route('my-courses'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }
}
