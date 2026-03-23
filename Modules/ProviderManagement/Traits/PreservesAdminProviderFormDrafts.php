<?php

namespace Modules\ProviderManagement\Traits;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\File\UploadedFile as SymfonyUploadedFile;

/**
 * Stores uploaded files on validation failure (withInput() does not keep file inputs)
 * and re-attaches them on the next request before validation.
 */
trait PreservesAdminProviderFormDrafts
{
    protected function providerFormDraftSessionKey(string $formKey): string
    {
        return 'provider_admin_form_drafts.' . $formKey;
    }

    public function getProviderFormDraftManifest(string $formKey): ?array
    {
        return session($this->providerFormDraftSessionKey($formKey));
    }

    protected function clearProviderFormDraft(string $formKey): void
    {
        $manifest = $this->getProviderFormDraftManifest($formKey);
        if ($manifest && ! empty($manifest['base_dir'])) {
            try {
                Storage::disk('public')->deleteDirectory($manifest['base_dir']);
            } catch (\Throwable $e) {
                report($e);
            }
        }
        session()->forget($this->providerFormDraftSessionKey($formKey));
    }

    protected function persistProviderFormDraftAfterFailedValidation(Request $request, string $formKey): void
    {
        $sessionId = preg_replace('/[^a-zA-Z0-9_-]/', '', $request->session()->getId());
        $prev = $this->getProviderFormDraftManifest($formKey);
        if ($prev && ! empty($prev['base_dir'])) {
            try {
                Storage::disk('public')->deleteDirectory($prev['base_dir']);
            } catch (\Throwable $e) {
                report($e);
            }
        }

        $baseDir = 'provider-form-drafts/' . $sessionId . '/' . Str::random(16);
        $disk = Storage::disk('public');
        $disk->makeDirectory($baseDir);

        $manifest = [
            'base_dir' => $baseDir,
            'owner_session' => $sessionId,
            'files' => [],
        ];

        $storeOne = function ($uploaded, string $logicalKey, ?int $index = null) use ($disk, $baseDir, &$manifest) {
            if (! $uploaded) {
                return;
            }
            $realPath = $uploaded->getRealPath();
            if (! $realPath || ! @is_readable($realPath)) {
                return;
            }
            if ($uploaded->getError() !== UPLOAD_ERR_OK) {
                return;
            }
            $original = $uploaded->getClientOriginalName() ?: 'file';
            $safe = Str::random(8) . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $original);
            $relative = $baseDir . '/' . $safe;
            $disk->put($relative, file_get_contents($realPath));
            $entry = ['rel' => $relative, 'original' => $original];
            if ($index === null) {
                $manifest['files'][$logicalKey] = $entry;
            } else {
                if (! isset($manifest['files'][$logicalKey]) || ! is_array($manifest['files'][$logicalKey])) {
                    $manifest['files'][$logicalKey] = [];
                }
                $manifest['files'][$logicalKey][$index] = $entry;
            }
        };

        if ($request->hasFile('logo')) {
            $storeOne($request->file('logo'), 'logo');
        }
        if ($request->hasFile('contact_person_photo')) {
            $storeOne($request->file('contact_person_photo'), 'contact_person_photo');
        }

        $identityFiles = $request->file('identity_images', []);
        if (is_array($identityFiles)) {
            foreach ($identityFiles as $i => $f) {
                if ($f) {
                    $storeOne($f, 'identity_images', (int) $i);
                }
            }
        }

        $identityPdfs = $request->file('identity_pdf_files', []);
        if (is_array($identityPdfs)) {
            foreach ($identityPdfs as $i => $f) {
                if ($f) {
                    $storeOne($f, 'identity_pdf_files', (int) $i);
                }
            }
        }

        $companyImages = $request->file('company_identity_images', []);
        if (is_array($companyImages)) {
            foreach ($companyImages as $i => $f) {
                if ($f) {
                    $storeOne($f, 'company_identity_images', (int) $i);
                }
            }
        }

        $companyPdfs = $request->file('company_identity_pdf_files', []);
        if (is_array($companyPdfs)) {
            foreach ($companyPdfs as $i => $f) {
                if ($f) {
                    $storeOne($f, 'company_identity_pdf_files', (int) $i);
                }
            }
        }

