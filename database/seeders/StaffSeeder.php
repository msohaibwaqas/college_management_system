<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class StaffSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();
        
        // Map old subject IDs to new subject IDs based on the data
        $subjectMapping = [
            1 => 1,   // Urdu
            2 => 2,   // English
            3 => 3,   // Translation Of Holy Quran
            4 => 4,   // Islamic Studies
            5 => 5,   // Pakistan Studies
            6 => 6,   // Biology
            7 => 7,   // Physics
            8 => 8,   // Chemistry
            9 => 9,   // Mathematics
            10 => 10, // Computer Science
            11 => 11, // Civics
            13 => 13, // History
            14 => 14, // Islamic Education
            15 => 15, // Economics
            16 => 16, // Library Science
            17 => 17, // Sociology
            18 => 18, // Education
            19 => 19, // Psychology
            20 => 20, // Statistics
            21 => 21, // Geography
            22 => 22, // Persian Language
            23 => 23, // Punjabi Language
            25 => 25, // Arabic
            28 => 7,  // Assuming 28 maps to Physics (based on context)
        ];

        $teachers = [
            ['name' => 'MUHAMMAD WAQAS JAMIL', 'cnic' => '3110249518349', 'subject_id' => 14, 'status' => 'active'],
            ['name' => 'NASIR MEHMOOD BAJWA', 'cnic' => '3110254867603', 'subject_id' => 18, 'status' => 'retired'],
            ['name' => 'MUHAMMAD RIAZ', 'cnic' => '3110206323315', 'subject_id' => 16, 'status' => 'active'],
            ['name' => 'ABDUL REHMAN', 'cnic' => '3110206021979', 'subject_id' => 20, 'status' => 'active'],
            ['name' => 'YASAR HAMEED', 'cnic' => '3110238821375', 'subject_id' => 20, 'status' => 'active'],
            ['name' => 'MUHAMMAD SHARIF', 'cnic' => '3110271489695', 'subject_id' => 13, 'status' => 'active'],
            ['name' => 'GUL BAHAR AHMAD', 'cnic' => '3110206124805', 'subject_id' => 4, 'status' => 'active'],
            ['name' => 'MUHAMMAD AFZAL', 'cnic' => '3110205387873', 'subject_id' => 23, 'status' => 'active'],
            ['name' => 'MUHAMMAD IRFAN NASEEM', 'cnic' => '3110283055381', 'subject_id' => 1, 'status' => 'active'],
            ['name' => 'MUHAMMAD SHAHZAD AKHTAR BAIG', 'cnic' => '3110205910181', 'subject_id' => 1, 'status' => 'active'],
            ['name' => 'ABDUL QAYYUM', 'cnic' => '3110205764709', 'subject_id' => 4, 'status' => 'active'],
            ['name' => 'MURTAZA ALI', 'cnic' => '3110205614947', 'subject_id' => 16, 'status' => 'active'],
            ['name' => 'ZESHAN ZAHID', 'cnic' => '3110206221333', 'subject_id' => 2, 'status' => 'active'],
            ['name' => 'MUHAMMAD EJAZ', 'cnic' => '3110217161735', 'subject_id' => 16, 'status' => 'active'],
            ['name' => 'KHALID MEHMOOD', 'cnic' => '3110206364909', 'subject_id' => 13, 'status' => 'active'],
            ['name' => 'MUHAMMAD SALEEM', 'cnic' => '3110223940983', 'subject_id' => 2, 'status' => 'active'],
            ['name' => 'BILAL RASHEED', 'cnic' => '3110295693979', 'subject_id' => 4, 'status' => 'active'],
            ['name' => 'SAIF-UR-REHMAN', 'cnic' => '3110259870779', 'subject_id' => 11, 'status' => 'active'],
            ['name' => 'ALLAH DITTA JAVED', 'cnic' => '3110211000081', 'subject_id' => 16, 'status' => 'active'],
            ['name' => 'MUHAMMAD ASIF', 'cnic' => '3110176920787', 'subject_id' => 16, 'status' => 'active'],
            ['name' => 'ZAHID HUSSAIN', 'cnic' => '3110121295813', 'subject_id' => 8, 'status' => 'active'],
            ['name' => 'ATIQ UR REHMAN', 'cnic' => '3110270013035', 'subject_id' => 16, 'status' => 'active'],
            ['name' => 'MUHAMMAD IRSHAD', 'cnic' => '3110247907019', 'subject_id' => 2, 'status' => 'active'],
            ['name' => 'MUHAMMAD ASIF', 'cnic' => '3110256138959', 'subject_id' => 17, 'status' => 'active'],
            ['name' => 'MUDASSAR JAMEEL', 'cnic' => '3110477836929', 'subject_id' => 1, 'status' => 'active'],
            ['name' => 'RAZWAN YOUNIS', 'cnic' => '3110226852111', 'subject_id' => 9, 'status' => 'active'],
            ['name' => 'SARMAD IQBAL', 'cnic' => '3110213313265', 'subject_id' => 13, 'status' => 'active'],
            ['name' => 'MUHAMMAD SOHAIB WAQAS', 'cnic' => '3660278920119', 'subject_id' => 20, 'status' => 'active'],
            ['name' => 'MUHAMMAD MUBASHIR HASSAN', 'cnic' => '3110251772843', 'subject_id' => 4, 'status' => 'active'],
            ['name' => 'MUHAMMAD SUFYAN', 'cnic' => '3110403033715', 'subject_id' => 13, 'status' => 'active'],
            ['name' => 'MUHAMMAD SALEEM', 'cnic' => '3110273122383', 'subject_id' => 18, 'status' => 'active'],
            ['name' => 'MUHAMMAD WAQAS', 'cnic' => '3120319114251', 'subject_id' => 11, 'status' => 'active'],
            ['name' => 'MUHAMMAD MUBBASHIR ALTAF', 'cnic' => '3110269750245', 'subject_id' => 16, 'status' => 'active'],
            ['name' => 'ABDUL SATTAR', 'cnic' => '3110261095613', 'subject_id' => 4, 'status' => 'active'],
            ['name' => 'MUHAMMAD ASHRAF', 'cnic' => '3110535441645', 'subject_id' => 13, 'status' => 'transferred'],
            ['name' => 'MUHAMMAD ISHFAQ', 'cnic' => '3110237104149', 'subject_id' => 19, 'status' => 'active'],
            ['name' => 'MUHAMMAD ADNAN', 'cnic' => '3110464612909', 'subject_id' => 19, 'status' => 'active'],
            ['name' => 'MOHSIN IKRAM', 'cnic' => '03458706300', 'subject_id' => 1, 'status' => 'left'],
            ['name' => 'FAHAD YOUNAS', 'cnic' => '03006981683', 'subject_id' => 2, 'status' => 'active'],
            ['name' => 'ABDUL MAJEED', 'cnic' => '03039604868', 'subject_id' => 2, 'status' => 'left'],
            ['name' => 'MUHAMMAD SAAD HANIF', 'cnic' => '03000733723', 'subject_id' => 2, 'status' => 'left'],
            ['name' => 'MUHAMMAD NOMAN AJMAL', 'cnic' => '03257225858', 'subject_id' => 2, 'status' => 'active'],
            ['name' => 'MUHAMMAD ABRAR UL HASSAN', 'cnic' => '03026783143', 'subject_id' => 20, 'status' => 'active'],
            ['name' => 'MUHAMMAD SUFYAN', 'cnic' => '03067341101', 'subject_id' => 21, 'status' => 'active'],
            ['name' => 'TOFIQ AHMAD', 'cnic' => '03078731246', 'subject_id' => 8, 'status' => 'left'],
            ['name' => 'MUHAMMAD ZEESHAN JAVED', 'cnic' => '03066577054', 'subject_id' => 22, 'status' => 'left'],
            ['name' => 'SYED ALI FAIZAN', 'cnic' => '03086440492', 'subject_id' => 5, 'status' => 'left'],
            ['name' => 'NADEEM HUSSAIN', 'cnic' => '03049560471', 'subject_id' => 7, 'status' => 'active'],
            ['name' => 'MUHAMMAD KHAN', 'cnic' => '03027575496', 'subject_id' => 16, 'status' => 'active'],
            ['name' => 'FIDA UR REHMAN', 'cnic' => '03344068587', 'subject_id' => 2, 'status' => 'transferred'],
            ['name' => 'MUHAMMAD SHAHID SALEEM', 'cnic' => '03006857055', 'subject_id' => 15, 'status' => 'transferred'],
            ['name' => 'SHAHID IQBAL', 'cnic' => '03347003846', 'subject_id' => 1, 'status' => 'transferred'],
            ['name' => 'SHAFQAT ALI', 'cnic' => '03346875582', 'subject_id' => 19, 'status' => 'active'],
            ['name' => 'ALTAF KHAN', 'cnic' => '03347021170', 'subject_id' => 11, 'status' => 'transferred'],
            ['name' => 'MUHAMMAD MAZHAR', 'cnic' => '03037619693', 'subject_id' => 1, 'status' => 'active'],
            ['name' => 'MUHAMMAD ZEESHAN', 'cnic' => '3110173699301', 'subject_id' => 3, 'status' => 'active'],
            ['name' => 'MUHAMMAD ARSLAN JAVED', 'cnic' => '3660195157689', 'subject_id' => 8, 'status' => 'active'],
            ['name' => 'MUHAMMAD ANS ALI', 'cnic' => '3110524032009', 'subject_id' => 2, 'status' => 'active'],
            ['name' => 'USAMA IDREES', 'cnic' => '3110585559167', 'subject_id' => 5, 'status' => 'active'],
            ['name' => 'MUHAMMAD ABDULLAH INAM', 'cnic' => '3110146205673', 'subject_id' => 1, 'status' => 'active'],
            ['name' => 'MUHAMMAD SHAHID', 'cnic' => '3110282587395', 'subject_id' => 15, 'status' => 'active'],
            ['name' => 'NADEEM HUSSAIN', 'cnic' => '3110166813748', 'subject_id' => 7, 'status' => 'active'],
        ];

        foreach ($teachers as $index => $teacher) {
            // Parse name into first and last name
            $nameParts = explode(' ', trim($teacher['name']));
            $firstName = $nameParts[0];
            $lastName = count($nameParts) > 1 ? implode(' ', array_slice($nameParts, 1)) : '';

            // Map subject ID
            $subjectId = isset($subjectMapping[$teacher['subject_id']]) 
                ? $subjectMapping[$teacher['subject_id']] 
                : $teacher['subject_id'];

            // Create staff record
            $staffId = DB::table('staff')->insertGetId([
                'staff_id' => 'TCH-' . str_pad($index + 1, 4, '0', STR_PAD_LEFT),
                'first_name' => $firstName,
                'last_name' => $lastName,
                'father_name' => 'N/A', // Default value, can be updated manually
                'cnic' => $teacher['cnic'],
                'date_of_birth' => '1980-01-01', // Default value, can be updated manually
                'category' => 'teaching',
                'designation' => 'Teacher',
                'bps' => 17, // Default BPS for teachers
                'status' => $teacher['status'],
                'gender' => 'male', // Default, can be updated manually
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            // Create subject specialization record
            if ($subjectId) {
                DB::table('staff_subject_specializations')->insert([
                    'staff_id' => $staffId,
                    'subject_id' => $subjectId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        $this->command->info('Successfully seeded ' . count($teachers) . ' staff members with their subject specializations.');
    }
}
