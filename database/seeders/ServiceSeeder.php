<?php

namespace Database\Seeders;

use App\Models\AppointmentReason;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        $company  = Company::first();
        $branches = Branch::where('company_id', $company->id)->get();

        // Service categories for a laser clinic
        $categories = [
            ['name' => 'Laser Hair Removal',   'color' => '#7C3AED'],
            ['name' => 'Skin Rejuvenation',     'color' => '#2563EB'],
            ['name' => 'Body Contouring',       'color' => '#059669'],
            ['name' => 'Facial Treatments',     'color' => '#DB2777'],
            ['name' => 'Tattoo Removal',        'color' => '#D97706'],
            ['name' => 'Consultations',         'color' => '#6B7280'],
        ];

        foreach ($categories as $cat) {
            ServiceCategory::create(array_merge($cat, ['company_id' => $company->id]));
        }

        $catMap = ServiceCategory::where('company_id', $company->id)->pluck('id', 'name');

        // Services per category
        $services = [
            // Laser Hair Removal
            ['category' => 'Laser Hair Removal', 'name' => 'Upper Lip',              'duration' => 15,  'price' => 150],
            ['category' => 'Laser Hair Removal', 'name' => 'Chin',                   'duration' => 15,  'price' => 150],
            ['category' => 'Laser Hair Removal', 'name' => 'Full Face',              'duration' => 30,  'price' => 450],
            ['category' => 'Laser Hair Removal', 'name' => 'Underarms',              'duration' => 20,  'price' => 200],
            ['category' => 'Laser Hair Removal', 'name' => 'Half Legs',              'duration' => 45,  'price' => 600],
            ['category' => 'Laser Hair Removal', 'name' => 'Full Legs',              'duration' => 75,  'price' => 900],
            ['category' => 'Laser Hair Removal', 'name' => 'Full Body',              'duration' => 120, 'price' => 2500],
            ['category' => 'Laser Hair Removal', 'name' => 'Brazilian',              'duration' => 30,  'price' => 500],
            ['category' => 'Laser Hair Removal', 'name' => 'Back - Full',            'duration' => 60,  'price' => 900],
            ['category' => 'Laser Hair Removal', 'name' => 'Arms - Full',            'duration' => 45,  'price' => 700],
            // Skin Rejuvenation
            ['category' => 'Skin Rejuvenation',  'name' => 'IPL Photofacial',        'duration' => 45,  'price' => 800],
            ['category' => 'Skin Rejuvenation',  'name' => 'Pigmentation Treatment', 'duration' => 45,  'price' => 700],
            ['category' => 'Skin Rejuvenation',  'name' => 'Vascular Lesions',       'duration' => 30,  'price' => 600],
            ['category' => 'Skin Rejuvenation',  'name' => 'Skin Tightening',        'duration' => 60,  'price' => 1200],
            // Body Contouring
            ['category' => 'Body Contouring',    'name' => 'Cryolipolysis - Abdomen','duration' => 60,  'price' => 1500],
            ['category' => 'Body Contouring',    'name' => 'RF Body Contouring',     'duration' => 60,  'price' => 1000],
            ['category' => 'Body Contouring',    'name' => 'Cellulite Treatment',    'duration' => 45,  'price' => 900],
            // Facial
            ['category' => 'Facial Treatments',  'name' => 'Hydrafacial',            'duration' => 60,  'price' => 700],
            ['category' => 'Facial Treatments',  'name' => 'Chemical Peel',          'duration' => 45,  'price' => 500],
            ['category' => 'Facial Treatments',  'name' => 'Microneedling',          'duration' => 60,  'price' => 900],
            // Tattoo Removal
            ['category' => 'Tattoo Removal',     'name' => 'Small Tattoo (< 5cm)',   'duration' => 20,  'price' => 400],
            ['category' => 'Tattoo Removal',     'name' => 'Medium Tattoo (5-15cm)', 'duration' => 30,  'price' => 700],
            ['category' => 'Tattoo Removal',     'name' => 'Large Tattoo (> 15cm)',  'duration' => 45,  'price' => 1100],
            // Consultations
            ['category' => 'Consultations',      'name' => 'Initial Consultation',   'duration' => 30,  'price' => 0],
            ['category' => 'Consultations',      'name' => 'Follow-Up Consultation', 'duration' => 20,  'price' => 0],
        ];

        foreach ($services as $svc) {
            $service = Service::create([
                'company_id'       => $company->id,
                'category_id'      => $catMap[$svc['category']],
                'name'             => $svc['name'],
                'duration_minutes' => $svc['duration'],
                'price'            => $svc['price'],
                'is_active'        => true,
                'settings'         => ['laser_type' => 'Nd:YAG / Diode', 'skin_types' => 'I-VI'],
            ]);

            // Attach to all branches
            foreach ($branches as $branch) {
                $service->branches()->attach($branch->id, ['is_active' => true]);
            }
        }

        // Appointment reasons (configurable list)
        $reasons = [
            'Initial Consultation',
            'Laser Hair Removal Session',
            'Skin Treatment Session',
            'Follow-Up Visit',
            'Assessment / Review',
            'Package Inquiry',
            'Post-Treatment Check',
            'Emergency / Reaction',
            'Walk-In Inquiry',
        ];

        foreach ($reasons as $reason) {
            AppointmentReason::create(['company_id' => $company->id, 'name' => $reason]);
        }
    }
}
