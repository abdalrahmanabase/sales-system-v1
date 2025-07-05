# Employee Management System Documentation

## Overview

This comprehensive employee management system provides a complete solution for managing employees with attendance tracking, loan management, salary processing, and leave management according to global HR standards.

## Features

### 1. Employee Management
- **Complete Employee Profiles**: Full personal, contact, and employment information
- **Global Standards Compliance**: Including national IDs, tax information, and banking details
- **Employment Status Tracking**: Active, inactive, terminated, resigned statuses
- **Multi-branch Support**: Employees can be assigned to different branches
- **Department Management**: Organize employees by departments
- **Employee Types**: Support for full-time, part-time, contract, and intern employees

### 2. Attendance Management
- **Real-time Check-in/Check-out**: Clock in/out with timestamp tracking
- **Scheduled Hours**: Compare actual vs scheduled working hours
- **Overtime Calculation**: Automatic overtime hours calculation
- **Break Time Tracking**: Record and track break periods
- **Late/Early Departure Detection**: Automatic status calculation
- **Location Tracking**: Record attendance location
- **Holiday Management**: Mark holidays and handle accordingly
- **Leave Integration**: Integrated with leave management system

### 3. Loan Management
- **Multiple Loan Types**: Personal, advance, emergency, housing, education, medical
- **Interest Calculation**: Support for interest-bearing loans
- **Payment Scheduling**: Automatic payment schedule generation
- **Loan Approval Workflow**: Pending → Approved → Active → Completed
- **Guarantor Support**: Record guarantor information
- **Payment Tracking**: Individual payment tracking and history
- **Overdue Management**: Automatic overdue detection and notifications
- **Loan Eligibility**: Automatic eligibility checking based on salary

### 4. Salary Management
- **Comprehensive Payroll**: Basic salary, overtime, allowances, bonuses, commissions
- **Tax Calculations**: Automatic tax deductions based on employee tax rate
- **Deduction Management**: Health insurance, pension, loan deductions
- **Payslip Generation**: Complete payslip with all details
- **Payment Tracking**: Track partial and full payments
- **Multi-payment Methods**: Cash, bank transfer, check, mobile money
- **Salary History**: Complete salary payment history

### 5. Leave Management
- **Multiple Leave Types**: Annual, sick, maternity, paternity, emergency, unpaid, study, compassionate
- **Leave Balance Tracking**: Automatic leave balance calculation
- **Approval Workflow**: Request → Approval/Rejection → Processing
- **Leave Conflict Detection**: Prevent overlapping leave requests
- **Business Days Calculation**: Automatic calculation of working days
- **Attachment Support**: Support for medical certificates and documents

## Database Structure

### Enhanced Employee Table
```sql
- Personal Information: name, national_id, date_of_birth, gender, nationality
- Contact Information: phone, email, address, emergency contacts
- Employment Details: employee_type, department, job_title, hire_date
- Financial Information: salary, hourly_rate, tax_rate, bank_account
- Leave Management: annual_leave_days, sick_leave_days, used_leave
```

### Loan Management Tables
```sql
- employee_loans: Loan records with amount, interest, terms
- employee_loan_payments: Individual payment tracking
```

### Salary Management Tables
```sql
- employee_salaries: Payroll records with earnings and deductions
```

### Attendance Management Tables
```sql
- employee_attendance: Enhanced attendance with hours tracking
- employee_leave_requests: Leave request management
```

## Model Relationships

### Employee Model
```php
// Relationships
hasMany(EmployeeAttendance::class)
hasMany(EmployeeLoan::class)
hasMany(EmployeeSalary::class)
hasMany(EmployeeLeaveRequest::class)
hasMany(EmployeeLoanPayment::class)
belongsTo(Branch::class)
belongsTo(User::class)

// Key Methods
calculateMonthlyGrossPayForHourlyEmployee()
calculateTaxDeduction()
isEligibleForLoan()
hasOutstandingLoans()
getMonthlyAttendanceData()
calculateOvertimeHours()
```

### EmployeeLoan Model
```php
// Key Methods
calculateMonthlyPayment()
createPaymentSchedule()
updateRemainingBalance()
approve()
activate()
cancel()
```

### EmployeeSalary Model
```php
// Key Methods
calculateGrossSalary()
calculateTotalDeductions()
calculateNetSalary()
generatePayslip()
markAsPaid()
processPartialPayment()
```

### EmployeeAttendance Model
```php
// Key Methods
checkIn()
checkOut()
calculateHours()
calculateStatus()
addBreak()
getProductivityRate()
getAttendanceScore()
```

### EmployeeLeaveRequest Model
```php
// Key Methods
approve()
reject()
cancel()
checkLeaveBalance()
calculateBusinessDays()
hasConflictingLeave()
```

## Usage Examples

### Creating an Employee
```php
$employee = Employee::create([
    'employee_id' => 'EMP001',
    'first_name' => 'John',
    'last_name' => 'Doe',
    'email' => 'john.doe@company.com',
    'national_id' => '1234567890',
    'job_title' => 'Software Developer',
    'department' => 'IT',
    'employee_type' => 'full_time',
    'salary' => 5000.00,
    'hourly_rate' => 25.00,
    'hire_date' => '2024-01-01',
    'branch_id' => 1,
    'tax_rate' => 15.00,
    'annual_leave_days' => 21,
    'sick_leave_days' => 10,
]);
```

### Recording Attendance
```php
$attendance = EmployeeAttendance::create([
    'employee_id' => $employee->id,
    'attendance_date' => '2024-01-15',
    'scheduled_in_time' => '09:00:00',
    'scheduled_out_time' => '17:00:00',
]);

// Check in
$attendance->checkIn(Carbon::now(), 'Main Office');

// Check out
$attendance->checkOut(Carbon::now());
```

