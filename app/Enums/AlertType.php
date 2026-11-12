<?php

namespace App\Enums;

/**
 * Kinds of in-system alerts (App\Services\Alerts\AlertService).
 */
enum AlertType: string
{
    case IqamaExpiry = 'iqama_expiry';
    case PassportExpiry = 'passport_expiry';
    case MedicalExpiry = 'medical_expiry';
    case ContractEnd = 'contract_end';
    case ProbationEnd = 'probation_end';
    case OverdueStage = 'overdue_stage';
    case HoldExpired = 'hold_expired';
    case WorkerAbsent = 'worker_absent';
    case TripUnassigned = 'trip_unassigned';
    case LicenseExpiry = 'license_expiry';
    case CaseWaiting = 'case_waiting';

    public function label(): string
    {
        return __("alerts.types.{$this->value}");
    }

    public function icon(): string
    {
        return match ($this) {
            self::IqamaExpiry, self::PassportExpiry, self::LicenseExpiry => 'ri-passport-line',
            self::MedicalExpiry => 'ri-heart-pulse-line',
            self::ContractEnd => 'ri-file-paper-2-line',
            self::ProbationEnd => 'ri-timer-line',
            self::OverdueStage => 'ri-flight-takeoff-line',
            self::HoldExpired => 'ri-lock-unlock-line',
            self::WorkerAbsent => 'ri-user-unfollow-line',
            self::TripUnassigned => 'ri-steering-2-line',
            self::CaseWaiting => 'ri-scales-3-line',
        };
    }

    /**
     * Permission needed to see the subject (the alert is hidden otherwise).
     */
    public function permission(): string
    {
        return match ($this) {
            self::IqamaExpiry, self::PassportExpiry, self::MedicalExpiry => 'workers.view',
            self::ContractEnd => 'rental_contracts.view',
            self::ProbationEnd, self::OverdueStage, self::HoldExpired => 'recruitment.view',
            self::WorkerAbsent => 'camps.view',
            self::TripUnassigned, self::LicenseExpiry => 'trips.view',
            self::CaseWaiting => 'cases.view',
        };
    }
}
