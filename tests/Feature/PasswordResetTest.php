<?php

namespace Tests\Feature;

use App\Mail\PasswordResetCodeMail;
use App\Mail\VerificationCodeMail;
use App\Models\Course;
use App\Models\User;
use App\Services\TelegramNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_requires_telegram_bot_otp_verification_before_creating_user(): void
    {
        Mail::fake();

        $response = $this->post(route('register.store'), [
            'name' => 'Yangi Talaba',
            'phone' => '90 123 45 67',
            'email' => 'talaba@example.com',
            'password' => 'secret1234',
            'password_confirmation' => 'secret1234',
        ]);

        $response->assertRedirect(route('register.verify'));
        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'talaba@example.com']);
        Mail::assertNothingSent();

        $regToken = session('pending_registration.telegram_token');
        $this->assertNotEmpty($regToken);

        // Simulate Telegram bot verification & OTP issuance
        $otpCode = '482910';
        DB::table('password_reset_tokens')->updateOrInsert(
            ['phone' => 'reg:talaba@example.com'],
            ['token' => Hash::make($otpCode), 'attempts' => 0, 'created_at' => now()]
        );
        Cache::put('tg_verified:'.$regToken, [
            'chat_id' => '777888999',
            'code' => $otpCode,
            'verified_at' => now()->toIso8601String(),
        ], now()->addMinutes(45));

        $verifyResponse = $this->post(route('register.verify.store'), [
            'code' => $otpCode,
        ]);

        $verifyResponse->assertRedirect(route('courses.index'));
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => 'talaba@example.com',
            'phone' => '+998901234567',
            'telegram_chat_id' => '777888999',
        ]);
    }

    public function test_profile_email_and_password_changes_require_otp_verification(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'email' => 'old@example.com',
            'phone' => '+998901234567',
        ]);

        // 1. Changing email sends OTP and does not update email immediately
        $this->actingAs($user)->put(route('profile.update'), [
            'name' => $user->name,
            'phone' => $user->phone,
            'email' => 'new@example.com',
        ])->assertRedirect();

        $this->assertSame('old@example.com', $user->fresh()->email);

        $emailCode = null;
        Mail::assertSent(VerificationCodeMail::class, function (VerificationCodeMail $mail) use (&$emailCode) {
            $emailCode = $mail->code;

            return $mail->hasTo('new@example.com');
        });

        $this->actingAs($user)->post(route('profile.email.verify'), [
            'email_code' => $emailCode,
        ])->assertRedirect(route('profile.edit'));

        $this->assertSame('new@example.com', $user->fresh()->email);

        // 2. Changing password from profile requires OTP code
        $this->actingAs($user->fresh())->post(route('profile.password.code'), [
            'channel' => 'email',
        ])->assertRedirect();

        $pwdCode = null;
        Mail::assertSent(VerificationCodeMail::class, function (VerificationCodeMail $mail) use (&$pwdCode) {
            if ($mail->purpose === "Parolni o'zgartirish kodi") {
                $pwdCode = $mail->code;

                return true;
            }

            return false;
        });

        $this->assertNotNull($pwdCode);

        $this->actingAs($user->fresh())->put(route('profile.password.update'), [
            'code' => $pwdCode,
            'password' => 'updated-pass-999',
            'password_confirmation' => 'updated-pass-999',
        ])->assertRedirect(route('profile.edit'));

        $this->assertTrue(Hash::check('updated-pass-999', $user->fresh()->password));
    }

    public function test_user_can_request_and_reset_password_via_email(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'email' => 'student@example.com',
            'phone' => '+998901234567',
        ]);

        $response = $this->post(route('password.phone'), [
            'phone' => 'STUDENT@example.com',
        ]);

        $response->assertRedirect(route('password.reset'));
        $response->assertSessionMissing('test_code');
        $this->assertEquals('student@example.com', session('password_reset_phone'));

        $sentCode = null;
        Mail::assertSent(PasswordResetCodeMail::class, function (PasswordResetCodeMail $mail) use (&$sentCode) {
            $sentCode = $mail->code;

            return $mail->hasTo('student@example.com');
        });

        $this->assertNotNull($sentCode);

        $resetResponse = $this->post(route('password.update'), [
            'phone' => 'student@example.com',
            'code' => $sentCode,
            'password' => 'new-secret-123',
            'password_confirmation' => 'new-secret-123',
        ]);

        $resetResponse->assertRedirect(route('login'));
        $this->assertTrue(Hash::check('new-secret-123', $user->fresh()->password));
        $this->assertDatabaseMissing('password_reset_tokens', ['phone' => 'student@example.com']);
    }

    public function test_user_can_request_password_reset_via_phone_without_exposing_test_code(): void
    {
        $user = User::factory()->create([
            'phone' => '+998901112233',
        ]);

        $response = $this->post(route('password.phone'), [
            'phone' => '90 111 22 33',
        ]);

        $response->assertRedirect(route('password.reset'));
        $response->assertSessionMissing('test_code');
        $this->assertEquals('+998901112233', session('password_reset_phone'));
        $this->assertDatabaseHas('password_reset_tokens', ['phone' => '+998901112233']);
    }

    public function test_token_is_deleted_after_five_failed_code_attempts(): void
    {
        $user = User::factory()->create([
            'phone' => '+998909998877',
        ]);

        DB::table('password_reset_tokens')->insert([
            'phone' => $user->phone,
            'token' => Hash::make('654321'),
            'attempts' => 0,
            'created_at' => now(),
        ]);

        for ($i = 1; $i <= 4; $i++) {
            $response = $this->from(route('password.reset'))->post(route('password.update'), [
                'phone' => $user->phone,
                'code' => '000000',
                'password' => 'newpassword123',
                'password_confirmation' => 'newpassword123',
            ]);

            $response->assertRedirect(route('password.reset'));
            $response->assertSessionHasErrors('code');
        }

        // 5th failed attempt invalidates and deletes the token
        $fifthResponse = $this->from(route('password.reset'))->post(route('password.update'), [
            'phone' => $user->phone,
            'code' => '000000',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $fifthResponse->assertRedirect(route('password.request'));
        $this->assertDatabaseMissing('password_reset_tokens', ['phone' => $user->phone]);
    }

    public function test_role_cannot_be_mass_assigned_on_user_creation(): void
    {
        $user = User::create([
            'name' => 'Hacker',
            'phone' => '+998905554433',
            'email' => 'hacker@example.com',
            'role' => 'admin',
            'password' => 'password123',
        ]);

        $this->assertSame('student', $user->role);
        $this->assertFalse($user->isAdmin());
    }

    public function test_stranger_cannot_hijack_pending_telegram_registration_without_token_or_from_another_chat(): void
    {
        $this->post(route('register.store'), [
            'name' => 'Asl Talaba',
            'phone' => '90 777 88 99',
            'email' => 'asl@example.com',
            'password' => 'secret1234',
            'password_confirmation' => 'secret1234',
        ])->assertRedirect(route('register.verify'));

        $regToken = (string) session('pending_registration.telegram_token');
        $this->assertNotEmpty($regToken);

        $telegram = app(TelegramNotificationService::class);

        // 1. Begona odam tokensiz oddiy /start yuborsa, asl foydalanuvchining tokeniga ulanib qolmasligi kerak
        $telegram->handleSingleUpdate([
            'message' => [
                'chat' => ['id' => 999111222],
                'from' => ['id' => 999111222, 'first_name' => 'Stranger'],
                'text' => '/start',
            ],
        ]);

        $this->assertNull(Cache::get('tg_start:'.$regToken));

        // 2. Asl foydalanuvchi o'zining maxsus reg_ tokeni bilan /start bosadi
        $telegram->handleSingleUpdate([
            'message' => [
                'chat' => ['id' => 111222333],
                'from' => ['id' => 111222333, 'first_name' => 'Asl'],
                'text' => '/start reg_'.$regToken,
            ],
        ]);

        $this->assertSame('111222333', (string) Cache::get('tg_start:'.$regToken));

        // 3. Begona chat_id xuddi shu reg_ tokenni qayta egallab ololmaydi va kod ololmaydi
        $telegram->handleSingleUpdate([
            'message' => [
                'chat' => ['id' => 999111222],
                'from' => ['id' => 999111222, 'first_name' => 'Attacker'],
                'text' => '/start reg_'.$regToken,
            ],
        ]);

        $this->assertSame('111222333', (string) Cache::get('tg_start:'.$regToken));

        $hijackAttempt = $telegram->verifySubscriptionAndSendCode('999111222', $regToken);
        $this->assertFalse($hijackAttempt['ok']);
    }

    public function test_unpaid_student_cannot_read_or_post_in_paid_course_chat(): void
    {
        $student = User::factory()->create();
        $paidCourse = Course::create([
            'title' => 'Yopiq Premium Kurs',
            'slug' => 'yopiq-premium-kurs',
            'description' => 'Maxsus kurs',
            'price' => 300000,
            'is_active' => true,
        ]);

        $this->actingAs($student)
            ->getJson(route('chat.messages', ['course_id' => $paidCourse->id]))
            ->assertForbidden();

        $this->actingAs($student)
            ->postJson(route('chat.store'), [
                'course_id' => $paidCourse->id,
                'message' => 'Bepul kirishga urinish',
            ])
            ->assertForbidden();
    }
}
