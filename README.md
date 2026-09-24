# MoEST School Management System

A multi-tenant school management system for the Ministry of Education (MoEST), Malawi. It is built with Laravel 12, PHP, Blade and MySQL and runs on XAMPP.

Each school is a tenant, and its data is kept separate from every other school. The Ministry registers schools and activates head teacher accounts. Heads then set up staff, classes, learners, subjects and the timetable. District Education Managers (DEM), Education Division Managers (EDM) and Primary Education Advisors (PEA) get read-only dashboards covering the schools in their area.

## Features

- **Schools (tenants).** Primary, secondary or combined schools under the 8-4-4 or 1-6-6-3 structure. Categories are Government, Grant-Aided, CDSS and Private. Each school has a three-term calendar with mid-term breaks and a status of Active, Suspended or Pending Activation.
- **School System Administrator activation.** When the Ministry creates a school it also creates the school system administrator. This is either the head teacher acting as administrator or a separate officer such as an ICT or records officer. The account starts as pending. The administrator receives either a one-time code (valid for 15 minutes) or a temporary password (valid for 72 hours); only a hash of it is stored. On first sign-in the administrator must set a new password, can turn on two-step verification (optional), and must accept the MoEST ICT and child safeguarding policies. Code entry is locked after 5 wrong attempts. The school becomes Active when its administrator finishes activation.
- **Roles.**
  - School System Administrator: registers all staff, assigns class teachers and form masters, assigns subject teachers, and keeps the calendar, structure and finance records.
  - Primary: head, deputy, section head, class teacher and subject teacher.
  - Secondary: deputy head (academic), deputy head (administration), head of department and form master.
  - Also: learner, parent, SMC, PTA and Board of Governors.
- **Learner registration and Learner ID.**
  - A class teacher or form master registers learners into their own class. A subject teacher can register learners into any class they teach. No National ID is needed.
  - Every learner gets a national Learner ID (for example MW2600001081, with a check digit) and a QR code, printed on a learner card.
  - The profile holds date of birth, gender, physical address, guardian and emergency contact, and previous school history.
  - Transfers: type the Learner ID at the new school to bring the record across. The ID stays the same, the old record is marked Transferred, the old school is notified, and the history follows the learner from primary to secondary.
- **Access by class.** Class teachers and form masters see their own class only. Subject teachers see the classes they teach. Learners see their own record and parents see their linked children.
- **Teaching.**
  - Subjects follow the curriculum for each level, and Chichewa is the language of instruction for Standard 1 to 4.
  - Secondary learners choose electives.
  - Each class has one class teacher per year, and the teacher qualification rules are enforced.
  - The timetable builder detects clashes.
- **Marks and grading.**
  - Final mark is 40% continuous assessment plus 60% examination.
  - Primary grades run from 4 (Excellent) to 1 (Needs support).
  - Secondary uses MSCE grades 1 to 9. The MSCE rule is six credits including English, and the JCE rule is six passes including English.
- **Approval chain.**
  - Primary: subject teacher, then class teacher, then head teacher.
  - Secondary: subject teacher, then head of department, then form master, then deputy head (academic), then head teacher release.
  - Report cards can be printed.
- **MANEB.**
  - Candidate register for PSLCE, JCE and MSCE.
  - Centre and examination numbers.
  - Password-protected (AES encrypted) CSV export, recorded in the audit log.
- **Supervision.**
  - Dashboards with drill-down by division, district and zone.
  - Examination readiness for PSLCE, JCE and MSCE: candidates, how many meet the standard, and girls meeting it. This is worked out from school results; importing official MANEB results is not included.
  - Inspection reports are routed to the right directorate and can be flagged for a follow-up visit.
- **Finance.** The administrator records income (School Improvement Grant, ORT, fees, PTA, donations) and expenditure (learning materials, maintenance, examinations, utilities and others) for each term, with receipt or voucher numbers.
- **Governance.**
  - SMC, PTA and Board of Governors members see a read-only summary, examination readiness and the financial summary.
  - They can raise concerns with the head and escalate them to the DEM or EDM.
- **Notifications.**
  - An in-app notification bell.
  - SMS and email messages for results release, activation codes, inspections and escalations.
  - A termly SMS summary for SMC, PTA and Board members.
- **Security.**
  - Tenant isolation on every query.
  - Throttled sign-in and code entry.
  - A full audit log.
  - Learner personal details restricted by role.

## Install on XAMPP (Windows)

