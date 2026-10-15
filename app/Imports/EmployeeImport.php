<?php

namespace App\Imports;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\HrmAddon\App\Models\Employee;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Modules\HrmAddon\App\Models\Department;
use Maatwebsite\Excel\Concerns\ToCollection;
use Modules\HrmAddon\App\Models\Designation;

class EmployeeImport implements ToCollection
{
    protected $businessId;

    public function __construct($businessId)
    {
        $this->businessId = $businessId;
    }

    public function collection(Collection $rows)
    {
        DB::transaction(function () use ($rows) {

            $employees = [];

            foreach ($rows as $index => $row) {
                if ($index === 0) continue; // Skip header row

                // Read Excel columns
                $name            = trim($row[0]);
                $gender          = trim($row[1]);
                $phone           = trim($row[2]);
                $designationName = trim($row[3] ?? null);
                $departmentName  = trim($row[4] ?? null);
                $email           = trim($row[5] ?? null);
                $birthDate       = trim($row[6] ?? null);
                $joinDate        = trim($row[7] ?? null);
                $salary          = trim($row[8] ?? 0);

                if (!$name || !$phone || !$gender) continue;

                $birthDateFormatted = null;
                $joinDateFormatted  = null;

                if (!empty($birthDate)) {
                    $birthDateFormatted = is_numeric($birthDate)
                        ? Date::excelToDateTimeObject($birthDate)->format('Y-m-d')
                        : Carbon::createFromFormat('d/m/Y', $birthDate)->format('Y-m-d');
                }

                if (!empty($joinDate)) {
                    $joinDateFormatted = is_numeric($joinDate)
                        ? Date::excelToDateTimeObject($joinDate)->format('Y-m-d')
                        : Carbon::createFromFormat('d/m/Y', $joinDate)->format('Y-m-d');
                }

                $designationId = Designation::firstOrCreate(
                    ['name' => $designationName, 'business_id' => $this->businessId],
                    ['name' => $designationName]
                )->id;

                $departmentId = Department::firstOrCreate(
                    ['name' => $departmentName, 'business_id' => $this->businessId],
                    ['name' => $departmentName]
                )->id;

                $employees[] = [
                    'name' => $name,
                    'business_id' => $this->businessId,
                    'branch_id' => auth()->user()->branch_id ?? auth()->user()->active_branch_id,
                    'designation_id' => $designationId,
                    'department_id' => $departmentId,
                    'phone' => $phone,
                    'amount' => $salary,
                    'email' => $email,
                    'gender' => $gender,
                    'birth_date' => $birthDateFormatted,
                    'join_date' => $joinDateFormatted,
                ];

            }

            if (!empty($employees)) {
                Employee::insert($employees);
            }
        });
    }
}
