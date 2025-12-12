<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;

class PredictionController extends Controller
{
    public function predict(Request $request)
    {
        $client = new Client();

        $health = null;

        // Quick health check to provide clearer diagnostics when API is down/misconfigured
        try {
            $healthResp = $client->get("http://127.0.0.1:8001/health", [
                "timeout" => 5,
                "connect_timeout" => 2
            ]);
            $health = json_decode($healthResp->getBody(), true);

            if (!isset($health['status']) || $health['status'] !== 'ok') {
                return view('predict', [
                    "prediction" => null,
                    "error" => 'ML API health check failed: ' . json_encode($health),
                    "ml_health" => $health
                ]);
            }
        } catch (RequestException $e) {
            $resp = $e->getResponse();
            $body = $resp ? (string)$resp->getBody() : $e->getMessage();
            return view('predict', [
                "prediction" => null,
                "error" => 'ML API unreachable: ' . $body,
                "ml_health" => $health
            ]);
        } catch (\Exception $e) {
            return view('predict', [
                "prediction" => null,
                "error" => 'Health check error: ' . $e->getMessage(),
                "ml_health" => $health
            ]);
        }

        // Build explicit payload matching the required schema
        $payload = [
            "distance_km" => $request->input('distance_km', $request->input('distance', null)),
            "avg_speed" => $request->input('avg_speed', null),
            "is_weekend" => $request->has('is_weekend') ? 1 : (int)$request->input('is_weekend', 0),
            "is_holiday" => $request->has('is_holiday') ? 1 : (int)$request->input('is_holiday', 0),
            "date" => $request->input('date', $request->input('travel_date', null)),
            "time" => $request->input('time', $request->input('hour_of_day', null)),
            "days_to_christmas" => $request->input('days_to_christmas', null),
            "days_to_new_year" => $request->input('days_to_new_year', null),
            "route" => $request->input('route', $request->input('route_id', null)),
            "origin" => $request->input('origin', null),
            "destination" => $request->input('destination', null),
            // optional compatibility fields
            "distance_speed_interaction" => $request->input('distance_speed_interaction', null)
        ];

        try {
            $response = $client->post("http://127.0.0.1:8001/predict", [
                "json" => $payload,
                "timeout" => 60,
                "connect_timeout" => 10
            ]);

            $result = json_decode($response->getBody(), true);

            return view('predict', [
                "prediction" => $result['prediction'] ?? null,
                "error" => $result['error'] ?? null,
                "ml_health" => $health
            ]);
        } catch (ClientException $e) {
            $resp = $e->getResponse();
            $body = $resp ? (string)$resp->getBody() : $e->getMessage();
            $decoded = json_decode($body, true);
            $errMsg = $decoded['detail'] ?? $body;
            return view('predict', [
                "prediction" => null,
                "error" => $errMsg,
                "ml_health" => $health
            ]);
        } catch (\Exception $e) {
            return view('predict', [
                "prediction" => null,
                "error" => $e->getMessage(),
                "ml_health" => $health
            ]);
        }
    }
}