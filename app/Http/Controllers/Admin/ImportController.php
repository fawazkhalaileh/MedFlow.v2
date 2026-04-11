<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Company;
use App\Models\ImportLog;
use App\Models\Patient;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ImportController extends Controller
{
    // -----------------------------------------------------------------------
    // System field definitions
    // -----------------------------------------------------------------------

    private function patientFields(): array
    {
        return [
            'first_name'              => 'First Name',
            'last_name'               => 'Last Name',
            'email'                   => 'Email',
            'phone'                   => 'Phone',
            'date_of_birth'           => 'Date of Birth',
            'gender'                  => 'Gender',
            'nationality'             => 'Nationality',
            'address'                 => 'Address',
            'city'                    => 'City',
            'status'                  => 'Status',
            'source'                  => 'Source',
            'internal_notes'          => 'Internal Notes',
            'emergency_contact_name'  => 'Emergency Contact Name',
            'emergency_contact_phone' => 'Emergency Contact Phone',
            'consent_given'           => 'Consent Given',
        ];
    }

    private function appointmentFields(): array
    {
        return [
            'patient_code'     => 'Patient Code',
            'patient_email'    => 'Patient Email',
            'patient_phone'    => 'Patient Phone',
            'scheduled_date'   => 'Scheduled Date',
            'scheduled_time'   => 'Scheduled Time',
            'service_name'     => 'Service Name',
            'status'           => 'Status',
            'duration_minutes' => 'Duration (Minutes)',
            'reason_notes'     => 'Reason / Notes',
        ];
    }

    private function validAppointmentStatuses(): array
    {
        return [
            'scheduled', 'confirmed', 'arrived',
            'in_progress', 'completed', 'cancelled', 'no_show', 'rescheduled',
        ];
    }

    // -----------------------------------------------------------------------
    // index() — show import page with recent import logs
    // -----------------------------------------------------------------------

    public function index()
    {
        $logs = ImportLog::with('user')
            ->latest()
            ->limit(20)
            ->get();

        return view('admin.import.index', compact('logs'));
    }

    // -----------------------------------------------------------------------
    // upload() — receive file, parse headers + first 5 rows, store in session
    // -----------------------------------------------------------------------

    public function upload(Request $request)
    {
        $request->validate([
            'file'        => 'required|file|max:10240|mimes:csv,txt,xlsx',
            'import_type' => 'required|in:patients,appointments',
        ]);

        $file        = $request->file('file');
        $importType  = $request->input('import_type');
        $originalName = $file->getClientOriginalName();

        // Store uploaded file
        $dir = storage_path('app/imports');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $storedFilename = uniqid('import_') . '_' . Str::slug(pathinfo($originalName, PATHINFO_FILENAME)) . '.csv';
        $filepath = $dir . DIRECTORY_SEPARATOR . $storedFilename;
        $file->move($dir, $storedFilename);

        // Parse CSV
        $handle = fopen($filepath, 'r');
        if (!$handle) {
            return back()->withErrors(['file' => 'Could not open uploaded file.']);
        }

        // Skip BOM if present
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        $headers = fgetcsv($handle);
        if (!$headers) {
            fclose($handle);
            return back()->withErrors(['file' => 'Could not read CSV headers.']);
        }

        // Trim headers
        $headers = array_map('trim', $headers);

        $sample = [];
        for ($i = 0; $i < 5; $i++) {
            $row = fgetcsv($handle);
            if ($row === false) break;
            $sample[] = $row;
        }
        fclose($handle);

        // Store in session
        session([
            'import_headers'  => $headers,
            'import_sample'   => $sample,
            'import_type'     => $importType,
            'import_filepath' => $filepath,
            'import_filename' => $originalName,
        ]);

        return redirect()->route('admin.import.preview');
    }

    // -----------------------------------------------------------------------
    // preview() — show the column mapping page
    // -----------------------------------------------------------------------

    public function preview()
    {
        if (!session()->has('import_headers')) {
            return redirect()->route('admin.import.index')
                ->with('error', 'No file uploaded. Please start the import process again.');
        }

        $headers    = session('import_headers');
        $sample     = session('import_sample');
        $importType = session('import_type');

        $systemFields = $importType === 'patients'
            ? $this->patientFields()
            : $this->appointmentFields();

        return view('admin.import.preview', compact('headers', 'sample', 'importType', 'systemFields'));
    }

    // -----------------------------------------------------------------------
    // validate_import() — full row validation
    // -----------------------------------------------------------------------

    public function validate_import(Request $request)
    {
        $request->validate([
            'column_map' => 'required|array',
        ]);

        if (!session()->has('import_filepath')) {
            return redirect()->route('admin.import.index')
                ->with('error', 'Session expired. Please re-upload the file.');
        }

        $columnMap  = $request->input('column_map'); // [csv_col => system_field]
        $importType = session('import_type');
        $filepath   = session('import_filepath');

        // Filter out "skip" entries
        $columnMap = array_filter($columnMap, fn($v) => $v !== '' && $v !== '_skip');

        $errors      = [];
        $validRows   = [];
        $rowNumber   = 1; // header = row 0

        $handle = fopen($filepath, 'r');
        if (!$handle) {
            return back()->withErrors(['file' => 'Could not re-open uploaded file.']);
        }

        // Skip BOM
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        $headers = fgetcsv($handle); // skip header row
        $headers = array_map('trim', $headers);

        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;

            // Skip completely empty rows
            if (empty(array_filter($row))) {
                continue;
            }

            // Map CSV columns to system fields
            $mapped = [];
            foreach ($columnMap as $csvCol => $systemField) {
                $colIndex = array_search($csvCol, $headers);
                if ($colIndex !== false && isset($row[$colIndex])) {
                    $mapped[$systemField] = trim($row[$colIndex]);
                }
            }

            $rowErrors = [];

            if ($importType === 'patients') {
                $rowErrors = $this->validatePatientRow($mapped, $rowNumber);
            } else {
                $rowErrors = $this->validateAppointmentRow($mapped, $rowNumber);
            }

            if (empty($rowErrors)) {
                $validRows[] = $mapped;
            } else {
                foreach ($rowErrors as $err) {
                    $errors[] = array_merge($err, ['raw_data' => implode(', ', array_slice($row, 0, 5))]);
                }
            }
        }

        fclose($handle);

        $totalRows = ($rowNumber - 1); // subtract header

        session([
            'import_column_map'  => $columnMap,
            'import_total_rows'  => $totalRows,
            'import_valid_count' => count($validRows),
            'import_errors'      => $errors,
        ]);

        return redirect()->route('admin.import.confirm');
    }

    private function validatePatientRow(array $mapped, int $rowNumber): array
    {
        $errors = [];

        if (empty($mapped['first_name'])) {
            $errors[] = ['row_number' => $rowNumber, 'field' => 'first_name', 'message' => 'First name is required'];
        }

        if (empty($mapped['email']) && empty($mapped['phone'])) {
            $errors[] = ['row_number' => $rowNumber, 'field' => 'email/phone', 'message' => 'Either email or phone is required'];
        }

        if (!empty($mapped['date_of_birth'])) {
            try {
                Carbon::parse($mapped['date_of_birth']);
            } catch (\Exception $e) {
                $errors[] = ['row_number' => $rowNumber, 'field' => 'date_of_birth', 'message' => 'Invalid date format: ' . $mapped['date_of_birth']];
            }
        }

        return $errors;
    }

    private function validateAppointmentRow(array $mapped, int $rowNumber): array
    {
        $errors = [];

        if (empty($mapped['patient_code']) && empty($mapped['patient_email']) && empty($mapped['patient_phone'])) {
            $errors[] = ['row_number' => $rowNumber, 'field' => 'patient', 'message' => 'At least one of patient_code, patient_email, or patient_phone is required'];
        }

        if (empty($mapped['scheduled_date'])) {
            $errors[] = ['row_number' => $rowNumber, 'field' => 'scheduled_date', 'message' => 'Scheduled date is required'];
        } else {
            try {
                Carbon::parse($mapped['scheduled_date']);
            } catch (\Exception $e) {
                $errors[] = ['row_number' => $rowNumber, 'field' => 'scheduled_date', 'message' => 'Invalid date format: ' . $mapped['scheduled_date']];
            }
        }

        if (!empty($mapped['status']) && !in_array(strtolower($mapped['status']), $this->validAppointmentStatuses())) {
            $errors[] = ['row_number' => $rowNumber, 'field' => 'status', 'message' => 'Invalid status: ' . $mapped['status']];
        }

        return $errors;
    }

    // -----------------------------------------------------------------------
    // confirm() — show validation summary
    // -----------------------------------------------------------------------

    public function confirm()
    {
        if (!session()->has('import_type')) {
            return redirect()->route('admin.import.index')
                ->with('error', 'Session expired. Please start the import process again.');
        }

        $importType  = session('import_type');
        $totalRows   = session('import_total_rows', 0);
        $validCount  = session('import_valid_count', 0);
        $errors      = session('import_errors', []);
        $errorCount  = count($errors);

        return view('admin.import.confirm', compact(
            'importType', 'totalRows', 'validCount', 'errorCount', 'errors'
        ));
    }

    // -----------------------------------------------------------------------
    // execute() — insert records, create ImportLog
    // -----------------------------------------------------------------------

    public function execute(Request $request)
    {
        if (!session()->has('import_filepath')) {
            return redirect()->route('admin.import.index')
                ->with('error', 'Session expired. Please start the import process again.');
        }

        $importType = session('import_type');
        $filepath   = session('import_filepath');
        $columnMap  = session('import_column_map', []);
        $filename   = session('import_filename', basename($filepath));

        $company  = Company::first();
        $branch   = Branch::where('company_id', $company->id)->first();
        $userId   = Auth::id();

        $handle = fopen($filepath, 'r');
        if (!$handle) {
            return redirect()->route('admin.import.index')
                ->with('error', 'Could not open file for import.');
        }

        // Skip BOM
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        $headers = fgetcsv($handle);
        $headers = array_map('trim', $headers);

        $imported     = 0;
        $skipped      = 0;
        $errorDetails = [];
        $rowNumber    = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;

            if (empty(array_filter($row))) {
                continue;
            }

            // Map columns
            $mapped = [];
            foreach ($columnMap as $csvCol => $systemField) {
                $colIndex = array_search($csvCol, $headers);
                if ($colIndex !== false && isset($row[$colIndex])) {
                    $mapped[$systemField] = trim($row[$colIndex]);
                }
            }

            // Validate again
            if ($importType === 'patients') {
                $rowErrors = $this->validatePatientRow($mapped, $rowNumber);
            } else {
                $rowErrors = $this->validateAppointmentRow($mapped, $rowNumber);
            }

            if (!empty($rowErrors)) {
                foreach ($rowErrors as $err) {
                    $errorDetails[] = $err;
                }
                $skipped++;
                continue;
            }

            // Import the row
            try {
                if ($importType === 'patients') {
                    $result = $this->importPatientRow($mapped, $company, $branch);
                } else {
                    $result = $this->importAppointmentRow($mapped, $company, $branch);
                }

                if ($result === 'imported') {
                    $imported++;
                } elseif ($result === 'skipped') {
                    $skipped++;
                    $errorDetails[] = [
                        'row_number' => $rowNumber,
                        'field'      => 'email',
                        'message'    => 'Skipped: duplicate or patient not found',
                    ];
                }
            } catch (\Exception $e) {
                $skipped++;
                $errorDetails[] = [
                    'row_number' => $rowNumber,
                    'field'      => 'row',
                    'message'    => 'Error: ' . $e->getMessage(),
                ];
            }
        }

        fclose($handle);

        $totalRows = $rowNumber - 1;

        // Create import log
        $log = ImportLog::create([
            'company_id'   => $company->id,
            'user_id'      => $userId,
            'import_type'  => $importType,
            'filename'     => $filename,
            'status'       => 'completed',
            'total_rows'   => $totalRows,
            'imported'     => $imported,
            'skipped'      => $skipped,
            'errors'       => count($errorDetails),
            'error_details'=> $errorDetails,
            'column_map'   => $columnMap,
        ]);

        // Clear session
        session()->forget([
            'import_headers',
            'import_sample',
            'import_type',
            'import_filepath',
            'import_filename',
            'import_column_map',
            'import_total_rows',
            'import_valid_count',
            'import_errors',
        ]);

        return redirect()->route('admin.import.show', $log)
            ->with('success', "Import complete: {$imported} imported, {$skipped} skipped.");
    }

    private function importPatientRow(array $mapped, $company, $branch): string
    {
        // Check for duplicate email (including soft-deleted)
        if (!empty($mapped['email'])) {
            if (Patient::withTrashed()->where('email', $mapped['email'])->exists()) {
                return 'skipped';
            }
        }

        // Parse date_of_birth
        $dob = null;
        if (!empty($mapped['date_of_birth'])) {
            try {
                $dob = Carbon::parse($mapped['date_of_birth'])->toDateString();
            } catch (\Exception $e) {
                $dob = null;
            }
        }

        // Parse consent_given
        $consent = false;
        if (!empty($mapped['consent_given'])) {
            $v = strtolower(trim($mapped['consent_given']));
            $consent = in_array($v, ['1', 'yes', 'true', 'y']);
        }

        Patient::create([
            'company_id'              => $company->id,
            'branch_id'               => $branch?->id,
            'first_name'              => $mapped['first_name'] ?? '',
            'last_name'               => $mapped['last_name'] ?? '',
            'email'                   => $mapped['email'] ?? null,
            'phone'                   => $mapped['phone'] ?? null,
            'date_of_birth'           => $dob,
            'gender'                  => $mapped['gender'] ?? null,
            'nationality'             => $mapped['nationality'] ?? null,
            'address'                 => $mapped['address'] ?? null,
            'city'                    => $mapped['city'] ?? null,
            'status'                  => $mapped['status'] ?? 'active',
            'source'                  => $mapped['source'] ?? null,
            'internal_notes'          => $mapped['internal_notes'] ?? null,
            'emergency_contact_name'  => $mapped['emergency_contact_name'] ?? null,
            'emergency_contact_phone' => $mapped['emergency_contact_phone'] ?? null,
            'consent_given'           => $consent,
            'registration_date'       => now()->toDateString(),
        ]);

        return 'imported';
    }

    private function importAppointmentRow(array $mapped, $company, $branch): string
    {
        // Find patient
        $patient = null;

        if (!empty($mapped['patient_code'])) {
            $patient = Patient::where('company_id', $company->id)
                ->where('patient_code', $mapped['patient_code'])
                ->first();
        }

        if (!$patient && !empty($mapped['patient_email'])) {
            $patient = Patient::where('company_id', $company->id)
                ->where('email', $mapped['patient_email'])
                ->first();
        }

        if (!$patient && !empty($mapped['patient_phone'])) {
            $patient = Patient::where('company_id', $company->id)
                ->where('phone', $mapped['patient_phone'])
                ->first();
        }

        if (!$patient) {
            return 'skipped';
        }

        // Find service
        $service = null;
        if (!empty($mapped['service_name'])) {
            $service = Service::where('company_id', $company->id)
                ->whereRaw('LOWER(name) = ?', [strtolower($mapped['service_name'])])
                ->first();
        }

        // Parse scheduled_at
        $scheduledAt = null;
        try {
            $dateStr = $mapped['scheduled_date'];
            if (!empty($mapped['scheduled_time'])) {
                $dateStr .= ' ' . $mapped['scheduled_time'];
            }
            $scheduledAt = Carbon::parse($dateStr);
        } catch (\Exception $e) {
            return 'skipped';
        }

        // Parse status
        $status = 'scheduled';
        if (!empty($mapped['status']) && in_array(strtolower($mapped['status']), $this->validAppointmentStatuses())) {
            $status = strtolower($mapped['status']);
        }

        // Parse duration
        $duration = 30;
        if (!empty($mapped['duration_minutes']) && is_numeric($mapped['duration_minutes'])) {
            $duration = (int) $mapped['duration_minutes'];
        }

        Appointment::create([
            'company_id'       => $company->id,
            'branch_id'        => $branch?->id,
            'patient_id'       => $patient->id,
            'service_id'       => $service?->id,
            'scheduled_at'     => $scheduledAt,
            'duration_minutes' => $duration,
            'status'           => $status,
            'reason_notes'     => $mapped['reason_notes'] ?? null,
            'booked_by'        => Auth::id(),
        ]);

        return 'imported';
    }

    // -----------------------------------------------------------------------
    // show() — show import log details
    // -----------------------------------------------------------------------

    public function show(ImportLog $log)
    {
        return view('admin.import.show', compact('log'));
    }

    // -----------------------------------------------------------------------
    // downloadTemplate() — download CSV template
    // -----------------------------------------------------------------------

    public function downloadTemplate(string $type)
    {
        if (!in_array($type, ['patients', 'appointments'])) {
            abort(404);
        }

        if ($type === 'patients') {
            $headers = 'first_name,last_name,email,phone,date_of_birth,gender,nationality,address,city,status,source,internal_notes,emergency_contact_name,emergency_contact_phone,consent_given';
            $rows = [
                'John,Doe,john.doe@example.com,+962791234567,1985-03-15,male,Jordanian,"123 Main St",Amman,active,referral,,Jane Doe,+962799876543,yes',
                'Sara,Smith,sara.smith@example.com,+962792345678,1992-07-22,female,,"456 Oak Ave",Zarqa,active,walk-in,VIP patient,,,no',
            ];
        } else {
            $headers = 'patient_code,patient_email,patient_phone,scheduled_date,scheduled_time,service_name,status,duration_minutes,reason_notes';
            $rows = [
                'MF-00001,john.doe@example.com,+962791234567,2024-06-15,10:00,Consultation,scheduled,30,Follow-up after treatment',
                'MF-00002,sara.smith@example.com,+962792345678,2024-06-16,14:30,Physical Therapy,scheduled,60,Initial session',
            ];
        }

        $content = $headers . "\n" . implode("\n", $rows) . "\n";

        return response($content, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$type}_template.csv\"",
        ]);
    }
}
