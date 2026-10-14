<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SubjectSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            ['id' => 1, 'name' => 'Urdu', 'code' => '6002', 'type' => 'Compulsory', 'class_level' => 'both', 'total_11' => 100, 'theory_12' => 100, 'practical_12' => 0, 'created_at' => '2025-11-19 03:26:22', 'updated_at' => '2025-11-19 05:34:46'],
            ['id' => 2, 'name' => 'English', 'code' => '6001', 'type' => 'Compulsory', 'class_level' => 'both', 'total_11' => 100, 'theory_12' => 100, 'practical_12' => 0, 'created_at' => '2025-11-19 04:46:12', 'updated_at' => '2025-11-19 05:35:11'],
            ['id' => 3, 'name' => 'Translation Of Holy Quran', 'code' => 'TTQ', 'type' => 'Compulsory', 'class_level' => 'both', 'total_11' => 50, 'theory_12' => 50, 'practical_12' => 0, 'created_at' => '2025-11-19 04:46:45', 'updated_at' => '2025-11-19 04:46:45'],
            ['id' => 4, 'name' => 'Islamic Education', 'code' => '6092', 'type' => 'Compulsory', 'class_level' => '11', 'total_11' => 50, 'theory_12' => 0, 'practical_12' => 0, 'created_at' => '2025-11-19 05:06:42', 'updated_at' => '2025-11-19 05:35:35'],
            ['id' => 5, 'name' => 'Pakistan Studies', 'code' => '6091', 'type' => 'Compulsory', 'class_level' => '12', 'total_11' => 0, 'theory_12' => 50, 'practical_12' => 0, 'created_at' => '2025-11-19 05:17:23', 'updated_at' => '2025-11-19 05:36:24'],
            ['id' => 6, 'name' => 'Biology', 'code' => '6046', 'type' => 'Elective', 'class_level' => 'both', 'total_11' => 85, 'theory_12' => 85, 'practical_12' => 30, 'created_at' => '2025-11-19 05:21:14', 'updated_at' => '2025-11-19 05:36:39'],
            ['id' => 7, 'name' => 'Physics', 'code' => '6047', 'type' => 'Elective', 'class_level' => 'both', 'total_11' => 85, 'theory_12' => 85, 'practical_12' => 30, 'created_at' => '2025-11-19 05:21:50', 'updated_at' => '2025-11-19 05:37:00'],
            ['id' => 8, 'name' => 'Chemistry', 'code' => '6048', 'type' => 'Elective', 'class_level' => 'both', 'total_11' => 85, 'theory_12' => 85, 'practical_12' => 30, 'created_at' => '2025-11-19 05:22:30', 'updated_at' => '2025-11-19 05:37:07'],
            ['id' => 9, 'name' => 'Mathematics', 'code' => '6019', 'type' => 'Elective', 'class_level' => 'both', 'total_11' => 100, 'theory_12' => 100, 'practical_12' => 0, 'created_at' => '2025-11-19 05:23:03', 'updated_at' => '2025-11-19 05:37:28'],
            ['id' => 10, 'name' => 'Computer Science', 'code' => '6083', 'type' => 'Elective', 'class_level' => 'both', 'total_11' => 75, 'theory_12' => 75, 'practical_12' => 50, 'created_at' => '2025-11-19 05:23:47', 'updated_at' => '2025-11-19 05:41:59'],
            ['id' => 11, 'name' => 'Civics', 'code' => '6017', 'type' => 'Elective', 'class_level' => 'both', 'total_11' => 100, 'theory_12' => 100, 'practical_12' => 0, 'created_at' => '2025-11-19 05:27:06', 'updated_at' => '2025-11-19 05:39:05'],
            ['id' => 12, 'name' => 'Health And Physical Education', 'code' => '6042', 'type' => 'Elective', 'class_level' => 'both', 'total_11' => 85, 'theory_12' => 85, 'practical_12' => 30, 'created_at' => '2025-11-19 05:28:15', 'updated_at' => '2025-11-19 05:41:10'],
            ['id' => 13, 'name' => 'History', 'code' => '6015', 'type' => 'Elective', 'class_level' => 'both', 'total_11' => 100, 'theory_12' => 100, 'practical_12' => 0, 'created_at' => '2025-11-19 05:28:46', 'updated_at' => '2025-11-19 05:38:11'],
            ['id' => 14, 'name' => 'Islamic Studies', 'code' => '6020', 'type' => 'Elective', 'class_level' => 'both', 'total_11' => 100, 'theory_12' => 100, 'practical_12' => 0, 'created_at' => '2025-11-19 05:29:10', 'updated_at' => '2025-11-19 05:39:45'],
            ['id' => 15, 'name' => 'Economics', 'code' => '6011', 'type' => 'Elective', 'class_level' => 'both', 'total_11' => 100, 'theory_12' => 100, 'practical_12' => 0, 'created_at' => '2025-11-19 05:29:31', 'updated_at' => '2025-11-19 05:38:24'],
            ['id' => 16, 'name' => 'Library Science', 'code' => '6008', 'type' => 'Elective', 'class_level' => 'both', 'total_11' => 85, 'theory_12' => 85, 'practical_12' => 30, 'created_at' => '2025-11-19 05:29:53', 'updated_at' => '2025-11-19 05:42:12'],
            ['id' => 17, 'name' => 'Sociology', 'code' => '6045', 'type' => 'Elective', 'class_level' => 'both', 'total_11' => 100, 'theory_12' => 100, 'practical_12' => 0, 'created_at' => '2025-11-19 05:30:18', 'updated_at' => '2025-11-19 05:41:41'],
            ['id' => 18, 'name' => 'Education', 'code' => '6043', 'type' => 'Elective', 'class_level' => 'both', 'total_11' => 100, 'theory_12' => 100, 'practical_12' => 0, 'created_at' => '2025-11-19 05:30:40', 'updated_at' => '2025-11-19 05:41:27'],
            ['id' => 19, 'name' => 'Psychology', 'code' => '6016', 'type' => 'Elective', 'class_level' => 'both', 'total_11' => 85, 'theory_12' => 85, 'practical_12' => 30, 'created_at' => '2025-11-19 05:31:02', 'updated_at' => '2025-11-19 05:38:51'],
            ['id' => 20, 'name' => 'Statistics', 'code' => '6018', 'type' => 'Elective', 'class_level' => 'both', 'total_11' => 85, 'theory_12' => 85, 'practical_12' => 30, 'created_at' => '2025-11-19 05:31:23', 'updated_at' => '2025-11-19 05:39:25'],
            ['id' => 21, 'name' => 'Geography', 'code' => '6012', 'type' => 'Elective', 'class_level' => 'both', 'total_11' => 85, 'theory_12' => 85, 'practical_12' => 30, 'created_at' => '2025-11-19 05:31:46', 'updated_at' => '2025-11-19 05:38:35'],
            ['id' => 22, 'name' => 'Persian Language', 'code' => '6034', 'type' => 'Elective', 'class_level' => 'both', 'total_11' => 100, 'theory_12' => 100, 'practical_12' => 0, 'created_at' => '2025-11-19 05:32:09', 'updated_at' => '2025-11-19 05:40:17'],
            ['id' => 23, 'name' => 'Punjabi Language', 'code' => '6032', 'type' => 'Elective', 'class_level' => 'both', 'total_11' => 100, 'theory_12' => 100, 'practical_12' => 0, 'created_at' => '2025-11-19 05:32:30', 'updated_at' => '2025-11-19 05:40:35'],
            ['id' => 24, 'name' => 'Urdu Elective', 'code' => '6037', 'type' => 'Elective', 'class_level' => 'both', 'total_11' => 100, 'theory_12' => 100, 'practical_12' => 0, 'created_at' => '2025-11-19 05:32:57', 'updated_at' => '2025-11-19 05:40:57'],
            ['id' => 25, 'name' => 'Arabic', 'code' => '6024', 'type' => 'Elective', 'class_level' => 'both', 'total_11' => 100, 'theory_12' => 100, 'practical_12' => 0, 'created_at' => '2025-11-19 05:33:20', 'updated_at' => '2025-11-19 05:39:59'],
        ];

        DB::table('subjects')->insert($data);
    }
}
