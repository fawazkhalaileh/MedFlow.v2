<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ClinicalFlagSeeder extends Seeder
{
    public function run(): void
    {
        $company = \App\Models\Company::first();

        $defaults = [
            // [name, category, color, requires_detail, detail_placeholder, icon, sort_order]
            ['Overweight / Obese',        'lifestyle', '#7c3aed', false, null,                    '⚖️',  1],
            ['Diabetic',                  'medical',   '#d97706', false, null,                    '🩸',  2],
            ['Hypertension',              'medical',   '#d97706', false, null,                    '💓',  3],
            ['Pregnant',                  'medical',   '#059669', false, null,                    '🤰',  4],
            ['Pacemaker / Metal Implant', 'alert',     '#dc2626', false, null,                    '⚡',  5],
            ['Allergic to',               'allergy',   '#dc2626', true,  'Specify allergen...',   '⚠️',  6],
            ['Skin Condition',            'medical',   '#d97706', true,  'Describe condition...', '🩹',  7],
            ['Recent Surgery',            'medical',   '#d97706', true,  'Type and date...',      '🔪',  8],
            ['Blood Disorder',            'medical',   '#dc2626', false, null,                    '🩸',  9],
            ['Claustrophobic',            'lifestyle', '#7c3aed', false, null,                    '😰', 10],
            ['On Blood Thinners',         'alert',     '#dc2626', true,  'Medication name...',    '💊', 11],
            ['Epilepsy',                  'alert',     '#dc2626', false, null,                    '⚡', 12],
        ];

        foreach ($defaults as [$name, $cat, $color, $req, $placeholder, $icon, $sort]) {
            \App\Models\ClinicalFlag::firstOrCreate(
                ['name' => $name, 'company_id' => $company?->id],
                [
                    'category'           => $cat,
                    'color'              => $color,
                    'requires_detail'    => $req,
                    'detail_placeholder' => $placeholder,
                    'icon'               => $icon,
                    'sort_order'         => $sort,
                    'is_active'          => true,
                ]
            );
        }
    }
}
