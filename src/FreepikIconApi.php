<?php

namespace App\Core\IconApi;

use Illuminate\Support\Facades\Cache;

class FreepikIconApi
{
    protected string $cookie;

    public function __construct()
    {
        $this->cookie = $this->getCookieFromStorage();
    }

    protected function getCookieFromStorage(): string
    {
        return Cache::get('freepik_cookie', '');
    }

    public function updateCookie(): void
    {
        $ch = curl_init('https://www.freepik.com/icons');

        curl_setopt_array($ch, array(
          CURLOPT_URL => 'https://www.freepik.com/icons',
          CURLOPT_HEADER => true,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_SSL_VERIFYPEER => false,
          CURLOPT_ENCODING => '',
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 0,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => 'GET',
          CURLOPT_HTTPHEADER => array(
            "Accept-Encoding: gzip, deflate",
            "Connection: keep-alive",
            "Accept: application/json, text/plain, */*",
            "Host: www.freepik.com",
            "User-Agent: PostmanRuntime/7.43.4",
          ),
        ));

        $response = curl_exec($ch);
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $headers_raw = substr($response, 0, $header_size);
        curl_close($ch);

        preg_match_all('/^Set-Cookie:\s*([^;]+)/mi', $headers_raw, $matches);
        foreach ($matches[1] as $cookie_line) {
            if (strpos($cookie_line, 'ak_bmsc=') === 0) {
                $cookie = trim(str_replace('ak_bmsc=', '', $cookie_line));
                Cache::put('freepik_cookie', $cookie, now()->addMinutes(30));
                $this->cookie = $cookie;
                break;
            }
        }
    }

    public function search(string $term, int $page = 1, int $attempts = 0): ?array
    {
        if (!$this->cookie) {
            $this->updateCookie();
        }

        $query = http_build_query([
            'filters[icon_type][]' => 'standard',
            'format[search]' => '1',
            'locale' => 'en',
            'order' => 'relevance',
            'page' => $page,
            'term' => $term,
            'type[icon]' => '1',
        ]);

        $ch = curl_init();
        curl_setopt_array($ch, array(
          CURLOPT_URL => "https://www.freepik.com/api/icons?$query",
          CURLOPT_HEADER => true,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_SSL_VERIFYPEER => false,
          CURLOPT_ENCODING => '',
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 0,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => 'GET',
          CURLOPT_HTTPHEADER => array(
            "Accept-Encoding: gzip, deflate",
            "Connection: keep-alive",
            "Accept: application/json, text/plain, */*",
            "Host: www.freepik.com",
            "User-Agent: PostmanRuntime/7.43.4",
            "Cookie: ak_bmsc={$this->cookie}"
          ),
        ));

        $response = curl_exec($ch);
        curl_close($ch);

        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $body = substr($response, $header_size);

        $data = json_decode($body, true);

        if ((!$data || !is_array($data)) && $attempts < 1) {
            $this->updateCookie();
            // handle cookie expiration when its read from old cache
            return $this->search($term, $page, $attempts + 1);
        }

        return $data;
    }

}
