<?php

namespace App\Services;

/**
 * RiskCalculator
 *
 * Hitung disease risk scores berdasarkan health parameters.
 * Menggunakan evidence-based medical guidelines.
 */
class RiskCalculator
{
    /**
     * Calculate diabetes risk berdasarkan glucose dan BMI.
     * 
     * Diabetes indicators:
     * - Fasting glucose >126 mg/dL: strong indicator
     * - Glucose 100-125 mg/dL: prediabetic
     * - BMI > 30: obesity
     * - Age > 45: increases risk
     */
    public static function diabetesRisk(float $glucose, float $bmi, int $age): float
    {
        $risk = 0;

        // Glucose contribution (0-0.6)
        if ($glucose >= 126) {
            $risk += 0.6; // Diabetic range
        } elseif ($glucose >= 100) {
            $risk += 0.4; // Prediabetic
        } elseif ($glucose >= 85) {
            $risk += 0.2;
        }

        // BMI contribution (0-0.25)
        if ($bmi >= 35) {
            $risk += 0.25; // Obese
        } elseif ($bmi >= 30) {
            $risk += 0.2; // Overweight
        } elseif ($bmi >= 25) {
            $risk += 0.1;
        }

        // Age contribution (0-0.15)
        if ($age > 65) {
            $risk += 0.15;
        } elseif ($age > 45) {
            $risk += 0.1;
        }

        return min($risk, 1.0);
    }

    /**
     * Calculate hypertension risk berdasarkan blood pressure, BMI, age.
     *
     * BP stages:
     * - Normal: <120/<80
     * - Elevated: 120-129/<80
     * - Stage 1 HTN: 130-139/80-89
     * - Stage 2 HTN: >=140/>=90
     */
    public static function hypertensionRisk(int $bp, float $bmi, int $age): float
    {
        $risk = 0;

        // Blood pressure contribution (0-0.6)
        if ($bp >= 160) {
            $risk += 0.6; // Stage 2+ hypertension
        } elseif ($bp >= 140) {
            $risk += 0.5; // Stage 2
        } elseif ($bp >= 130) {
            $risk += 0.35; // Stage 1
        } elseif ($bp >= 120) {
            $risk += 0.15; // Elevated
        }

        // BMI contribution (0-0.2)
        if ($bmi >= 35) {
            $risk += 0.2;
        } elseif ($bmi >= 30) {
            $risk += 0.15;
        } elseif ($bmi >= 25) {
            $risk += 0.08;
        }

        // Age contribution (0-0.2)
        if ($age > 65) {
            $risk += 0.2;
        } elseif ($age > 50) {
            $risk += 0.12;
        } elseif ($age > 40) {
            $risk += 0.06;
        }

        return min($risk, 1.0);
    }

    /**
     * Calculate heart disease risk berdasarkan BP, glucose, BMI, age.
     *
     * Cardiovascular risk factors:
     * - High BP: Major risk factor
     * - High glucose: Increases risk
     * - Obesity: Increases workload
     * - Age: Cumulative effect
     */
    public static function heartDiseaseRisk(int $bp, float $glucose, float $bmi, int $age, int $gender): float
    {
        $risk = 0;

        // BP contribution (0-0.4)
        if ($bp >= 160) {
            $risk += 0.4;
        } elseif ($bp >= 140) {
            $risk += 0.3;
        } elseif ($bp >= 130) {
            $risk += 0.2;
        } elseif ($bp >= 120) {
            $risk += 0.1;
        }

        // Glucose contribution (0-0.3)
        if ($glucose >= 140) {
            $risk += 0.3;
        } elseif ($glucose >= 110) {
            $risk += 0.2;
        } elseif ($glucose >= 85) {
            $risk += 0.1;
        }

        // BMI contribution (0-0.15)
        if ($bmi >= 35) {
            $risk += 0.15;
        } elseif ($bmi >= 30) {
            $risk += 0.1;
        } elseif ($bmi >= 25) {
            $risk += 0.05;
        }

        // Age contribution (0-0.15) - men have higher risk at younger ages
        if ($age > 60) {
            $risk += 0.15;
        } elseif ($age > 50) {
            $risk += $gender == 1 ? 0.1 : 0.08; // Men > Women
        } elseif ($age > 40) {
            $risk += $gender == 1 ? 0.05 : 0.02;
        }

        return min($risk, 1.0);
    }

