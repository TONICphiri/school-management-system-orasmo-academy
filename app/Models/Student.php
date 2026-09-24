<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Student extends Model
{
    use BelongsToSchool;
    protected $guarded = [];
    protected $casts = ["date_of_birth" => "date", "admitted_on" => "date"];

    protected static function booted(): void
    {
        // Every learner gets a national Learner ID the first time they are registered anywhere.
        // A learner who moves school keeps the same ID, so it is only generated when none was carried over.
        static::created(function (Student $student) {
            if (! $student->learner_uid) {
                $student->learner_uid = self::makeLearnerUid($student->id);
                $student->saveQuietly();
            }
        });
    }

    /**
     * Learner ID format: MW + two digit year of first registration + seven digit serial + check digit.
     * Example MW2600001237. The check digit (Luhn) catches most typing mistakes when an ID is keyed in.
     */
    public static function makeLearnerUid(int $serial, ?int $year = null): string
    {
        $body = sprintf('%02d%07d', ($year ?? (int) now()->format('y')) % 100, $serial);
        $sum = 0;
        foreach (array_reverse(str_split($body)) as $i => $d) {
            $n = (int) $d * ($i % 2 === 0 ? 2 : 1);
            $sum += $n > 9 ? $n - 9 : $n;
        }

        return 'MW'.$body.((10 - $sum % 10) % 10);
    }

    public static function validLearnerUid(string $uid): bool
    {
        if (! preg_match('/^MW(\d{2})(\d{7})(\d)$/', $uid, $m)) {
            return false;
        }

        return self::makeLearnerUid((int) $m[2], (int) $m[1]) === $uid;
    }

    public function user() { return $this->belongsTo(User::class); }
    public function schoolClass() { return $this->belongsTo(SchoolClass::class); }
    public function guardians() { return $this->belongsToMany(User::class, "guardian_student")->withPivot("relationship"); }
    public function electives() { return $this->belongsToMany(ClassSubject::class, "student_subjects"); }
    public function marks() { return $this->hasMany(Mark::class); }
    public function attendances() { return $this->hasMany(Attendance::class); }
    public function histories() { return $this->hasMany(LearnerSchoolHistory::class)->latest('year_left'); }
    public function registeredBy() { return $this->belongsTo(User::class, 'registered_by'); }

    /** Every enrolment this learner has had in any school, oldest first. Used for the learner passport. */
    public function passportRecords()
    {
        return self::withoutGlobalScopes()->with(['school', 'schoolClass' => fn ($q) => $q->withoutGlobalScopes()->with(['level' => fn ($l) => $l->withoutGlobalScopes()])])
            ->where('learner_uid', $this->learner_uid)->where('id', '!=', $this->id)->orderBy('admitted_on')->orderBy('id')->get();
    }

    public function fullName(): string
    {
        return $this->first_name." ".$this->last_name;
    }

    public function qrSvg(int $size = 160): string
    {
        $payload = 'MoEST Learner ID '.$this->learner_uid;
        $writer = new Writer(new ImageRenderer(new RendererStyle($size, 1), new SvgImageBackEnd()));
        $svg = $writer->writeString($payload);

        return preg_replace('/^<\?xml[^>]*>\s*/', '', $svg);
    }
}
