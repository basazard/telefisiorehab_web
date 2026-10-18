<?php

namespace App\Support\Onboarding;

/** Region ids used by <x-body-map>; stored in the database. Labels are display-only (Indonesian). */
final class BodyRegions
{
    public const IDS = [
        'Abdomen',
        'Back of Head',
        'Cervical Spine',
        'Chest',
        'Glutes',
        'Head',
        'Left Ankle',
        'Left Calf',
        'Left Elbow',
        'Left Forearm',
        'Left Hamstring',
        'Left Heel',
        'Left Hip',
        'Left Knee',
        'Left Scapula',
        'Left Shin',
        'Left Shoulder',
        'Left Thigh',
        'Left Wrist',
        'Lower Back',
        'Neck',
        'Right Ankle',
        'Right Calf',
        'Right Elbow',
        'Right Forearm',
        'Right Hamstring',
        'Right Heel',
        'Right Hip',
        'Right Knee',
        'Right Scapula',
        'Right Shin',
        'Right Shoulder',
        'Right Thigh',
        'Right Wrist',
        'Upper Back',
    ];

    public const LABELS = [
        'Abdomen' => 'Perut',
        'Back of Head' => 'Belakang kepala',
        'Cervical Spine' => 'Tulang leher',
        'Chest' => 'Dada',
        'Glutes' => 'Bokong',
        'Head' => 'Kepala',
        'Left Ankle' => 'Pergelangan kaki / kaki kiri',
        'Left Calf' => 'Betis kiri',
        'Left Elbow' => 'Siku kiri',
        'Left Forearm' => 'Lengan bawah kiri',
        'Left Hamstring' => 'Paha belakang kiri',
        'Left Heel' => 'Tumit kiri',
        'Left Hip' => 'Pinggul kiri',
        'Left Knee' => 'Lutut kiri',
        'Left Scapula' => 'Tulang belikat kiri',
        'Left Shin' => 'Tulang kering kiri',
        'Left Shoulder' => 'Bahu kiri',
        'Left Thigh' => 'Paha depan kiri',
        'Left Wrist' => 'Pergelangan tangan / tangan kiri',
        'Lower Back' => 'Punggung bawah',
        'Neck' => 'Leher',
        'Right Ankle' => 'Pergelangan kaki / kaki kanan',
        'Right Calf' => 'Betis kanan',
        'Right Elbow' => 'Siku kanan',
        'Right Forearm' => 'Lengan bawah kanan',
        'Right Hamstring' => 'Paha belakang kanan',
        'Right Heel' => 'Tumit kanan',
        'Right Hip' => 'Pinggul kanan',
        'Right Knee' => 'Lutut kanan',
        'Right Scapula' => 'Tulang belikat kanan',
        'Right Shin' => 'Tulang kering kanan',
        'Right Shoulder' => 'Bahu kanan',
        'Right Thigh' => 'Paha depan kanan',
        'Right Wrist' => 'Pergelangan tangan / tangan kanan',
        'Upper Back' => 'Punggung atas',
    ];

    public static function label(string $id): string
    {
        return self::LABELS[$id] ?? $id;
    }

    /** The PRD's body areas (for routing and reports); the detailed region ids roll up into these. */
    public const AREA_LABELS = [
        'head_neck' => 'Kepala / Leher',
        'shoulder' => 'Bahu',
        'elbow' => 'Siku',
        'wrist' => 'Pergelangan tangan',
        'upper_back' => 'Punggung',
        'lower_back' => 'Pinggang',
        'pelvis' => 'Panggul',
        'knee' => 'Lutut',
        'ankle' => 'Pergelangan kaki',
        'other' => 'Area lain (dada, perut, paha, betis)',
    ];

    public const AREA_OF = [
        'Abdomen' => 'other',
        'Back of Head' => 'head_neck',
        'Cervical Spine' => 'head_neck',
        'Chest' => 'other',
        'Glutes' => 'pelvis',
        'Head' => 'head_neck',
        'Left Ankle' => 'ankle',
        'Left Calf' => 'other',
        'Left Elbow' => 'elbow',
        'Left Forearm' => 'elbow',
        'Left Hamstring' => 'other',
        'Left Heel' => 'ankle',
        'Left Hip' => 'pelvis',
        'Left Knee' => 'knee',
        'Left Scapula' => 'shoulder',
        'Left Shin' => 'other',
        'Left Shoulder' => 'shoulder',
        'Left Thigh' => 'other',
        'Left Wrist' => 'wrist',
        'Lower Back' => 'lower_back',
        'Neck' => 'head_neck',
        'Right Ankle' => 'ankle',
        'Right Calf' => 'other',
        'Right Elbow' => 'elbow',
        'Right Forearm' => 'elbow',
        'Right Hamstring' => 'other',
        'Right Heel' => 'ankle',
        'Right Hip' => 'pelvis',
        'Right Knee' => 'knee',
        'Right Scapula' => 'shoulder',
        'Right Shin' => 'other',
        'Right Shoulder' => 'shoulder',
        'Right Thigh' => 'other',
        'Right Wrist' => 'wrist',
        'Upper Back' => 'upper_back',
    ];

    public static function area(string $id): string
    {
        return self::AREA_OF[$id] ?? 'other';
    }

    public static function areaLabel(string $id): string
    {
        return self::AREA_LABELS[self::area($id)];
    }
}
