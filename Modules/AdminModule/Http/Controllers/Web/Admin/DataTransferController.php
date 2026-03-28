<?php

namespace Modules\AdminModule\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Response;
use InvalidArgumentException;
use Modules\AdminModule\Services\DataTransfer\ServiceCatalogTransfer;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DataTransferController extends Controller
{
    private const DOMAINS = ['service'];

    /** @var array<string, list<string>> */
    private const DOMAIN_GATES = [
        'service' => ['service_view', 'category_view'],
    ];

    public function index()
    {
        $this->ensurePageAccess();

        $domains = array_values(array_filter(self::DOMAINS, fn (string $d) => $this->domainAllowed($d)));

        $domainLabels = [
            'service' => translate('Service'),
        ];

        return view('adminmodule::admin.data-transfer.index', [
            'domains' => $domains,
            'domainLabels' => $domainLabels,
        ]);
    }

    public function export(Request $request, string $domain): StreamedResponse
    {
        $this->authorizeDomain($domain);

        $payload = match ($domain) {
            'service' => app(ServiceCatalogTransfer::class)->export(),
            default => throw new InvalidArgumentException('Invalid domain.'),
        };

        $filename = 'data-transfer-'.$domain.'-'.date('Y-m-d-His').'.json';

        return Response::streamDownload(static function () use ($payload) {
            echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR);
        }, $filename, ['Content-Type' => 'application/json; charset=UTF-8']);
    }

    public function preview(Request $request)
    {
        $validated = $request->validate([
            'domain' => 'required|in:'.implode(',', self::DOMAINS),
            'file' => 'required|file|mimes:json,txt|max:102400',
        ]);

        $domain = $validated['domain'];
        $this->authorizeDomain($domain);

        try {
            $payload = $this->decodeUploadedJson($request->file('file'));
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        try {
            $preview = match ($domain) {
                'service' => app(ServiceCatalogTransfer::class)->preview($payload),
                default => throw new InvalidArgumentException('Invalid domain.'),
            };
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $uploaded = $request->file('file');

        return response()->json([
            'preview' => $preview,
            'upload' => [
                'name' => $uploaded->getClientOriginalName(),
                'size' => $uploaded->getSize(),
            ],
            'export_meta' => $payload['meta'] ?? null,
        ]);
    }

    public function import(Request $request)
    {
        $validated = $request->validate([
            'domain' => 'required|in:'.implode(',', self::DOMAINS),
            'file' => 'required|file|mimes:json,txt|max:102400',
        ]);

        $domain = $validated['domain'];
        $this->authorizeDomain($domain);

        try {
            $payload = $this->decodeUploadedJson($request->file('file'));
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        try {
            $result = match ($domain) {
                'service' => app(ServiceCatalogTransfer::class)->import($payload),
                default => throw new InvalidArgumentException('Invalid domain.'),
            };
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Import failed: '.$e->getMessage(),
            ], 500);
        }

        return response()->json(['result' => $result]);
    }

    private function decodeUploadedJson($uploadedFile): array
    {
        $path = $uploadedFile->getRealPath() ?: $uploadedFile->getPathname();
        $raw = file_get_contents($path);
        if ($raw === false || $raw === '') {
            throw new InvalidArgumentException('Empty file.');
        }
        try {
            $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new InvalidArgumentException('Invalid JSON file.');
        }
        if (! is_array($data)) {
            throw new InvalidArgumentException('Invalid JSON structure.');
        }

        return $data;
    }

    private function ensurePageAccess(): void
    {
        foreach (self::DOMAIN_GATES as $gates) {
            foreach ($gates as $gate) {
                if (Gate::allows($gate)) {
                    return;
                }
            }
        }
        abort(403);
    }

    private function authorizeDomain(string $domain): void
    {
        if (! $this->domainAllowed($domain)) {
            abort(403);
        }
    }

    private function domainAllowed(string $domain): bool
    {
        foreach (self::DOMAIN_GATES[$domain] ?? [] as $gate) {
            if (Gate::allows($gate)) {
                return true;
            }
        }

        return false;
    }
}
