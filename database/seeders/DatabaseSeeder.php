<?php

namespace Database\Seeders;

use App\Models\Assessment;
use App\Models\Attendance;
use App\Models\ClassResultStatus;
use App\Models\ClassSubject;
use App\Models\Committee;
use App\Models\Department;
use App\Models\District;
use App\Models\Division;
use App\Models\FeedbackItem;
use App\Models\GovernanceMembership;
use App\Models\InspectionReport;
use App\Models\Level;
use App\Models\Mark;
use App\Models\Notice;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Student;
use App\Models\Subject;
use App\Models\SubjectResultStatus;
use App\Models\TeacherProfile;
use App\Models\Term;
use App\Models\User;
use App\Models\Zone;
use App\Services\SchoolSetup;
use App\Services\Timetable;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public const PASSWORD = 'Malawi@2026';

    protected string $hash;
    protected int $phoneSeq = 1101;
    protected array $used = [];

    protected array $female = ['Chikondi', 'Tiwonge', 'Thokozani', 'Tadala', 'Tamanda', 'Chimwemwe', 'Takondwa', 'Lusungu', 'Temwa', 'Tawonga',
        'Chifundo', 'Grace', 'Esnart', 'Ruth', 'Stella', 'Agnes', 'Martha', 'Esther', 'Memory', 'Loveness', 'Precious', 'Fanny', 'Alinafe',
        'Towera', 'Mercy', 'Faith', 'Tionge', 'Chisomo', 'Thandiwe', 'Linda', 'Eneless', 'Dorothy', 'Maria', 'Tereza', 'Sungeni', 'Wezi'];

    protected array $male = ['Kondwani', 'Madalitso', 'Dalitso', 'Limbani', 'Yamikani', 'Pemphero', 'Wongani', 'Mayeso', 'Kumbukani', 'Mwayi',
        'Blessings', 'Joseph', 'Patrick', 'Emmanuel', 'Innocent', 'Gift', 'Chisomo', 'Mphatso', 'Hastings', 'Francis', 'Charles', 'Yohane',
        'Moses', 'Isaac', 'Peter', 'Daniel', 'Kelvin', 'Frank', 'Samuel', 'Thoko', 'Lloyd', 'Wisdom', 'Steven', 'Harold', 'Ackim', 'Chrispin'];

    protected array $surnames = ['Banda', 'Phiri', 'Mwale', 'Chirwa', 'Kamanga', 'Nyirenda', 'Mkandawire', 'Gondwe', 'Msiska', 'Kumwenda',
        'Kachingwe', 'Mbewe', 'Tembo', 'Zulu', 'Jere', 'Chimwaza', 'Kalua', 'Mhango', 'Ngwira', 'Mussa', 'Kapito', 'Nkhoma', 'Moyo', 'Lungu',
        'Chikoti', 'Makwinja', 'Kalilani', 'Mangani', 'Saidi', 'Bakali', 'Chiumia', 'Mponda', 'Kaunda', 'Mvula', 'Chinsinga', 'Matola',
        'Chiwaya', 'Ndalama', 'Kanyama', 'Mlenga', 'Chimombo', 'Nsona', 'Gama', 'Luhanga', 'Kaponda', 'Chunga', 'Mtambo', 'Namarika'];

    protected array $villages = [
        ['Mpemba', 'Kapeni', 'Blantyre'], ['Chigumula', 'Kuntaja', 'Blantyre'], ['Lunzu', 'Kapeni', 'Blantyre'],
        ['Chilomoni', 'Kapeni', 'Blantyre'], ['Kameza', 'Machinjiri', 'Blantyre'], ['Namiwawa', 'Somba', 'Blantyre'],
        ['Mikolongwe', 'Nchema', 'Chiradzulu'], ['Khongoloni', 'Nsabwe', 'Thyolo'], ['Masambanjati', 'Chimaliro', 'Thyolo'],
        ['Chinakanaka', 'Mabuka', 'Mulanje'], ['Nkando', 'Juma', 'Mulanje'], ['Ntonda', 'Chitera', 'Chiradzulu'],
        ['Ngumbe', 'Kunthembwe', 'Blantyre'], ['Mdeka', 'Kuntaja', 'Blantyre'],
    ];

    public function run(): void
    {
        mt_srand(2026);
        $this->hash = Hash::make(self::PASSWORD);
        $this->call(GradeBandSeeder::class);

        $jur = $this->jurisdictions();

        // National level
        $this->person('Mphatso Kalilani', 'SYSTEM_ADMIN', ['email' => 'admin@education.gov.mw', 'gender' => 'Male']);
        $this->person('Grace Chinsinga', 'SYSTEM_ADMIN', ['email' => 'grace.chinsinga@education.gov.mw', 'gender' => 'Female']);

        // Supervisors for the South West Education Division
        $sw = $jur['divisions']['South West Education Division'];
        $bc = $jur['districts']['Blantyre City'];
        $br = $jur['districts']['Blantyre Rural'];
        $this->person('Harold Chiumia', 'EDM', ['email' => 'edm.southwest@education.gov.mw', 'division_id' => $sw->id, 'gender' => 'Male']);
        $this->person('Stella Mhango', 'DEM', ['email' => 'dem.blantyrecity@education.gov.mw', 'division_id' => $sw->id, 'district_id' => $bc->id, 'gender' => 'Female']);
        $this->person('Francis Kaunda', 'DEM', ['email' => 'dem.blantyrerural@education.gov.mw', 'division_id' => $sw->id, 'district_id' => $br->id, 'gender' => 'Male']);
        $pea = $this->person('Esnart Chunga', 'PEA', ['email' => 'pea.chilomoni@education.gov.mw', 'division_id' => $sw->id, 'district_id' => $bc->id,
            'zone_id' => $jur['zones']['Chilomoni']->id, 'gender' => 'Female']);
        $this->person('Lloyd Mtambo', 'EDM', ['email' => 'edm.central.west@education.gov.mw', 'division_id' => $jur['divisions']['Central West Education Division']->id, 'gender' => 'Male']);

        $primary = $this->primarySchool($jur);
        $secondary = $this->secondarySchool($jur);
        $this->otherSchools($jur);

        // A past inspection visit by the PEA
        InspectionReport::create([
            'school_id' => $primary->id, 'supervisor_id' => $pea->id, 'visit_date' => '2026-09-17',
            'directorate' => $primary->directorate(), 'overall_rating' => 'GOOD',
            'findings' => "Lesson plans and schemes of work were up to date in 7 of the 8 classes observed. Standard 1 has 74 learners against a capacity of 60 and shares one room with Standard 2 in the afternoon. The feeding programme is running and attendance on the day was 91 percent.",
            'recommendations' => "Request an additional temporary classroom through the DEM office before the rainy season. The Standard 3 teacher should attend the zonal Chichewa literacy training on 8 October. Keep continuous assessment records in the system for every learner.",
            'flag_follow_up' => true, 'follow_up_by' => '2026-10-30', 'status' => 'SUBMITTED',
        ]);
    }

    protected function jurisdictions(): array
    {
        $map = [
            'Northern Education Division' => ['Northern', ['Chitipa', 'Karonga', 'Rumphi', 'Mzimba North', 'Mzimba South', 'Nkhata Bay', 'Likoma', 'Mzuzu City']],
            'Central East Education Division' => ['Central', ['Kasungu', 'Nkhotakota', 'Ntchisi', 'Dowa', 'Salima']],
            'Central West Education Division' => ['Central', ['Lilongwe City', 'Lilongwe Rural East', 'Lilongwe Rural West', 'Dedza', 'Ntcheu', 'Mchinji']],
            'South East Education Division' => ['Southern', ['Mangochi', 'Machinga', 'Zomba City', 'Zomba Rural', 'Balaka']],
            'South West Education Division' => ['Southern', ['Blantyre City', 'Blantyre Rural', 'Mwanza', 'Neno', 'Chikwawa', 'Nsanje']],
            'Shire Highlands Education Division' => ['Southern', ['Thyolo', 'Mulanje', 'Phalombe', 'Chiradzulu']],
        ];
        $zones = [
            'Blantyre City' => ['Chilomoni', 'Ndirande', 'Bangwe', 'Limbe', 'Chichiri', 'Soche'],
            'Blantyre Rural' => ['Lunzu', 'Chileka', 'Lirangwe', 'Chikuli'],
            'Lilongwe City' => ['Area 25', 'Kawale', 'Chinsapo'],
            'Dowa' => ['Mponela', 'Madisi'],
            'Zomba City' => ['Chikamveka', 'Sadzi'],
            'Mzuzu City' => ['Chibavi', 'Katoto'],
        ];

        $out = ['divisions' => [], 'districts' => [], 'zones' => []];
        foreach ($map as $name => [$region, $districts]) {
            $div = Division::create(['name' => $name, 'region' => $region]);
            $out['divisions'][$name] = $div;
            foreach ($districts as $d) {
                $district = District::create(['division_id' => $div->id, 'name' => $d]);
                $out['districts'][$d] = $district;
                foreach ($zones[$d] ?? [] as $z) {
                    $out['zones'][$z] = Zone::create(['district_id' => $district->id, 'name' => $z]);
                }
            }
        }

        return $out;
    }

    protected function phone(): string
    {
        $prefix = ['099', '088', '098', '089'][$this->phoneSeq % 4];

        return '+265'.substr($prefix, 1).str_pad((string) (1000000 + $this->phoneSeq++ * 7919 % 8999999), 7, '0', STR_PAD_LEFT);
    }

    protected function person(string $name, string $role, array $extra = []): User
    {
        return User::create(array_merge([
            'name' => $name,
            'role' => $role,
            'status' => 'ACTIVE',
            'password' => $this->hash,
            'must_change_password' => false,
            'policy_accepted_at' => now()->subDays(20),
            'activated_at' => now()->subDays(20),
            'phone' => $this->phone(),
            'preferred_channel' => 'SMS',
        ], $extra));
    }

    protected function randomName(?string $gender = null): array
    {
        do {
            $gender = $gender ?? (mt_rand(0, 1) ? 'Female' : 'Male');
            $pool = $gender === 'Female' ? $this->female : $this->male;
            $first = $pool[mt_rand(0, count($pool) - 1)];
            $last = $this->surnames[mt_rand(0, count($this->surnames) - 1)];
        } while (isset($this->used[$first.$last]) && count($this->used) < 1500);
        $this->used[$first.$last] = true;

        return [$first, $last, $gender];
    }

    protected function staff(School $school, string $domain, string $name, string $gender, string $role, string $qualification, string $specialisation, int $n): User
    {
        [$first, $last] = explode(' ', $name, 2);
        $user = $this->person($name, $role, [
            'school_id' => $school->id,
            'email' => strtolower($first.'.'.$last).'@'.$domain,
            'gender' => $gender,
            'national_id' => strtoupper(substr(md5($name), 0, 8)),
            'preferred_channel' => $n % 3 === 0 ? 'EMAIL' : 'SMS',
        ]);
        TeacherProfile::create([
            'user_id' => $user->id,
            'employment_number' => 'TSC'.str_pad((string) (40210 + $school->id * 100 + $n), 6, '0', STR_PAD_LEFT),
            'qualification' => $qualification,
            'specialisation' => $specialisation,
            'first_appointment' => Carbon::create(2004 + ($n * 3) % 18, 1 + $n % 12, 1)->toDateString(),
        ]);

        return $user;
    }

    protected function learners(School $school, SchoolClass $class, int $count, int $ageAtStart, int &$seq): array
    {
        $out = [];
        for ($i = 0; $i < $count; $i++) {
            [$first, $last, $gender] = $this->randomName();
            $v = $this->villages[mt_rand(0, count($this->villages) - 1)];
            $out[] = Student::create([
                'school_id' => $school->id,
                'school_class_id' => $class->id,
                'admission_number' => $school->code.'/'.($seq++),
                'first_name' => $first,
                'last_name' => $last,
                'gender' => $gender,
                'date_of_birth' => Carbon::create(2026 - $ageAtStart, mt_rand(1, 12), mt_rand(1, 28))->toDateString(),
                'home_village' => $v[0],
                'traditional_authority' => $v[1],
                'home_district' => $v[2],
                'status' => 'ENROLLED',
                'admitted_on' => Carbon::create(2026 - max(0, $ageAtStart - 6), 9, 14)->toDateString(),
            ]);
        }

        return $out;
    }

    protected function parents(School $school, array $students, int $every = 2): void
    {
        foreach ($students as $i => $student) {
            if ($i % $every !== 0) {
                continue;
            }
            $gender = mt_rand(0, 2) ? 'Female' : 'Male';
            [$first] = $this->randomName($gender);
            $parent = $this->person($first.' '.$student->last_name, 'PARENT', [
                'school_id' => $school->id, 'gender' => $gender,
                'preferred_channel' => 'SMS',
                'email' => $i === 0 ? strtolower($first.'.'.$student->last_name).'@gmail.com' : null,
            ]);
            $parent->children()->attach($student->id, ['relationship' => $gender === 'Female' ? 'Mother' : 'Father']);
        }
    }

    protected function governance(School $school, array $members, string $domain): void
    {
        foreach ($members as $i => [$name, $gender, $body, $position, $voting]) {
            $user = $this->person($name, 'GOVERNANCE', [
                'school_id' => $school->id, 'gender' => $gender,
                'email' => $i < 2 ? strtolower(str_replace(' ', '.', $name)).'@'.$domain : null,
            ]);
            GovernanceMembership::create([
                'school_id' => $school->id, 'user_id' => $user->id, 'body' => $body,
                'position' => $position, 'is_voting' => $voting, 'term_ends' => '2028-08-31',
            ]);
        }
    }

    protected function createClass(School $school, $year, Level $level, string $stream, ?User $teacher, int $capacity, string $room): SchoolClass
    {
        $section = $level->phase === 'PRIMARY'
            ? Section::where('school_id', $school->id)->where('from_ordinal', '<=', $level->ordinal)->where('to_ordinal', '>=', $level->ordinal)->first() : null;
        $class = SchoolClass::create([
            'school_id' => $school->id, 'academic_year_id' => $year->id, 'level_id' => $level->id,
            'section_id' => $section?->id, 'stream' => $stream, 'class_teacher_id' => $teacher?->id,
            'capacity' => $capacity, 'room' => $room,
        ]);
        foreach (Subject::where('school_id', $school->id)->where('phase', $level->phase)->get() as $subject) {
            ClassSubject::create([
                'school_id' => $school->id, 'school_class_id' => $class->id, 'subject_id' => $subject->id,
                'periods_per_week' => in_array($subject->code, ['ENG', 'MAT', 'CHI']) ? 6 : 4,
            ]);
        }

        return $class;
    }

    /**
     * Record one continuous assessment per class subject with realistic spread.
     */
    protected function assessmentMarks(School $school, Term $term, SchoolClass $class, string $status, ?User $validator, array $ability): void
    {
        $class->load(['classSubjects.subject', 'classSubjects.teacher']);
        foreach ($class->classSubjects as $cs) {
            if (! $cs->teacher) {
                continue;
            }
            $lang = $class->level->instruction_language === 'Chichewa' && $cs->subject->code !== 'ENG' ? 'Chichewa' : 'English';
            $a = Assessment::create([
                'school_id' => $school->id, 'class_subject_id' => $cs->id, 'term_id' => $term->id, 'kind' => 'CA',
                'title' => 'Continuous Assessment 1', 'language' => $lang, 'max_score' => 50,
                'held_on' => '2026-09-22', 'created_by' => $cs->teacher_id,
            ]);
            foreach ($cs->roster() as $student) {
                $base = $ability[$student->id] ?? 55;
                $score = max(4, min(50, round(($base + mt_rand(-14, 14)) / 2)));
                $absent = mt_rand(1, 40) === 1;
                Mark::create([
                    'school_id' => $school->id, 'assessment_id' => $a->id, 'student_id' => $student->id,
                    'score' => $absent ? null : $score, 'absent' => $absent,
                    'entered_by' => $cs->teacher_id, 'entered_at' => Carbon::parse('2026-09-23 15:10'),
                ]);
            }
            if ($status !== 'DRAFT') {
                SubjectResultStatus::create([
                    'school_id' => $school->id, 'class_subject_id' => $cs->id, 'term_id' => $term->id, 'status' => $status,
                    'submitted_by' => $cs->teacher_id, 'submitted_at' => Carbon::parse('2026-09-23 16:00'),
                    'validated_by' => $status === 'VALIDATED' ? $validator?->id : null,
                    'validated_at' => $status === 'VALIDATED' ? Carbon::parse('2026-09-24 09:30') : null,
                ]);
            }
        }
    }

    protected function attendance(School $school, SchoolClass $class, array $students, ?User $by): void
    {
        $days = [];
        $d = Carbon::parse('2026-09-14');
        while ($d->lte(Carbon::parse('2026-09-24'))) {
            if ($d->isWeekday()) {
                $days[] = $d->toDateString();
            }
            $d->addDay();
        }
        foreach ($students as $s) {
            foreach ($days as $day) {
                $r = mt_rand(1, 100);
                Attendance::create([
                    'school_id' => $school->id, 'school_class_id' => $class->id, 'student_id' => $s->id,
                    'attended_on' => $day, 'status' => $r <= 88 ? 'PRESENT' : ($r <= 94 ? 'LATE' : 'ABSENT'), 'recorded_by' => $by?->id,
                ]);
            }
        }
    }

    protected function primarySchool(array $jur): School
    {
        $school = School::create([
            'code' => 'BC0412', 'name' => 'Chilomoni Primary School', 'type' => 'PRIMARY', 'category' => 'GOVERNMENT',
            'structure' => '8-4-4', 'status' => 'ACTIVE',
            'division_id' => $jur['divisions']['South West Education Division']->id,
            'district_id' => $jur['districts']['Blantyre City']->id, 'zone_id' => $jur['zones']['Chilomoni']->id,
            'maneb_centre_number' => '30412', 'postal_address' => 'P.O. Box 30412, Chilomoni, Blantyre 3',
            'phone' => '+265 1 870 412', 'email' => 'chilomoniprimary@education.gov.mw',
        ]);
        SchoolSetup::initialise($school, '2026/2027', SchoolSetup::defaultTerms(2026));
        $year = $school->currentYear();
        $term = $school->currentTerm();
        $domain = 'chilomoni.edu.mw';

        $n = 0;
        $head = $this->staff($school, $domain, 'Agnes Kachingwe', 'Female', 'FACILITY_ADMIN', 'DIPLOMA', 'Primary Education Management', $n++);
        $deputy = $this->staff($school, $domain, 'Patrick Nkhoma', 'Male', 'DEPUTY_HEAD', 'DIPLOMA', 'Mathematics', $n++);
        $infantHead = $this->staff($school, $domain, 'Ruth Mbewe', 'Female', 'SECTION_HEAD', 'T2', 'Early Grade Reading', $n++);
        $seniorHead = $this->staff($school, $domain, 'Joseph Chirwa', 'Male', 'SECTION_HEAD', 'DIPLOMA', 'Science and Health', $n++);
        $ct = [
            1 => $this->staff($school, $domain, 'Loveness Tembo', 'Female', 'CLASS_TEACHER', 'T2', 'Early Grade Reading', $n++),
            2 => $this->staff($school, $domain, 'Martha Kalua', 'Female', 'CLASS_TEACHER', 'T2', 'Chichewa', $n++),
            3 => $this->staff($school, $domain, 'Innocent Mwale', 'Male', 'CLASS_TEACHER', 'T2', 'Chichewa', $n++),
            4 => $this->staff($school, $domain, 'Memory Chimwaza', 'Female', 'CLASS_TEACHER', 'T2', 'Expressive Arts', $n++),
            5 => $this->staff($school, $domain, 'Emmanuel Gondwe', 'Male', 'CLASS_TEACHER', 'DIPLOMA', 'English', $n++),
            6 => $this->staff($school, $domain, 'Esther Nyirenda', 'Female', 'CLASS_TEACHER', 'T2', 'Social Studies', $n++),
            7 => $this->staff($school, $domain, 'Samuel Kumwenda', 'Male', 'CLASS_TEACHER', 'T2', 'Agriculture', $n++),
            8 => $this->staff($school, $domain, 'Tiwonge Msiska', 'Female', 'CLASS_TEACHER', 'DIPLOMA', 'Mathematics', $n++),
        ];
        $subjectTeacher = $this->staff($school, $domain, 'Yohane Banda', 'Male', 'SUBJECT_TEACHER', 'T3', 'Religious Education', $n++);
        $this->staff($school, $domain, 'Mercy Ngwira', 'Female', 'SUBJECT_TEACHER', 'T3', 'Expressive Arts', $n++);

        Section::where('school_id', $school->id)->where('name', 'Infant')->update(['head_id' => $infantHead->id]);
        Section::where('school_id', $school->id)->where('name', 'Junior')->update(['head_id' => $deputy->id]);
        Section::where('school_id', $school->id)->where('name', 'Senior')->update(['head_id' => $seniorHead->id]);

        $committees = Committee::where('school_id', $school->id)->get()->keyBy('name');
        $committees['Examinations']->members()->attach([$seniorHead->id => ['position' => 'Chairperson'], $ct[8]->id => ['position' => 'Secretary'], $ct[5]->id => ['position' => 'Member']]);
        $committees['Discipline']->members()->attach([$deputy->id => ['position' => 'Chairperson'], $ct[7]->id => ['position' => 'Member']]);
        $committees['Sports']->members()->attach([$ct[7]->id => ['position' => 'Chairperson'], $subjectTeacher->id => ['position' => 'Member']]);
        $committees['Timetable']->members()->attach([$deputy->id => ['position' => 'Chairperson'], $ct[6]->id => ['position' => 'Member']]);

        $sizes = [1 => 16, 2 => 15, 3 => 14, 4 => 14, 5 => 13, 6 => 13, 7 => 12, 8 => 12];
        $seq = 2601;
        $levels = Level::where('school_id', $school->id)->orderBy('ordinal')->get();
        $all = [];
        foreach ($levels as $level) {
            $class = $this->createClass($school, $year, $level, 'A', $ct[$level->ordinal], 60, 'Block '.chr(64 + (int) ceil($level->ordinal / 2)).' Room '.$level->ordinal);
            $students = $this->learners($school, $class, $sizes[$level->ordinal], 5 + $level->ordinal, $seq);
            $all[$level->ordinal] = [$class, $students];

            // Class teacher teaches most subjects in primary, specialists take RE and Expressive Arts in upper classes
            foreach ($class->classSubjects()->with('subject')->get() as $cs) {
                $teacher = $ct[$level->ordinal];
                if ($cs->subject->code === 'RE') {
                    $teacher = $subjectTeacher;
                } elseif ($level->ordinal >= 5 && $cs->subject->code === 'MAT' && $level->ordinal !== 8) {
                    $teacher = $ct[8];
                } elseif ($level->ordinal >= 6 && $cs->subject->code === 'SCH') {
                    $teacher = $seniorHead;
                }
                $cs->update(['teacher_id' => $teacher->id]);
            }
            $this->parents($school, $students, 2);
            $this->attendance($school, $class, $students, $ct[$level->ordinal]);
        }

        // Marks: upper classes recorded, Standard 5 fully validated and waiting for head release
        foreach ([5 => 'VALIDATED', 6 => 'SUBMITTED', 7 => 'SUBMITTED', 8 => 'DRAFT', 4 => 'DRAFT'] as $ord => $status) {
            [$class, $students] = $all[$ord];
            $ability = [];
            foreach ($students as $s) {
                $ability[$s->id] = mt_rand(30, 90);
            }
            $this->assessmentMarks($school, $term, $class, $status, $class->classTeacher, $ability);
        }
        ClassResultStatus::create([
            'school_id' => $school->id, 'school_class_id' => $all[5][0]->id, 'term_id' => $term->id, 'stage' => 'CLASS_REVIEWED',
            'class_reviewed_by' => $ct[5]->id, 'class_reviewed_at' => Carbon::parse('2026-09-24 10:15'),
        ]);

        // Give one parent a known login
        $parent = $all[5][1][0]->guardians()->first();
        if ($parent) {
            $parent->update(['email' => 'parent.chilomoni@gmail.com']);
        }

        $this->governance($school, [
            ['Charles Matola', 'Male', 'SMC', 'Chairperson', true],
            ['Eneless Chikoti', 'Female', 'SMC', 'Vice Chairperson', true],
            ['Davie Chilomoni', 'Male', 'SMC', 'Village Headman Representative', true],
            ['Dorothy Kaponda', 'Female', 'SMC', 'Treasurer', true],
            ['Ackim Namarika', 'Male', 'SMC', 'Member', true],
            ['Wisdom Luhanga', 'Male', 'PTA', 'Chairperson', true],
            ['Tereza Gama', 'Female', 'PTA', 'Secretary', true],
            ['Chrispin Mlenga', 'Male', 'PTA', 'Member', true],
        ], $domain);
        User::where('school_id', $school->id)->where('name', 'Charles Matola')->update(['email' => 'smc.chilomoni@gmail.com']);

        Timetable::generate($term);

        $smc = User::where('email', 'smc.chilomoni@gmail.com')->first();
        FeedbackItem::create([
            'school_id' => $school->id, 'user_id' => $smc->id, 'subject' => 'Standard 1 classroom overcrowding',
            'body' => 'Parents raised at the community meeting on 19 September that Standard 1 learners are sitting on the floor and some are learning under the mango tree. The SMC asks the school to present a request for desks and a temporary shelter.',
            'status' => 'IN_PROGRESS', 'response' => 'We have written to the DEM office for 40 desks and will discuss the shelter at the next SMC meeting.',
            'responded_by' => $head->id, 'responded_at' => Carbon::parse('2026-09-21 14:05'),
        ]);

        Notice::create(['user_id' => $head->id, 'school_id' => $school->id, 'category' => 'RESULTS', 'title' => 'Standard 5A is ready for release',
            'body' => 'Emmanuel Gondwe reviewed all subjects for Standard 5A. The results are waiting for your release.', 'link' => '/school/results', 'priority' => 'HIGH',
            'created_at' => now()->subHours(3)]);
        Notice::create(['user_id' => $head->id, 'school_id' => $school->id, 'category' => 'INSPECTION', 'title' => 'Inspection report received',
            'body' => 'Esnart Chunga (Primary Education Advisor) submitted the report of the visit on 17 Sep 2026.', 'priority' => 'NORMAL',
            'created_at' => now()->subDays(6)]);

        return $school;
    }

    protected function secondarySchool(array $jur): School
    {
        $school = School::create([
            'code' => 'BR2207', 'name' => 'Lunzu Community Day Secondary School', 'type' => 'SECONDARY', 'category' => 'CDSS',
            'structure' => '8-4-4', 'status' => 'ACTIVE',
            'division_id' => $jur['divisions']['South West Education Division']->id,
            'district_id' => $jur['districts']['Blantyre Rural']->id, 'zone_id' => $jur['zones']['Lunzu']->id,
            'maneb_centre_number' => '22070', 'postal_address' => 'P.O. Box 1207, Lunzu, Blantyre',
            'phone' => '+265 1 920 207', 'email' => 'lunzucdss@education.gov.mw',
        ]);
        SchoolSetup::initialise($school, '2026/2027', SchoolSetup::defaultTerms(2026));
        $year = $school->currentYear();
        $term = $school->currentTerm();
        $domain = 'lunzucdss.edu.mw';

        $n = 0;
        $head = $this->staff($school, $domain, 'Hastings Mkandawire', 'Male', 'FACILITY_ADMIN', 'MASTERS', 'Educational Leadership', $n++);
        $academic = $this->staff($school, $domain, 'Chimwemwe Phiri', 'Female', 'DEPUTY_HEAD_ACADEMIC', 'BACHELOR', 'Mathematics', $n++);
        $this->staff($school, $domain, 'Moses Chiwaya', 'Male', 'DEPUTY_HEAD_ADMIN', 'BACHELOR', 'Geography', $n++);
        $hodSci = $this->staff($school, $domain, 'Kondwani Jere', 'Male', 'HEAD_OF_DEPARTMENT', 'BACHELOR', 'Biology and Chemistry', $n++);
        $hodHum = $this->staff($school, $domain, 'Tamanda Moyo', 'Female', 'HEAD_OF_DEPARTMENT', 'BACHELOR', 'History', $n++);
        $hodLang = $this->staff($school, $domain, 'Lusungu Kamanga', 'Female', 'HEAD_OF_DEPARTMENT', 'BACHELOR', 'English', $n++);
        $hodMat = $this->staff($school, $domain, 'Daniel Lungu', 'Male', 'HEAD_OF_DEPARTMENT', 'BACHELOR', 'Mathematics', $n++);
        $hodTech = $this->staff($school, $domain, 'Frank Makwinja', 'Male', 'HEAD_OF_DEPARTMENT', 'DIPLOMA', 'Agriculture', $n++);
        $fm = [
            1 => $this->staff($school, $domain, 'Fanny Kanyama', 'Female', 'FORM_MASTER', 'DIPLOMA', 'Chichewa', $n++),
            2 => $this->staff($school, $domain, 'Isaac Saidi', 'Male', 'FORM_MASTER', 'BACHELOR', 'Physical Science', $n++),
            3 => $this->staff($school, $domain, 'Precious Mangani', 'Female', 'FORM_MASTER', 'BACHELOR', 'Biology', $n++),
            4 => $this->staff($school, $domain, 'Kelvin Chunga', 'Male', 'FORM_MASTER', 'BACHELOR', 'Social Studies', $n++),
        ];
        $t = [
            'ENG' => $hodLang, 'CHI' => $fm[1], 'MAT' => $hodMat, 'BIO' => $fm[3], 'PHS' => $fm[2], 'SOS' => $fm[4],
            'CHE' => $hodSci, 'PHY' => $fm[2], 'HIS' => $hodHum, 'GEO' => $this->staff($school, $domain, 'Steven Chinsinga', 'Male', 'SUBJECT_TEACHER', 'DIPLOMA', 'Geography', $n++),
            'BK' => $this->staff($school, $domain, 'Linda Bakali', 'Female', 'SUBJECT_TEACHER', 'DIPLOMA', 'Bible Knowledge', $n++),
            'AGR' => $hodTech, 'COM' => $this->staff($school, $domain, 'Wongani Mvula', 'Male', 'SUBJECT_TEACHER', 'BACHELOR', 'Computer Studies', $n++),
        ];
        $eng2 = $this->staff($school, $domain, 'Thandiwe Chimombo', 'Female', 'SUBJECT_TEACHER', 'DIPLOMA', 'English', $n++);

        $depts = Department::where('school_id', $school->id)->get()->keyBy('name');
        $depts['Sciences']->update(['head_id' => $hodSci->id]);
        $depts['Humanities']->update(['head_id' => $hodHum->id]);
        $depts['Languages']->update(['head_id' => $hodLang->id]);
        $depts['Mathematics']->update(['head_id' => $hodMat->id]);
        $depts['Practical and Technical']->update(['head_id' => $hodTech->id]);

        // Offer the subjects the school has teachers for
        $offered = array_keys($t);
        $seq = 1801;
        $levels = Level::where('school_id', $school->id)->orderBy('ordinal')->get();
        $sizes = [1 => 16, 2 => 15, 3 => 15, 4 => 14];
        $all = [];
        foreach ($levels as $level) {
            $class = $this->createClass($school, $year, $level, 'A', $fm[$level->ordinal], 55, 'Room '.$level->ordinal.'A');
            ClassSubject::where('school_class_id', $class->id)->whereHas('subject', fn ($q) => $q->whereNotIn('code', $offered))->delete();
            foreach ($class->classSubjects()->with('subject')->get() as $cs) {
                $teacher = $t[$cs->subject->code];
                if ($cs->subject->code === 'ENG' && $level->ordinal <= 2) {
                    $teacher = $eng2;
                }
                $cs->update(['teacher_id' => $teacher->id]);
            }
            $students = $this->learners($school, $class, $sizes[$level->ordinal], 13 + $level->ordinal, $seq);

            // Each learner takes three electives
            $electives = $class->classSubjects()->whereHas('subject', fn ($q) => $q->where('is_core', false))->get();
            foreach ($students as $i => $s) {
                $picks = $electives->shuffle()->take(3)->pluck('id')->all();
                $s->electives()->sync($picks);
                if ($level->national_exam) {
                    $s->update(['maneb_exam_number' => $school->maneb_centre_number.'/'.str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT)]);
                }
            }
            $all[$level->ordinal] = [$class, $students];
            $this->parents($school, $students, 3);
            $this->attendance($school, $class, $students, $fm[$level->ordinal]);
        }

        foreach ([4 => 'VALIDATED', 2 => 'SUBMITTED', 3 => 'DRAFT'] as $ord => $status) {
            [$class, $students] = $all[$ord];
            $ability = [];
            foreach ($students as $s) {
                $ability[$s->id] = mt_rand(28, 88);
            }
            $this->assessmentMarks($school, $term, $class, $status, $hodSci, $ability);
        }
        ClassResultStatus::create([
            'school_id' => $school->id, 'school_class_id' => $all[4][0]->id, 'term_id' => $term->id, 'stage' => 'CLASS_REVIEWED',
            'class_reviewed_by' => $fm[4]->id, 'class_reviewed_at' => Carbon::parse('2026-09-24 11:40'),
        ]);

        // Two Form 4 learners with portal access
        foreach (array_slice($all[4][1], 0, 2) as $i => $s) {
            $u = $this->person($s->fullName(), 'STUDENT', [
                'school_id' => $school->id, 'gender' => $s->gender, 'preferred_channel' => 'EMAIL',
                'email' => $i === 0 ? 'learner.lunzu@gmail.com' : strtolower($s->first_name.'.'.$s->last_name).'@gmail.com',
            ]);
            $s->update(['user_id' => $u->id]);
        }

        // Board of Governors: 13 members, 9 voting
        $this->governance($school, [
            ['Peter Chilima', 'Male', 'BOG', 'Chairperson', true],
            ['Maria Chunga', 'Female', 'BOG', 'Vice Chairperson', true],
            ['Harrison Ndalama', 'Male', 'BOG', 'Treasurer', true],
            ['Faith Nsona', 'Female', 'BOG', 'Member', true],
            ['Rev. Moses Kapito', 'Male', 'BOG', 'Church Representative', true],
            ['Sungeni Matola', 'Female', 'BOG', 'Member', true],
            ['Bester Kapeni', 'Male', 'BOG', 'Community Representative', true],
            ['Alinafe Chiwaya', 'Female', 'BOG', 'Parent Representative', true],
            ['Gift Mponda', 'Male', 'BOG', 'Former Student Representative', true],
            ['Rhoda Kapalamula', 'Female', 'BOG', 'Secretary', false],
            ['Towera Gondwe', 'Female', 'BOG', 'Staff Representative', false],
            ['Yamikani Kalua', 'Male', 'BOG', 'Student Council President', false],
            ['Wezi Luhanga', 'Female', 'BOG', 'DEM Office Observer', false],
        ], $domain);
        User::where('school_id', $school->id)->where('name', 'Peter Chilima')->update(['email' => 'bog.lunzu@gmail.com']);

        Timetable::generate($term);

        Notice::create(['user_id' => $academic->id, 'school_id' => $school->id, 'category' => 'RESULTS', 'title' => 'Form 4A reviewed by form master',
            'body' => 'Kelvin Chunga reviewed all subjects for Form 4A. Your approval is needed before the head can release.', 'link' => '/school/results', 'priority' => 'HIGH',
            'created_at' => now()->subHours(2)]);

        return $school;
    }

    protected function otherSchools(array $jur): void
    {
        $school = School::create([
            'code' => 'DW0931', 'name' => 'Mponela Primary School', 'type' => 'PRIMARY', 'category' => 'GOVERNMENT',
            'structure' => '8-4-4', 'status' => 'PENDING_ACTIVATION',
            'division_id' => $jur['divisions']['Central East Education Division']->id,
            'district_id' => $jur['districts']['Dowa']->id, 'zone_id' => $jur['zones']['Mponela']->id,
        ]);
        SchoolSetup::initialise($school, '2026/2027', SchoolSetup::defaultTerms(2026));
        User::create([
            'school_id' => $school->id, 'name' => 'Blessings Chimwaza', 'role' => 'FACILITY_ADMIN', 'status' => 'PENDING_ACTIVATION',
            'email' => 'head.mponela@education.gov.mw', 'phone' => $this->phone(), 'gender' => 'Male', 'preferred_channel' => 'SMS',
            'must_change_password' => true,
        ]);

        $school = School::create([
            'code' => 'ZC1104', 'name' => 'Zomba Urban Grant-Aided Secondary School', 'type' => 'SECONDARY', 'category' => 'GRANT_AIDED',
            'structure' => '8-4-4', 'status' => 'SUSPENDED',
            'division_id' => $jur['divisions']['South East Education Division']->id,
            'district_id' => $jur['districts']['Zomba City']->id, 'zone_id' => $jur['zones']['Sadzi']->id,
            'maneb_centre_number' => '41104',
        ]);
        SchoolSetup::initialise($school, '2026/2027', SchoolSetup::defaultTerms(2026));
        $this->person('Stella Kaunda', 'FACILITY_ADMIN', ['school_id' => $school->id, 'email' => 'head.zombaurban@education.gov.mw', 'gender' => 'Female']);
    }
}