1. Install [XAMPP](https://www.apachefriends.org) with PHP 8.2 or newer, and install [Composer](https://getcomposer.org).
2. Open `C:\xampp\php\php.ini` and make sure these lines are not commented out (remove the leading `;`):
   ```
   extension=zip
   extension=pdo_mysql
   extension=mbstring
   extension=openssl
   extension=fileinfo
   ```
   The zip extension is needed for the encrypted MANEB export. Restart Apache after saving.
3. Start Apache and MySQL in the XAMPP Control Panel.
4. Open [phpMyAdmin](http://localhost/phpmyadmin) and create a database called `moest_sms` with collation `utf8mb4_unicode_ci`.
5. Copy the project folder to `C:\xampp\htdocs\moest-sms`, then open a terminal in that folder and run:
   ```
   composer install
   copy .env.example .env
   php artisan key:generate
   php artisan migrate --seed
   ```
   On macOS or Linux, use `cp .env.example .env` instead of `copy`.
6. Start the app:
   ```
   php artisan serve
   ```
   Then open http://127.0.0.1:8000. You can also browse to http://localhost/moest-sms/public through Apache.

The default database settings in `.env` are `DB_USERNAME=root` with an empty password, which matches a fresh XAMPP install.

## Demo accounts

Every demo account uses the password `Malawi@2026`.

| Role | Email |
| --- | --- |
| System administrator (MoEST HQ) | admin@education.gov.mw |
| Education Division Manager, South West | edm.southwest@education.gov.mw |
| District Education Manager, Blantyre City | dem.blantyrecity@education.gov.mw |
| Primary Education Advisor, Chilomoni zone | pea.chilomoni@education.gov.mw |
| Head teacher, Chilomoni Primary | agnes.kachingwe@chilomoni.edu.mw |
| Standard 5 class teacher, Chilomoni Primary | emmanuel.gondwe@chilomoni.edu.mw |
| Head teacher, Lunzu CDSS | hastings.mkandawire@lunzucdss.edu.mw |
| School System Administrator (ICT officer), Lunzu CDSS | thokozani.banda@lunzucdss.edu.mw |
| Subject teacher, Lunzu CDSS | steven.chinsinga@lunzucdss.edu.mw |
| Form 2 form master, Lunzu CDSS | isaac.saidi@lunzucdss.edu.mw |
| Deputy head (academic), Lunzu CDSS | chimwemwe.phiri@lunzucdss.edu.mw |
| Head of Sciences, Lunzu CDSS | kondwani.jere@lunzucdss.edu.mw |
| Form 4 form master, Lunzu CDSS | kelvin.chunga@lunzucdss.edu.mw |
| Parent, Chilomoni Primary | parent.chilomoni@gmail.com |
| Learner, Lunzu CDSS | learner.lunzu@gmail.com |
| SMC member, Chilomoni Primary | smc.chilomoni@gmail.com |
| Board of Governors member, Lunzu CDSS | bog.lunzu@gmail.com |

Mponela Primary is seeded with its head teacher account (head.mponela@education.gov.mw) still pending activation. Sign in as the administrator, open Mponela Primary and click Resend activation to try the activation flow.

## Not included yet

Health passport link with the Ministry of Health, analytics and predictions, an online payment gateway and biometric attendance are planned for later versions.

## Settings in .env

| Setting | Purpose |
| --- | --- |
| `SHOW_CODES_ON_SCREEN=true` | Shows activation and sign-in codes on screen so you can test without a phone. Set it to `false` on a live server. |
| `SMS_DRIVER=log` | Records SMS and email messages in the outbox (System administrator, SMS and email outbox) instead of sending them. |
| `SMS_DRIVER=africastalking` | Sends real SMS through Africa's Talking. Also set `SMS_USERNAME`, `SMS_API_KEY` and `SMS_SENDER_ID`. |
| `MAIL_MAILER` | Set to `smtp` with your mail server details to send real email. The default `log` writes emails to `storage/logs`. |

## Scheduled tasks

The scheduler checks every day at 07:00 and, one week before the current term ends, sends each SMC, PTA and Board of Governors member an SMS summary of enrolment, staffing, attendance, marks progress and pass rate. To run the scheduler while testing:

```
php artisan schedule:work
```

On a server, add one cron entry: `* * * * * php /path/to/moest-sms/artisan schedule:run`. On Windows, use Task Scheduler to run `php artisan schedule:run` every minute. To send the summaries now for every active school, run `php artisan sms:term-summaries --force`.

## Resetting the demo data

```
php artisan migrate:fresh --seed
```
