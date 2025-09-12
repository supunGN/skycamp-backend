<?php

class LocationController extends Controller
{
    /**
     * Proxy to Nominatim search (Sri Lanka-bounded)
     */
    public function search(Request $request, Response $response): void
    {
        $q = trim((string)$request->get('q', ''));
        if ($q === '') {
            $response->json(['success' => true, 'data' => []]);
            return;
        }

        $params = http_build_query([
            'format' => 'json',
            'q' => $q,
            'countrycodes' => 'lk',
            'limit' => 8,
            'addressdetails' => 1,
            'bounded' => 1,
            'viewbox' => '79.652,9.835,81.881,5.916',
        ]);

        $url = "https://nominatim.openstreetmap.org/search?{$params}";

        $ctx = stream_context_create([
            'http' => [
                'header' => "User-Agent: SkyCamp/1.0 (skycamp@example.com)\r\nAccept: application/json\r\n",
                'timeout' => 10,
            ],
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
            ],
        ]);

        $res = @file_get_contents($url, false, $ctx);
        if ($res === false) {
            $response->json(['success' => true, 'data' => []]);
            return;
        }

        header('Content-Type: application/json');
        echo $res;
        exit;
    }

    /**
     * Proxy to Nominatim reverse geocoding
     */
    public function reverse(Request $request, Response $response): void
    {
        $lat = (string)$request->get('lat', '');
        $lon = (string)$request->get('lon', '');
        if ($lat === '' || $lon === '') {
            $response->json(['success' => false, 'message' => 'lat/lon required'], 400);
            return;
        }

        $params = http_build_query([
            'format' => 'json',
            'lat' => $lat,
            'lon' => $lon,
            'zoom' => 14,
            'addressdetails' => 1,
        ]);

        $url = "https://nominatim.openstreetmap.org/reverse?{$params}";

        $ctx = stream_context_create([
            'http' => [
                'header' => "User-Agent: SkyCamp/1.0 (skycamp@example.com)\r\nAccept: application/json\r\n",
                'timeout' => 10,
            ],
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
            ],
        ]);

        $res = @file_get_contents($url, false, $ctx);
        if ($res === false) {
            $response->json(['success' => true, 'data' => null]);
            return;
        }

        header('Content-Type: application/json');
        echo $res;
        exit;
    }
}