    /**
     * Calculate stroke risk berdasarkan BP, glucose, age, BMI.
     *
     * Stroke risk factors:
     * - High BP: Primary risk
     * - Diabetes/high glucose: Major contributor
     * - Age
     * - Obesity
     */
    public static function strokeRisk(int $bp, float $glucose, int $age, float $bmi): float
    {
        $risk = 0;

        // BP contribution (0-0.5)
        if ($bp >= 160) {
            $risk += 0.5;
        } elseif ($bp >= 140) {
            $risk += 0.4;
        } elseif ($bp >= 130) {
            $risk += 0.25;
        } elseif ($bp >= 120) {
            $risk += 0.1;
        }

        // Glucose contribution (0-0.25)
        if ($glucose >= 140) {
            $risk += 0.25;
        } elseif ($glucose >= 110) {
            $risk += 0.15;
        } elseif ($glucose >= 85) {
            $risk += 0.08;
        }

        // Age contribution (0-0.15)
        if ($age > 65) {
            $risk += 0.15;
        } elseif ($age > 55) {
            $risk += 0.1;
        } elseif ($age > 45) {
            $risk += 0.05;
        }

        // BMI contribution (0-0.1)
        if ($bmi >= 35) {
            $risk += 0.1;
        } elseif ($bmi >= 30) {
            $risk += 0.06;
        }

        return min($risk, 1.0);
    }

    /**
     * Calculate Chronic Kidney Disease (CKD) risk berdasarkan BP, glucose, age, gender.
     *
     * CKD risk factors:
     * - High BP: Damages glomeruli
     * - Diabetes: Major cause
     * - Age
     * - Gender (male > female typically)
     */
    public static function ckdRisk(int $bp, float $glucose, int $age, int $gender): float
    {
        $risk = 0;

        // BP contribution (0-0.35)
        if ($bp >= 160) {
            $risk += 0.35;
        } elseif ($bp >= 140) {
            $risk += 0.25;
        } elseif ($bp >= 130) {
            $risk += 0.15;
        } elseif ($bp >= 120) {
            $risk += 0.08;
        }

        // Glucose contribution (0-0.35)
        if ($glucose >= 140) {
            $risk += 0.35;
        } elseif ($glucose >= 110) {
            $risk += 0.25;
        } elseif ($glucose >= 85) {
            $risk += 0.1;
        }

        // Age contribution (0-0.2)
        if ($age > 65) {
            $risk += 0.2;
        } elseif ($age > 55) {
            $risk += 0.12;
        } elseif ($age > 45) {
            $risk += 0.06;
        }

        // Gender contribution (0-0.1) - men slightly higher risk
        if ($gender == 1 && $age > 50) {
            $risk += 0.05;
        } elseif ($gender == 0 && $age > 55) {
            $risk += 0.02;
        }

        return min($risk, 1.0);
    }

    /**
     * Calculate all disease risks sekaligus.
     * Returns array dengan semua risk scores.
     */
    public static function calculateAll(
        int $age,
        int $gender,
        float $glucose,
        int $bp,
        float $bmi
    ): array {
        return [
            'diabetes_risk' => self::diabetesRisk($glucose, $bmi, $age),
            'hypertension_risk' => self::hypertensionRisk($bp, $bmi, $age),
            'heart_disease_risk' => self::heartDiseaseRisk($bp, $glucose, $bmi, $age, $gender),
            'stroke_risk' => self::strokeRisk($bp, $glucose, $age, $bmi),
            'ckd_risk' => self::ckdRisk($bp, $glucose, $age, $gender),
        ];
    }
}
