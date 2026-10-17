<?php

namespace App\Support\Onboarding;

/**
 * Static questionnaire content ported from onboarding.ts (copied programmatically, order preserved).
 * Keys such as 'lefs-3' / 'odi-0' / 'rom' used for answers match the legacy app.
 */
final class Questionnaires
{
    public const CONDITIONS = [
        'Diabetes',
        'Pregnancy',
        'Post-surgery',
        'Cardiac',
        'Neurological',
        'None',
    ];

    public const GOALS = [
        'Reduce pain',
        'Improve mobility',
        'Return to sport',
        'Prevent falls',
        'Post-op recovery',
        'Build strength',
        'Better posture',
    ];

    public const OCCUPATIONS = [
        'Office / desk work',
        'Manual labour',
        'Healthcare',
        'Teacher',
        'Driver',
        'Student',
        'Retired',
        'Athlete',
        'Other',
    ];

    public const ACTIVITY_LEVELS = [
        ['id' => 'sedentary', 'label' => 'Sedentary'],
        ['id' => 'light', 'label' => 'Lightly active'],
        ['id' => 'moderate', 'label' => 'Moderately active'],
        ['id' => 'active', 'label' => 'Active'],
        ['id' => 'very-active', 'label' => 'Very active'],
    ];

    public const PAIN_CHARACTERS = [
        'Sharp',
        'Dull',
        'Burning',
        'Throbbing',
        'Stiffness',
        'Numbness',
        'Tingling',
    ];

    public const AGGRAVATORS = [
        'Sitting',
        'Standing',
        'Walking',
        'Lifting',
        'Sleeping',
        'Exercise',
    ];

    public const RED_FLAG_QUESTIONS = [
        'Chest pain or tightness?',
        'Severe difficulty breathing?',
        'Fainting or loss of consciousness?',
        'Sudden weakness in arms or legs?',
        'Sudden difficulty speaking or slurred speech?',
        'Recent major trauma or accident?',
        'Uncontrolled bleeding?',
        'High fever (above 38.5°C / 101.3°F)?',
        'Sudden severe headache (“worst of your life”)?',
        'Loss of bladder or bowel control?',
        'Numbness in the groin or saddle area?',
        'Unexplained rapid weight loss?',
        'Night pain that prevents sleep and doesn\'t improve?',
        'Suspected bone fracture (visible deformity, unable to bear weight)?',
    ];

    public const LEFS_ITEMS = [
        'Any of your usual work, housework or school activities',
        'Your usual hobbies or recreational activities',
        'Getting into or out of the bath',
        'Walking between rooms',
        'Putting on your shoes or socks',
        'Squatting',
        'Lifting an object from the floor',
        'Performing light activities around your home',
        'Performing heavy activities around your home',
        'Getting into or out of a car',
        'Walking 2 blocks',
        'Walking a mile',
        'Going up or down 10 stairs',
        'Standing for 1 hour',
        'Sitting for 1 hour',
        'Running on even ground',
        'Running on uneven ground',
        'Making sharp turns while running',
        'Hopping',
        'Rolling over in bed',
    ];

    public const LEFS_CHOICES = [
        ['v' => 0, 'label' => 'Extreme difficulty / unable'],
        ['v' => 1, 'label' => 'Quite a bit of difficulty'],
        ['v' => 2, 'label' => 'Moderate difficulty'],
        ['v' => 3, 'label' => 'A little bit of difficulty'],
        ['v' => 4, 'label' => 'No difficulty'],
    ];

    public const ODI_ITEMS = [
        'Pain intensity',
        'Personal care (washing, dressing)',
        'Lifting',
        'Walking',
        'Sitting',
        'Standing',
        'Sleeping',
        'Sex life',
        'Social life',
        'Travelling',
    ];

    public const ODI_CHOICES = [
        ['v' => 0, 'label' => 'No limitation at all'],
        ['v' => 1, 'label' => 'Very slight limitation'],
        ['v' => 2, 'label' => 'Moderate limitation'],
        ['v' => 3, 'label' => 'Considerable limitation'],
        ['v' => 4, 'label' => 'Severe limitation'],
        ['v' => 5, 'label' => 'Completely prevented'],
    ];

    public const TIERS = [
        'low' => [
            'emoji' => '🟢',
            'label' => 'LOW RISK',
            'meaning' => 'You have minimal functional limitation. A self-guided home program with light monitoring is appropriate.',
            'program' => [
                'Personalized exercise videos',
                'Automatic rep & duration tracking',
                'Monthly progress check-ins',
            ],
        ],
        'moderate' => [
            'emoji' => '🟡',
            'label' => 'MODERATE RISK',
            'meaning' => 'You have moderate functional limitation. We recommend a supervised exercise program with regular check-ins.',
            'program' => [
                'Personalized exercise videos',
                'Weekly progress check-ins',
                'Chat with a physiotherapist',
                'Video consultation available',
            ],
        ],
        'high' => [
            'emoji' => '🔴',
            'label' => 'HIGH RISK',
            'meaning' => 'You have significant functional limitation. Your program starts gently and is closely supervised by a physiotherapist.',
            'program' => [
                'Low-intensity guided exercise videos',
                'Twice-weekly physiotherapist review',
                'Priority chat support',
                'Scheduled video consultations',
            ],
        ],
    ];

    public const TRACK_LABELS = [
        'pediatric' => ['emoji' => '🧒', 'text' => 'Pediatric track activated'],
        'adult' => ['emoji' => '🧑', 'text' => 'Adult track activated'],
        'geriatric' => ['emoji' => '🧓', 'text' => 'Geriatric track activated (fall risk screening included)'],
    ];
}
