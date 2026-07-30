<?php

namespace Modules\Employee\Resources;

use App\Http\Resources\SuccessResource;
use App\Support\Traits\CamelCaseResource;
use Illuminate\Http\Request;
use Modules\AccountLedger\Resources\AccountLedgerResource;
use Modules\Address\Resources\AddressResource;
use Modules\Department\Resources\DepartmentResource;
use Modules\Designation\Resources\DesignationResource;
use Modules\EmployeeGroup\Resources\EmployeeGroupResource;
use Modules\Grade\Resources\GradeResource;
use Modules\Shift\Resources\ShiftResource;
use Modules\User\Resources\UserResource;

class EmployeeResource extends SuccessResource
{
    use CamelCaseResource;

    public function toArray(Request $request): array
    {

        return array_merge($this->toCamelCaseArray($request), [

            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'dob' => $this->dob,
            'doj' => $this->doj,
            'email' => $this->email,
            'contactNo' => $this->contact_no,
            'education' => $this->education,
            'pan' => $this->pan,
            'image' => $this->image,
            'status' => $this->status,
            'departmentId' => $this->department_id,
            'designationId' => $this->designation_id,
            'employeeGroupId' => $this->employee_group_id,
            'shiftId' => $this->shift_id,
            'gradeId' => $this->grade_id,
            'department' => DepartmentResource::make($this->whenLoaded('department')),
            'designation' => DesignationResource::make($this->whenLoaded('designation')),
            'employeeGroup' => EmployeeGroupResource::make($this->whenLoaded('employee_group')),
            'shift' => ShiftResource::make($this->whenLoaded('shift')),
            'grade' => GradeResource::make($this->whenLoaded('grade')),
            'address' => $this->whenLoaded('address', fn () => $this->address ? AddressResource::make($this->address) : null),
            'user' => UserResource::make($this->whenLoaded('user')),
            'accountLedger' => AccountLedgerResource::make($this->whenLoaded('account_ledger')),

        ]);

    }
}
