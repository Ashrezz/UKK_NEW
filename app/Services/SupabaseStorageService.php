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
        $endpoint = $this->url . "/storage/v1/object/" . $this->bucket . "/" . $filename;
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->serviceKey,
            'apikey' => $this->serviceKey,
            'Content-Type' => 'application/sql',
        ])->put($endpoint, $content);
        return $response->successful();
    }

    public function listFiles()
    {
        $endpoint = $this->url . "/storage/v1/object/list/" . $this->bucket . "?limit=100";
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->serviceKey,
            'apikey' => $this->serviceKey,
        ])->get($endpoint);
        if ($response->successful()) {
            return $response->json();
        }
        return [];
    }

    public function getDownloadUrl($filename)
    {
        $endpoint = $this->url . "/storage/v1/object/public/" . $this->bucket . "/" . $filename;
        return $endpoint;
    }
}