### Creating a Loan
```php
$loan = EmployeeLoan::create([
    'employee_id' => $employee->id,
    'loan_type' => 'personal',
    'loan_amount' => 10000.00,
    'interest_rate' => 12.00,
    'installments_count' => 12,
    'start_date' => '2024-02-01',
    'end_date' => '2025-01-31',
    'purpose' => 'Home renovation',
]);

// Calculate monthly payment
$monthlyPayment = $loan->calculateMonthlyPayment();
$loan->monthly_payment = $monthlyPayment;
$loan->save();

// Approve loan
$loan->approve(auth()->user(), 'Approved based on salary and credit history');
```

### Processing Salary
```php
$salary = EmployeeSalary::create([
    'employee_id' => $employee->id,
    'pay_period_start' => '2024-01-01',
    'pay_period_end' => '2024-01-31',
    'payment_date' => '2024-02-01',
    'basic_salary' => $employee->salary,
    'overtime_hours' => 10,
    'overtime_rate' => 37.50,
    'allowances' => 200.00,
    'bonuses' => 500.00,
]);

// Mark as paid
$salary->markAsPaid(auth()->user());

// Generate payslip
$payslip = $salary->generatePayslip();
```

### Leave Request
```php
$leaveRequest = EmployeeLeaveRequest::create([
    'employee_id' => $employee->id,
    'leave_type' => 'annual',
    'start_date' => '2024-03-01',
    'end_date' => '2024-03-05',
    'reason' => 'Family vacation',
]);

// Approve leave
$leaveRequest->approve(auth()->user(), 'Approved - adequate coverage arranged');
```

## Business Logic Features

### Loan Eligibility
- Checks if total monthly loan deductions don't exceed 50% of salary
- Considers existing active loans
- Validates employment status

### Attendance Scoring
- Base score of 100 points
- Deductions for lateness (up to 30 points)
- Deductions for early departure (up to 20 points)
- Zero score for absence

### Leave Balance Management
- Automatic balance checking before approval
- Balance deduction upon approval
- Balance refund upon cancellation
- Separate tracking for different leave types

### Salary Calculations
- Automatic tax calculations based on employee tax rate
- Loan deductions from active loans
- Overtime calculations based on scheduled hours
- Comprehensive deduction breakdown

## Global Standards Compliance

### Data Fields
- National ID / Social Security Number
- Tax ID and tax rate management
- Banking information for payments
- Emergency contact information
- Comprehensive address information

### Employment Law Compliance
- Proper employment status tracking
- Termination date and reason recording
- Probation period management
- Contract end date tracking

### Financial Compliance
- Detailed payroll record keeping
- Tax deduction tracking
- Loan documentation and approval trails
- Payment method tracking

## Security Features

### Audit Trails
- All salary payments tracked with processor information
- Loan approvals recorded with approver details
- Leave requests with approval/rejection history
- Attendance modifications logged

### Data Protection
- Sensitive financial information properly secured
- Personal data handling according to privacy standards
- Access control through Laravel's authorization system

## Integration Points

### User Management
- Integration with existing user authentication
- Role-based access control
- Approver/processor tracking

### Branch Management
- Multi-branch employee assignment
- Branch-specific reporting
- Centralized management with branch filtering

### Reporting Capabilities
- Monthly attendance reports
- Payroll summaries
- Loan status reports
- Leave balance reports
- Overtime analysis

## Installation & Setup

### Run Migrations
```bash
php artisan migrate
```

### Sample Data Creation
```php
// Create sample employee
$employee = Employee::factory()->create();

// Create sample attendance
EmployeeAttendance::factory()->count(30)->create(['employee_id' => $employee->id]);

// Create sample loan
EmployeeLoan::factory()->create(['employee_id' => $employee->id]);
```

## API Endpoints (Recommended)

### Employee Management
- `GET /api/employees` - List all employees
- `POST /api/employees` - Create new employee
- `GET /api/employees/{id}` - Get employee details
- `PUT /api/employees/{id}` - Update employee
- `DELETE /api/employees/{id}` - Delete employee

### Attendance Management
- `POST /api/attendance/check-in` - Check in employee
- `POST /api/attendance/check-out` - Check out employee
- `GET /api/attendance/employee/{id}` - Get employee attendance
- `GET /api/attendance/report` - Generate attendance report

### Loan Management
- `GET /api/loans` - List all loans
- `POST /api/loans` - Create new loan
- `POST /api/loans/{id}/approve` - Approve loan
- `POST /api/loans/{id}/payment` - Record payment

### Salary Management
- `GET /api/salaries` - List salary records
- `POST /api/salaries` - Create salary record
- `POST /api/salaries/{id}/pay` - Mark as paid
- `GET /api/salaries/{id}/payslip` - Generate payslip

### Leave Management
- `GET /api/leave-requests` - List leave requests
- `POST /api/leave-requests` - Create leave request
- `POST /api/leave-requests/{id}/approve` - Approve leave
- `POST /api/leave-requests/{id}/reject` - Reject leave

## Best Practices

### Performance Optimization
- Use database indexes on frequently queried fields
- Implement pagination for large datasets
- Cache frequently accessed data
- Use eager loading for relationships

### Data Integrity
- Use database transactions for multi-table operations
- Implement validation rules at model level
- Use foreign key constraints
- Regular data backups

### Security
- Validate all input data
- Use Laravel's built-in security features
- Implement proper authorization
- Regular security audits

This employee management system provides a comprehensive solution that meets global HR standards while maintaining flexibility for different business needs.