<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;

class SupabaseStorageService
{
    protected $url;
    protected $serviceKey;
    protected $bucket;

    public function __construct()
    {
        $this->url = config('supabase.url');
        $this->serviceKey = config('supabase.service_key');
        $this->bucket = config('supabase.bucket');
    }

    public function upload($filename, $content)
    {
        // Use upsert to overwrite older backup with same name
        $endpoint = $this->url . "/storage/v1/object/" . $this->bucket . "/" . $filename . "?upsert=true";
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->serviceKey,
            'apikey' => $this->serviceKey,
            'Content-Type' => 'application/octet-stream',
        ])->post($endpoint, $content);

        \Log::info('Supabase upload response', [
            'status' => $response->status(),
            'body' => $response->body()
        ]);

        return [
            'success' => $response->successful(),
            'status' => $response->status(),
            'body' => $response->json()
        ];
    }

    public function listFiles($limit = 100, $prefix = '')
    {
        $endpoint = $this->url . "/storage/v1/object/list/" . $this->bucket;
        $payload = [
            'prefix' => $prefix,
            'limit' => $limit,
            'offset' => 0,
            'sortBy' => [ 'column' => 'name', 'order' => 'asc' ]
        ];
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->serviceKey,
            'apikey' => $this->serviceKey,
            'Content-Type' => 'application/json',
        ])->post($endpoint, $payload);
        if ($response->successful()) {
            return $response->json();
        }
        \Log::warning('Supabase list files failed', ['status' => $response->status(), 'body' => $response->body()]);
        return [];
    }

    public function getDownloadUrl($filename)
    {
        // Assuming bucket is public; if not, need signed URL generation.
        return $this->url . "/storage/v1/object/public/" . $this->bucket . "/" . ltrim($filename, '/');
    }

    public function signUrl($filename, $expiresIn = 3600)
    {
        $endpoint = $this->url . "/storage/v1/object/sign/" . $this->bucket;
        $payload = [
            'expiresIn' => $expiresIn,
            'paths' => [ ltrim($filename, '/') ]
        ];
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->serviceKey,
            'apikey' => $this->serviceKey,
            'Content-Type' => 'application/json',
        ])->post($endpoint, $payload);
        if ($response->successful()) {
            $data = $response->json();
            return $data['signedUrls'][0]['signedUrl'] ?? null;
        }
        \Log::warning('Supabase sign URL failed', ['status' => $response->status(), 'body' => $response->body()]);
        return null;
    }
}
