<?php

use App\Http\Middleware\AddFrameAncestorsHeader;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\DocumentManager\Models\DocumentNode;
use Modules\User\Models\User;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

/**
 * Feature tests for the document-manager preview endpoint's framing headers:
 *
 *  - GET /api/document-manager/nodes/{node}/preview streams the file inline
 *    (Content-Type + inline Content-Disposition).
 *  - The AddFrameAncestorsHeader route middleware adds
 *    `Content-Security-Policy: frame-ancestors …` so browsers ignore the web
 *    server's X-Frame-Options: SAMEORIGIN and the SPA (a different origin in
 *    production) can embed the preview in an <iframe>.
 *  - No header is sent when config('app.frame_ancestors') is empty.
 *
 * Same request pattern as the other DocumentManager tests: real jwt.cookies
 * middleware with a JWT minted for a freshly created user.
 */
beforeEach(function () {
    Storage::fake('local');

    $this->user = User::create([
        'name' => 'Preview Tester',
        'email' => 'preview-tester@example.com',
        'password' => 'password',
    ]);
    $this->token = JWTAuth::fromUser($this->user);
});

function previewFile(string $name, string $mime, string $content): DocumentNode
{
    $path = 'tests/'.uniqid().'.bin';
    UploadedFile::fake()->createWithContent($name, $content)->storeAs('documents', $path, 'local');

    return DocumentNode::query()->create([
        'name' => $name,
        'kind' => DocumentNode::KIND_FILE,
        'visibility' => DocumentNode::VIS_PRIVATE,
        'parent_id' => null,
        'owner_id' => auth()->id() ?? 1,
        'mime_type' => $mime,
        'extension' => 'pdf',
        'storage_path' => 'documents/'.$path,
        'size_bytes' => strlen($content),
    ]);
}

test('preview streams the file inline with content type and disposition', function () {
    $file = previewFile('spec.pdf', 'application/pdf', '%PDF-1.4 fake pdf');

    $this->withToken($this->token)
        ->get("/api/document-manager/nodes/{$file->id}/preview")
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf')
        ->assertHeader('Content-Disposition', 'inline; filename="spec.pdf"');
});

test('preview sends frame-ancestors csp header when configured', function () {
    config(['app.frame_ancestors' => 'https://sharmahardware.co.in https://www.sharmahardware.co.in']);
    $file = previewFile('framed.pdf', 'application/pdf', '%PDF-1.4 framed');

    $this->withToken($this->token)
        ->get("/api/document-manager/nodes/{$file->id}/preview")
        ->assertOk()
        ->assertHeader(
            'Content-Security-Policy',
            'frame-ancestors https://sharmahardware.co.in https://www.sharmahardware.co.in'
        );
});

test('preview sends no frame-ancestors header when config is empty', function () {
    config(['app.frame_ancestors' => '']);
    $file = previewFile('unframed.pdf', 'application/pdf', '%PDF-1.4 unframed');

    $response = $this->withToken($this->token)
        ->get("/api/document-manager/nodes/{$file->id}/preview")
        ->assertOk();

    expect($response->headers->get('Content-Security-Policy'))->toBeNull();
});

test('frame-ancestors middleware is bound to the preview route only', function () {
    $route = collect(Route::getRoutes()->get('GET'))
        ->first(fn ($route) => str_contains($route->uri(), 'document-manager/nodes/{node}/preview'));

    expect($route)->not->toBeNull()
        ->and($route->gatherMiddleware())->toContain(AddFrameAncestorsHeader::class);

    // A sibling route must NOT carry the header middleware.
    $browse = collect(Route::getRoutes()->get('GET'))
        ->first(fn ($route) => str_contains($route->uri(), 'document-manager/browse'));

    expect($browse->gatherMiddleware())->not->toContain(AddFrameAncestorsHeader::class);
});
