<?php

namespace Tests\Unit;

use App\Support\SeekableRecordingResponse;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SeekableRecordingResponseTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        parent::setUp();

        $this->root = storage_path('framework/testing/seekable-recording');
        if (! is_dir($this->root)) {
            mkdir($this->root, 0755, true);
        }

        config([
            'filesystems.disks.seek_test' => [
                'driver' => 'local',
                'root' => $this->root,
            ],
        ]);
        Storage::forgetDisk('seek_test');
        file_put_contents($this->root.'/clip.bin', '0123456789abcdef');
    }

    protected function tearDown(): void
    {
        @unlink($this->root.'/clip.bin');
        parent::tearDown();
    }

    public function test_full_request_advertises_byte_ranges(): void
    {
        request()->headers->remove('Range');

        $response = SeekableRecordingResponse::fromDisk('seek_test', 'clip.bin', 'audio/mpeg', 'clip.mp3');
        $response->prepare(request());

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('bytes', $response->headers->get('Accept-Ranges'));
        $this->assertSame('16', $response->headers->get('Content-Length'));
        $this->assertSame('audio/mpeg', $response->headers->get('Content-Type'));
    }

    public function test_range_request_returns_the_requested_slice(): void
    {
        request()->headers->set('Range', 'bytes=4-7');

        $response = SeekableRecordingResponse::fromDisk('seek_test', 'clip.bin', 'audio/wav');
        $response->prepare(request());

        $this->assertSame(206, $response->getStatusCode());
        $this->assertSame('bytes 4-7/16', $response->headers->get('Content-Range'));
        $this->assertSame('4', $response->headers->get('Content-Length'));

        ob_start();
        $response->sendContent();
        $body = ob_get_clean();

        $this->assertSame('4567', $body);
    }
}