        $addDocs = $request->input('additional_documents', []);
        if (is_array($addDocs)) {
            foreach ($addDocs as $i => $row) {
                $name = $row['name'] ?? '';
                $description = $row['description'] ?? '';
                $fileList = $request->file('additional_documents.' . $i . '.files');
                $storedRow = ['name' => $name, 'description' => $description, 'files' => []];
                if (is_array($fileList)) {
                    foreach ($fileList as $f) {
                        if (! $f) {
                            continue;
                        }
                        $rp = $f->getRealPath();
                        if (! $rp || ! @is_readable($rp)) {
                            continue;
                        }
                        if ($f->getError() !== UPLOAD_ERR_OK) {
                            continue;
                        }
                        $original = $f->getClientOriginalName() ?: 'file';
                        $safe = Str::random(8) . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $original);
                        $relative = $baseDir . '/' . $safe;
                        $disk->put($relative, file_get_contents($rp));
                        $storedRow['files'][] = ['rel' => $relative, 'original' => $original];
                    }
                }
                if ($name !== '' || $description !== '' || count($storedRow['files']) > 0) {
                    $manifest['files']['additional_documents'][] = $storedRow;
                }
            }
        }

        session([$this->providerFormDraftSessionKey($formKey) => $manifest]);
    }

    protected function makeUploadedFromDraft(string $relativePath, string $originalName): ?UploadedFile
    {
        $relativePath = ltrim($relativePath, '/');
        if (! Str::startsWith($relativePath, 'provider-form-drafts/')) {
            return null;
        }
        $disk = Storage::disk('public');
        if (! $disk->exists($relativePath)) {
            return null;
        }
        $fullPath = $disk->path($relativePath);
        if (! is_readable($fullPath)) {
            return null;
        }
        try {
            $mime = @mime_content_type($fullPath) ?: 'application/octet-stream';
            $symfonyFile = new SymfonyUploadedFile($fullPath, $originalName, $mime, UPLOAD_ERR_OK, true);

            return UploadedFile::createFromBase($symfonyFile);
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
    }

    protected function attachProviderFormDraftToRequest(Request $request, string $formKey): void
    {
        $manifest = $this->getProviderFormDraftManifest($formKey);
        if (! $manifest || empty($manifest['files'])) {
            return;
        }

        $base = $manifest['base_dir'] ?? '';
        $sessionId = preg_replace('/[^a-zA-Z0-9_-]/', '', $request->session()->getId());
        if ($base === '' || ! Str::startsWith($base, 'provider-form-drafts/')) {
            return;
        }
        $ownerSession = $manifest['owner_session'] ?? null;
        if ($ownerSession !== null && $ownerSession !== $sessionId) {
            return;
        }
        if ($ownerSession === null && ! Str::contains($base, 'provider-form-drafts/' . $sessionId . '/')) {
            return;
        }

        $files = $manifest['files'];

        if (! $request->hasFile('logo') && ! empty($files['logo']['rel'])) {
            $up = $this->makeUploadedFromDraft($files['logo']['rel'], $files['logo']['original'] ?? 'logo');
            if ($up) {
                $request->files->set('logo', $up);
            }
        }

        if (! $request->hasFile('contact_person_photo') && ! empty($files['contact_person_photo']['rel'])) {
            $up = $this->makeUploadedFromDraft($files['contact_person_photo']['rel'], $files['contact_person_photo']['original'] ?? 'photo');
            if ($up) {
                $request->files->set('contact_person_photo', $up);
            }
        }

        $this->attachDraftFileArray($request, 'identity_images', $files['identity_images'] ?? null);
        $this->attachDraftFileArray($request, 'identity_pdf_files', $files['identity_pdf_files'] ?? null);
        $this->attachDraftFileArray($request, 'company_identity_images', $files['company_identity_images'] ?? null);
        $this->attachDraftFileArray($request, 'company_identity_pdf_files', $files['company_identity_pdf_files'] ?? null);

        $this->attachDraftAdditionalDocuments($request, $files['additional_documents'] ?? null);
    }

    protected function attachDraftFileArray(Request $request, string $key, $entries): void
    {
        if (! is_array($entries) || count($entries) === 0) {
            return;
        }
        $incoming = $request->file($key);
        $hasNew = false;
        if (is_array($incoming)) {
            foreach ($incoming as $f) {
                if ($f) {
                    $hasNew = true;
                    break;
                }
            }
        } elseif ($incoming) {
            $hasNew = true;
        }
        if ($hasNew) {
            return;
        }

        $uploaded = [];
        ksort($entries);
        foreach ($entries as $idx => $meta) {
            if (empty($meta['rel'])) {
                continue;
            }
            $up = $this->makeUploadedFromDraft($meta['rel'], $meta['original'] ?? 'file');
            if ($up) {
                $uploaded[$idx] = $up;
            }
        }
        if (count($uploaded) > 0) {
            ksort($uploaded);
            $request->files->set($key, array_values($uploaded));
        }
    }

    protected function attachDraftAdditionalDocuments(Request $request, ?array $rows): void
    {
        if (! is_array($rows) || count($rows) === 0) {
            return;
        }

        $allFiles = $request->files->all();

        foreach ($rows as $i => $draftRow) {
            $existingFiles = data_get($allFiles, 'additional_documents.' . $i . '.files');
            $hasNew = false;
            if (is_array($existingFiles)) {
                foreach ($existingFiles as $f) {
                    if ($f) {
                        $hasNew = true;
                        break;
                    }
                }
            } elseif ($existingFiles) {
                $hasNew = true;
            }
            if ($hasNew) {
                continue;
            }

            $uploaded = [];
            foreach (($draftRow['files'] ?? []) as $meta) {
                if (empty($meta['rel'])) {
                    continue;
                }
                $up = $this->makeUploadedFromDraft($meta['rel'], $meta['original'] ?? 'file');
                if ($up) {
                    $uploaded[] = $up;
                }
            }
            if (count($uploaded) > 0) {
                if (! isset($allFiles['additional_documents']) || ! is_array($allFiles['additional_documents'])) {
                    $allFiles['additional_documents'] = [];
                }
                if (! isset($allFiles['additional_documents'][$i]) || ! is_array($allFiles['additional_documents'][$i])) {
                    $allFiles['additional_documents'][$i] = [];
                }
                $allFiles['additional_documents'][$i]['files'] = $uploaded;
            }
        }

        $request->files->replace($allFiles);
    }

    protected function backWithInputAndDraft(Request $request, string $formKey): RedirectResponse
    {
        $this->persistProviderFormDraftAfterFailedValidation($request, $formKey);

        return back()->withInput();
    }
}
