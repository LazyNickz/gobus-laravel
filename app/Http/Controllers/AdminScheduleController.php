<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Schedule;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class AdminScheduleController extends Controller
{


    public function index()
    {
        // Check if admin is authenticated via session
        if (!session()->has('gobus_admin_logged')) {
            return redirect('/admin-login')->with('error', 'Please login to access admin panel.');
        }
        
        return view('admin-schedules');
    }



    public function upload(Request $request)
    {
        // Check if admin is authenticated via session
        if (!session()->has('gobus_admin_logged')) {
            return redirect('/admin-login')->with('error', 'Please login to access admin panel.');
        }
        
        $validator = Validator::make($request->all(), [
            'csv_file' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {
            $file = $request->file('csv_file');
            $path = $file->store('temp');

            $csvData = array_map('str_getcsv', file(storage_path('app/' . $path)));
            $header = array_shift($csvData);

            // Expected columns
            $expectedColumns = ['route_from', 'route_to', 'departure_time', 'arrival_time', 'bus_number', 'seats', 'available_seats', 'fare', 'status'];
            $header = array_map('trim', $header);

            if ($header !== $expectedColumns) {
                Storage::delete($path);
                return redirect()->back()->with('error', 'CSV format is incorrect. Expected columns: ' . implode(', ', $expectedColumns));
            }

            $created = 0;
            $errors = [];

            foreach ($csvData as $rowIndex => $row) {
                try {
                    $data = array_combine($header, array_map('trim', $row));

                    // Validate required fields
                    if (empty($data['route_from']) || empty($data['route_to']) || empty($data['departure_time'])) {
                        $errors[] = "Row " . ($rowIndex + 2) . ": Missing required fields";
                        continue;
                    }

                    // Create schedule
                    Schedule::create([
                        'route_from' => $data['route_from'],
                        'route_to' => $data['route_to'],
                        'departure_time' => $data['departure_time'],
                        'arrival_time' => $data['arrival_time'] ?: null,
                        'bus_number' => $data['bus_number'] ?: null,
                        'seats' => (int) ($data['seats'] ?: 0),
                        'available_seats' => (int) ($data['available_seats'] ?: $data['seats']),
                        'fare' => (float) ($data['fare'] ?: 0),
                        'status' => $data['status'] ?: 'active',
                        'created_by' => $request->session()->get('gobus_admin_email'),
                    ]);

                    $created++;
                } catch (\Exception $e) {
                    $errors[] = "Row " . ($rowIndex + 2) . ": " . $e->getMessage();
                }
            }

            Storage::delete($path);

            $message = "Successfully created $created schedules.";
            if (!empty($errors)) {
                $message .= " Errors: " . implode('; ', $errors);
            }

            return redirect()->back()->with('success', $message);

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error processing file: ' . $e->getMessage());
        }
    }
}
