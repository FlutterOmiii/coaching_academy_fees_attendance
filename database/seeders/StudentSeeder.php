<?php

namespace Database\Seeders;

use App\Models\Batch;
use App\Models\BatchTransfer;
use App\Models\Student;
use App\Models\StudentDocument;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class StudentSeeder extends Seeder
{
    private const FIRST_NAMES_MALE = [
        'Aarav', 'Vivaan', 'Aditya', 'Vihaan', 'Arjun', 'Sai', 'Reyansh', 'Krishna', 'Ishaan', 'Rudra',
        'Kabir', 'Ayaan', 'Dhruv', 'Atharv', 'Rohan', 'Karan', 'Yash', 'Nikhil', 'Siddharth', 'Manav',
        'Harsh', 'Om', 'Parth', 'Tanish', 'Veer', 'Shaurya', 'Ansh', 'Laksh', 'Advik', 'Neel',
    ];

    private const FIRST_NAMES_FEMALE = [
        'Ananya', 'Diya', 'Saanvi', 'Aadhya', 'Kiara', 'Myra', 'Anika', 'Navya', 'Riya', 'Ishita',
    ];

    private const LAST_NAMES = [
        'Sharma', 'Patel', 'Reddy', 'Kulkarni', 'Deshmukh', 'Joshi', 'Nair', 'Iyer', 'Gupta', 'Verma',
        'Mehta', 'Shah', 'Rao', 'Pillai', 'Chauhan', 'Bhosale', 'Jadhav', 'Pawar', 'Kadam', 'Shinde',
    ];

    private const SCHOOLS = [
        'Delhi Public School', 'St. Xavier\'s High School', 'Podar International', 'Vibgyor High',
        'Symbiosis School', 'City International School', 'Bishop\'s School', 'Loyola High School',
    ];

    /** Admissions per month over the last 14 months — trending upward. */
    private const MONTHLY_ADMISSIONS = [4, 5, 6, 7, 8, 9, 8, 10, 11, 12, 11, 13, 14, 12];

    /** age_group => [min_age, max_age] */
    private const AGE_RANGES = [
        'under_10' => [7, 9],
        'under_12' => [10, 11],
        'under_14' => [12, 13],
        'under_16' => [14, 15],
        'under_19' => [16, 18],
        'senior' => [19, 26],
        'open' => [12, 22],
    ];

    public function run(): void
    {
        $batches = Batch::all();
        // Track remaining seats so no batch is seeded beyond its capacity.
        $remaining = $batches->pluck('capacity', 'id')->all();

        $counter = 0;

        foreach (self::MONTHLY_ADMISSIONS as $offset => $count) {
            $monthsAgo = count(self::MONTHLY_ADMISSIONS) - 1 - $offset;

            for ($i = 0; $i < $count; $i++) {
                $counter++;

                $admissionDate = Carbon::now()
                    ->subMonths($monthsAgo)
                    ->startOfMonth()
                    ->addDays(random_int(0, 27));

                if ($admissionDate->isFuture()) {
                    $admissionDate = Carbon::now()->subDays(random_int(1, 5));
                }

                $batch = $this->pickBatch($batches, $remaining);
                if (! $batch) {
                    continue;
                }

                $student = $this->createStudent($counter, $admissionDate, $batch, $monthsAgo);

                $remaining[$batch->id]--;

                $this->attachBatch($student, $batch, $admissionDate);
                $this->seedDocuments($student);
            }
        }

        $this->seedTransfers($batches);
    }

    private function pickBatch(iterable $batches, array $remaining): ?Batch
    {
        $available = collect($batches)->filter(fn (Batch $b) => ($remaining[$b->id] ?? 0) > 0);

        return $available->isEmpty() ? null : $available->random();
    }

    /** Random element of a list. */
    private function pick(array $items)
    {
        return $items[array_rand($items)];
    }

    private function createStudent(int $counter, Carbon $admissionDate, Batch $batch, int $monthsAgo): Student
    {
        [$minAge, $maxAge] = self::AGE_RANGES[$batch->age_group] ?? [10, 18];
        $age = random_int($minAge, $maxAge);

        $isFemale = random_int(1, 10) === 1;
        $first = $isFemale
            ? self::FIRST_NAMES_FEMALE[array_rand(self::FIRST_NAMES_FEMALE)]
            : self::FIRST_NAMES_MALE[array_rand(self::FIRST_NAMES_MALE)];
        $last = self::LAST_NAMES[array_rand(self::LAST_NAMES)];

        // Recent joiners may still be awaiting approval; a few are rejected.
        $admissionStatus = match (true) {
            $monthsAgo === 0 && random_int(1, 3) === 1 => 'pending',
            random_int(1, 40) === 1 => 'rejected',
            default => 'approved',
        };

        $status = $admissionStatus !== 'approved'
            ? 'inactive'
            : (random_int(1, 100) <= 88 ? 'active' : 'inactive');

        $role = $this->pick(['batter', 'bowler', 'batting_allrounder', 'bowling_allrounder', 'wicket_keeper']);
        $battingStyle = random_int(1, 4) === 1 ? 'left_hand' : 'right_hand';

        $bowlingStyle = match ($role) {
            'wicket_keeper' => 'none',
            'batter' => $this->pick(['right_arm_medium', 'right_arm_off_spin', 'none']),
            default => $this->pick([
                'right_arm_fast', 'right_arm_medium', 'right_arm_off_spin', 'right_arm_leg_spin',
                'left_arm_fast', 'left_arm_medium', 'left_arm_orthodox',
            ]),
        };

        return Student::create([
            'student_code' => 'STU'.str_pad((string) $counter, 4, '0', STR_PAD_LEFT),
            'first_name' => $first,
            'last_name' => $last,
            'date_of_birth' => Carbon::now()->subYears($age)->subDays(random_int(0, 364))->toDateString(),
            'gender' => $isFemale ? 'female' : 'male',
            'blood_group' => $this->pick(['A+', 'B+', 'O+', 'AB+', 'A-', 'O-']),
            'email' => strtolower($first.'.'.$last.$counter).'@example.com',
            'phone' => '9'.random_int(100000000, 999999999),
            'address' => random_int(1, 200).', '.$this->pick(['Kothrud', 'Baner', 'Aundh', 'Viman Nagar', 'Hadapsar']),
            'city' => 'Pune',
            'state' => 'Maharashtra',
            'pincode' => '4110'.str_pad((string) random_int(1, 60), 2, '0', STR_PAD_LEFT),
            'school_name' => self::SCHOOLS[array_rand(self::SCHOOLS)],
            'guardian_name' => self::FIRST_NAMES_MALE[array_rand(self::FIRST_NAMES_MALE)].' '.$last,
            'guardian_phone' => '9'.random_int(100000000, 999999999),
            'guardian_email' => strtolower('parent.'.$last.$counter).'@example.com',
            'guardian_relation' => $this->pick(['Father', 'Mother', 'Uncle', 'Guardian']),
            'playing_role' => $role,
            'batting_style' => $battingStyle,
            'bowling_style' => $bowlingStyle,
            'admission_date' => $admissionDate->toDateString(),
            'admission_status' => $admissionStatus,
            'status' => $status,
            'medical_notes' => random_int(1, 12) === 1 ? 'Mild asthma — inhaler kept with coach.' : null,
        ]);
    }

    private function attachBatch(Student $student, Batch $batch, Carbon $admissionDate): void
    {
        $student->batches()->attach($batch->id, [
            'joined_on' => $admissionDate->toDateString(),
            'left_on' => $student->status === 'inactive' ? $admissionDate->copy()->addMonths(3)->toDateString() : null,
            'status' => $student->status === 'inactive' ? 'left' : 'active',
        ]);
    }

    private function seedDocuments(Student $student): void
    {
        $types = ['photo', 'birth_certificate', 'id_proof'];

        foreach (array_slice($types, 0, random_int(1, 3)) as $type) {
            StudentDocument::create([
                'student_id' => $student->id,
                'type' => $type,
                'title' => StudentDocument::TYPES[$type],
                'file_path' => 'documents/'.$student->student_code.'-'.$type.'.pdf',
                'mime_type' => $type === 'photo' ? 'image/jpeg' : 'application/pdf',
                'file_size' => random_int(50_000, 2_000_000),
            ]);
        }
    }

    /** A handful of students moved up a batch as they progressed. */
    private function seedTransfers($batches): void
    {
        $movers = Student::active()->inRandomOrder()->limit(8)->get();

        foreach ($movers as $student) {
            $from = $student->batches()->first();
            $to = $batches->where('id', '!=', $from?->id)->random();

            if (! $from || ! $to) {
                continue;
            }

            $date = Carbon::now()->subDays(random_int(10, 120));

            BatchTransfer::create([
                'student_id' => $student->id,
                'from_batch_id' => $from->id,
                'to_batch_id' => $to->id,
                'transferred_on' => $date->toDateString(),
                'reason' => $this->pick([
                    'Promoted to higher skill level', 'Schedule conflict', 'Age group change', 'Parent request',
                ]),
            ]);
        }
    }
}
