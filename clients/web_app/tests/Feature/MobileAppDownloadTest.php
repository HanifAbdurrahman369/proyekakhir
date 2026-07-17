<?php

namespace Tests\Feature;

use Tests\TestCase;

class MobileAppDownloadTest extends TestCase
{
    public function test_guest_is_redirected_to_login_and_download_intent_is_remembered(): void
    {
        $this->get('/download-mobile-app')
            ->assertRedirect('/login')
            ->assertSessionHas('pending_mobile_app_download', true);
    }

    public function test_supported_mobile_role_can_download_current_sipetani_apk(): void
    {
        $apkPath = storage_path('app/SiPetani.apk');

        $this->assertFileExists($apkPath);
        $this->assertGreaterThan(1_000_000, filesize($apkPath));

        $response = $this->withSession([
            'token' => 'mobile-download-test-token',
            'role_id' => 1,
        ])->get('/download-mobile-app/file');

        $response->assertOk()
            ->assertDownload('SiPetani.apk')
            ->assertHeader('Content-Type', 'application/vnd.android.package-archive');

        $cacheControl = $response->headers->get('Cache-Control');
        $this->assertStringContainsString('no-store', $cacheControl);
        $this->assertStringContainsString('no-cache', $cacheControl);
        $this->assertStringContainsString('private', $cacheControl);
        $this->assertStringNotContainsString('public', $cacheControl);
        $response->assertHeader('Pragma', 'no-cache')
            ->assertHeader('Expires', '0')
            ->assertHeader('X-SiPetani-APK-SHA256', hash_file('sha256', $apkPath));
        $this->assertNotEmpty($response->headers->get('X-SiPetani-APK-Version'));
    }

    public function test_download_page_uses_a_fingerprinted_apk_url(): void
    {
        $response = $this->withSession([
            'token' => 'mobile-download-test-token',
            'role_id' => 2,
        ])->get('/download-mobile-app');

        $response->assertOk()
            ->assertSee('Versi APK')
            ->assertSee('1.2.1')
            ->assertSee(substr(hash_file('sha256', storage_path('app/SiPetani.apk')), 0, 16));
    }

    public function test_unsupported_role_cannot_download_mobile_application(): void
    {
        $this->withSession([
            'token' => 'official-test-token',
            'role_id' => 3,
        ])->get('/download-mobile-app/file')
            ->assertForbidden();
    }
}
