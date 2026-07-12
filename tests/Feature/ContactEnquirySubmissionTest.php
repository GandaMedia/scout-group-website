<?php

use App\Mail\ContactEnquirySubmitted;
use App\Models\ContactEnquiry;
use App\Settings\ContactSettings;
use App\Settings\GroupProfileSettings;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

it('stores enquiries and sends an email when the contact form is submitted', function () {
    $settings = configureContactSettings();
    config()->set('services.turnstile.site_key', 'test-site-key');
    config()->set('services.turnstile.secret_key', 'test-secret-key');

    Mail::fake();
    Http::fake([
        'https://challenges.cloudflare.com/turnstile/v0/siteverify' => Http::response([
            'success' => true,
        ]),
    ]);

    $response = $this->from(route('page.show', ['page' => 'contact']))
        ->post(route('contact-enquiries.store'), [
            'name' => 'Taylor Scout',
            'email' => 'taylor@example.com',
            'message' => 'We would love to arrange a taster session.',
            'turnstile_token' => 'valid-turnstile-token',
        ]);

    $response->assertRedirect(route('page.show', ['page' => 'contact']))
        ->assertSessionHasNoErrors()
        ->assertSessionHas('contactEnquirySubmitted', $settings->success_message);

    $contactEnquiry = ContactEnquiry::query()->sole();

    expect($contactEnquiry->name)->toBe('Taylor Scout')
        ->and($contactEnquiry->email)->toBe('taylor@example.com')
        ->and($contactEnquiry->reviewed_at)->toBeNull();

    Mail::assertSent(ContactEnquirySubmitted::class, function (ContactEnquirySubmitted $mailable) use ($contactEnquiry): bool {
        return $mailable->contactEnquiry->is($contactEnquiry)
            && $mailable->hasTo('leaders@example.com')
            && $mailable->hasFrom('hello@scout-group.test')
            && $mailable->hasReplyTo('taylor@example.com');
    });
});

it('renders contact enquiry mail with group profile branding', function () {
    configureContactSettings();

    $contactEnquiry = ContactEnquiry::factory()->create([
        'name' => 'Taylor Scout',
        'email' => 'taylor@example.com',
        'message' => 'We would love to arrange a taster session.',
        'submitted_at' => now(),
    ]);

    $mailable = new ContactEnquirySubmitted($contactEnquiry);

    $mailable->assertFrom('hello@scout-group.test');
    $mailable->assertHasReplyTo('taylor@example.com');
    $mailable->assertSeeInHtml('Example Scout Group');
    $mailable->assertSeeInHtml('Charity No: 289022');
    $mailable->assertSeeInText('Example Scouts');
});

it('rejects invalid submissions without storing an enquiry', function () {
    Mail::fake();

    $response = $this->post(route('contact-enquiries.store'), [
        'name' => '',
        'email' => 'not-an-email',
        'message' => '',
    ]);

    $response->assertSessionHasErrors([
        'name',
        'email',
        'message',
        'turnstile_token',
    ]);

    expect(ContactEnquiry::query()->count())->toBe(0);

    Mail::assertNothingOutgoing();
});

it('rejects submissions when turnstile verification fails', function () {
    configureContactSettings();
    config()->set('services.turnstile.site_key', 'test-site-key');
    config()->set('services.turnstile.secret_key', 'test-secret-key');

    Mail::fake();
    Http::fake([
        'https://challenges.cloudflare.com/turnstile/v0/siteverify' => Http::response([
            'success' => false,
        ]),
    ]);

    $response = $this->post(route('contact-enquiries.store'), [
        'name' => 'Taylor Scout',
        'email' => 'taylor@example.com',
        'message' => 'We would love to arrange a taster session.',
        'turnstile_token' => 'invalid-token',
    ]);

    $response->assertSessionHasErrors([
        'turnstile',
    ]);

    expect(ContactEnquiry::query()->count())->toBe(0);

    Mail::assertNothingOutgoing();
});

function configureContactSettings(): ContactSettings
{
    $groupProfileSettings = app(GroupProfileSettings::class);
    $groupProfileSettings->group_name = 'Example Scout Group';
    $groupProfileSettings->group_short_name = 'Example Scouts';
    $groupProfileSettings->website_url = 'https://scout-group.test';
    $groupProfileSettings->mail_from_name = 'Example Scout Group';
    $groupProfileSettings->mail_from_address = 'hello@scout-group.test';
    $groupProfileSettings->contact_recipient_name = 'Example Scouts';
    $groupProfileSettings->contact_recipient_email = 'leaders@example.com';
    $groupProfileSettings->headquarters_label = 'Example Scout Headquarters';
    $groupProfileSettings->headquarters_address = "Example Scout Headquarters\nExampletown";
    $groupProfileSettings->map_embed_url = 'https://maps.example.test/embed';
    $groupProfileSettings->charity_number = '289022';
    $groupProfileSettings->charity_register_url = 'https://register.example.test/289022';
    $groupProfileSettings->district_name = 'Example Scout District';
    $groupProfileSettings->district_url = 'https://district.example.org/';
    $groupProfileSettings->save();

    $settings = app(ContactSettings::class);
    $settings->success_message = 'Thanks for getting in touch. We will reply soon.';
    $settings->save();

    return $settings;
}
