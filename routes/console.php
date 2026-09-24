<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('sms:term-summaries')->dailyAt('07:00');
