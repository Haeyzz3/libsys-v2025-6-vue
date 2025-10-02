<?php

namespace Database\Seeders;

use App\Models\College;
use App\Models\Course;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AcademicSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        /**
         * COE
         */
        $coe = College::create([
            'code' => 'COE',
            'name' => 'College of Engineering',
            'college_type' => 'undergraduate',
        ]);

        $coe->courses()->saveMany([
            new Course(['code' => 'BSCE', 'name' => 'Bachelor of Science in Civil Engineering']),
            new Course(['code' => 'BSEE', 'name' => 'Bachelor of Science in Electrical Engineering']),
            new Course(['code' => 'BSECE', 'name' => 'Bachelor of Science in Electronics Engineering']),
            new Course(['code' => 'BSGE', 'name' => 'Bachelor of Science in Geodetic Engineering']),
            new Course(['code' => 'BSGeo', 'name' => 'Bachelor of Science in Geology']),
            new Course(['code' => 'BSME', 'name' => 'Bachelor of Science in Mechanical Engineering']),
            new Course(['code' => 'BSMinE', 'name' => 'Bachelor of Science in Mining Engineering']),
            new Course(['code' => 'BSSE', 'name' => 'Bachelor of Science in Sanitary Engineering']),
            new Course(['code' => 'BSABE', 'name' => 'Bachelor of Science in Agricultural and Biosystems Engineering']),
        ]);

        Course::where('code', 'BSCE')->first()->majors()->createMany([
            ['name' => 'Geotechnical Engineering'],
            ['name' => 'Structural Engineering'],
            ['name' => 'Transportation Engineering'],
        ]);

        Course::where('code', 'BSABE')->first()->majors()->createMany([
            ['name' => 'Land and Water Resources Engineering'],
            ['name' => 'AB Machinery & Power Engineering'],
            ['name' => 'AB Process Engineering'],
            ['name' => 'AB Structures and Environment Engineering'],
        ]);

        /**
         * CTET
         */
        $ctet = College::create([
            'code' => 'CTET',
            'name' => 'College of Teacher Education and Technology',
            'college_type' => 'undergraduate',
        ]);

        $ctet->courses()->createMany([
            [
                'code' => 'BSED',
                'name' => 'Bachelor of Secondary Education',
            ],
            [
                'code' => 'BEED',
                'name' => 'Bachelor of Elementary Education',
            ],
            [
                'code' => 'BECED',
                'name' => 'Bachelor of Early Childhood Education',
            ],
            [
                'code' => 'BSNED',
                'name' => 'Bachelor of Special Needs Education',
            ],
            [
                'code' => 'BSIT',
                'name' => 'Bachelor Science in Information Technology',
            ],
            [
                'code' => 'BTVTED',
                'name' => 'Bachelor of Technical-Vocational Teacher Education',
            ],
        ]);

        Course::where('code', 'BSED')->first()->majors()->createMany([
            [
                'name' => 'English',
            ],
            [
                'name' => 'Filipino',
            ],
            [
                'name' => 'Mathematics',
            ],
        ]);

        Course::where('code', 'BSIT')->first()->majors()->createMany([
            [
                'name' => 'Information Security',
            ],
        ]);

        Course::where('code', 'BTVTED')->first()->majors()->createMany([
            [
                'name' => 'Agricultural Crop Production',
            ],
            [
                'name' => 'Animal Production',
            ],
        ]);

        /**
         * CARS
         */

        $cars = College::create([
            'code' => 'CARS',
            'name' => 'College of Agricultural and Related Sciences',
            'college_type' => 'undergraduate',
        ]);

        $cars->courses()->createMany([
            [
                'code' => 'BSA',
                'name' => 'Bachelor of Science in Agriculture',
            ],
            [
                'code' => 'BSF',
                'name' => 'Bachelor of Science in Forestry',
            ],
        ]);

        Course::where('code', 'BSA')->first()->majors()->createMany([
            ['name' => 'Animal Science'],
            ['name' => 'Soil Science'],
            ['name' => 'Crop Science'],
            ['name' => 'Entomology'],
            ['name' => 'Agronomy'],
            ['name' => 'Horticulture'],
            ['name' => 'Enterprise Management'],
            ['name' => 'Plant Pathology'],
        ]);

        /**
         * GRADUATE SCHOOL OF AGRICULTURE AND RELATED SCIENCES (GSARS)
         */

        $gsars = College::create([
            'code' => 'GSARS',
            'name' => 'GraduateStudent School of Agriculture and Related Sciences',
            'college_type' => 'graduate',
        ]);

        $gsars->courses()->createMany([
            [
                'code' => 'PhDH',
                'name' => 'Doctor of Philosophy in Horticulture',
            ],
            [
                'code' => 'MSF',
                'name' => 'Master of Science in Forestry',
            ],
            [
                'code' => 'MSERM',
                'name' => 'Master of Science in Environmental Resource Management',
            ],
            [
                'code' => 'MSAGEXT',
                'name' => 'Master of Science in Agricultural Extension',
            ],
            [
                'code' => 'MSA',
                'name' => 'Master of Science in Agriculture',
            ],
            [
                'code' => 'MEXED',
                'name' => 'Master of Extension Education',
            ],
        ]);

        Course::where('code', 'MSF')->first()->majors()->createMany([
            ['name' => 'Forest Resource Management'],
        ]);

        Course::where('code', 'MSA')->first()->majors()->createMany([
            ['name' => 'Agronomy'],
            ['name' => 'Animal Science'],
            ['name' => 'Horticulture'],
            ['name' => 'Crop Protection'],
            ['name' => 'Soil Science'],
        ]);

        /**
         * GRADUATE SCHOOL OF ENGINEERING (GSOE)
         */

        $gsoe = College::create([
            'code' => 'GSOE',
            'name' => 'GraduateStudent School of Engineering',
            'college_type' => 'graduate',
        ]);

        $gsoe->courses()->createMany([
            [
                'code' => 'MSE',
                'name' => 'Master of Science in Engineering',
            ],
        ]);

        Course::where('code', 'MSE')->first()->majors()->createMany([
            ['name' => 'Land and Water Resources Engineering and Technology'],
        ]);

        /**
         * GRADUATE SCHOOL OF TEACHER EDUCATION AND TECHNOLOGY (GSTET)
         */

        $gstet = College::create([
            'code' => 'GSTET',
            'name' => 'GraduateStudent School of Teacher Education and Technology',
            'college_type' => 'graduate',
        ]);

        $gstet->courses()->createMany([
            [
                'code' => 'EdD',
                'name' => 'Doctor of Education major in Educational Management',
            ],
            [
                'code' => 'MEEM',
                'name' => 'Master of Education in Educational Management',
            ],
            [
                'code' => 'MEd-LT',
                'name' => 'Master of Education in Language Teaching',
            ],
        ]);

        /**
         * SOM
         */

        $som = College::create([
            'code' => 'SOM',
            'name' => 'School of Medicine',
            'college_type' => 'graduate',
        ]);

        $som->courses()->createMany([
            [
                'code' => 'MD',
                'name' => 'Doctor of Medicine',
            ],
        ]);
    }
}
