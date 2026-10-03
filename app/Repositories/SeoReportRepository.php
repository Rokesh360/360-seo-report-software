<?php

namespace App\Repositories;

use App\Models\SeoReport;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;

class SeoReportRepository
{
    public function paginate(int $perPage = 10): LengthAwarePaginator
    {
        return SeoReport::with('client')->latest()->paginate($perPage);
    }

    public function find(int $id): SeoReport
    {
        return SeoReport::with('client')->findOrFail($id);
    }

    public function create(array $data): SeoReport
    {
        return SeoReport::create($this->prepare($data));
    }

    public function update(int $id, array $data): SeoReport
    {
        $report = SeoReport::findOrFail($id);

        $oldImages      = collect($report->images ?? [])->pluck('path');
        $oldScoreImage  = $report->seo_score_image;

        // Collect keyword-level image paths before update
        $oldKeywordImages = collect($report->keywords ?? [])
            ->pluck('image_existing')
            ->filter()
            ->values();

        $payload = $this->prepare($data);
        $report->update($payload);

        // Remove replaced score image
        if (isset($payload['seo_score_image']) && $oldScoreImage) {
            Storage::disk('public')->delete($oldScoreImage);
        }

        // Remove general images that were removed from the form
        $kept = collect($payload['images'])->pluck('path');
        $oldImages->diff($kept)->each(fn($path) => Storage::disk('public')->delete($path));

        // Remove keyword screenshots that were removed
        $keptKeywordImages = collect($payload['keywords'])->pluck('image_existing')->filter();
        $oldKeywordImages->diff($keptKeywordImages)->each(fn($path) => Storage::disk('public')->delete($path));

        return $report;
    }

    public function delete(int $id): bool
    {
        return (bool) SeoReport::findOrFail($id)->delete();
    }

    public function paginateTrashed(int $perPage = 10): LengthAwarePaginator
    {
        return SeoReport::onlyTrashed()->with('client')->latest('deleted_at')->paginate($perPage);
    }

    public function restore(int $id): bool
    {
        return (bool) SeoReport::onlyTrashed()->findOrFail($id)->restore();
    }

    private function prepare(array $data): array
    {
        $data['on_page_items'] = $data['on_page_items'] ?? [];
        $data['backlinks_items']  = $data['backlinks_items']  ?? [];

        // SEO score image
        if (isset($data['seo_score_image'])) {
            $data['seo_score_image'] = $data['seo_score_image']->store('seo-reports', 'public');
        } else {
            unset($data['seo_score_image']);
        }

        // Keywords: keep keyword + pages + positions + optional screenshot
        $data['keywords'] = collect($data['keywords'] ?? [])
            ->map(function ($k) {
                $imagePath = null;

                if (!empty($k['image_file'])) {
                    $imagePath = $k['image_file']->store('seo-reports', 'public');
                } elseif (!empty($k['image_existing'])) {
                    $imagePath = $k['image_existing'];
                }

                return [
                    'keyword'        => $k['keyword'] ?? '',
                    'page'           => !empty($k['page'])     ? (int) $k['page']     : null,
                    'position'       => !empty($k['position']) ? (int) $k['position'] : null,
                    'image_existing' => $imagePath,
                ];
            })
            ->values()
            ->all();

        // General images
        $data['images'] = collect($data['images'] ?? [])
            ->map(function ($img) {
                $path = isset($img['file'])
                    ? $img['file']->store('seo-reports', 'public')
                    : ($img['existing'] ?? null);

                return $path ? ['title' => $img['title'] ?? '', 'path' => $path] : null;
            })
            ->filter()
            ->values()
            ->all();

        // Drop queries entirely (no longer used)
        unset($data['queries']);

        return $data;
    }
}
