<?php

namespace App\Services;

use App\Models\AllowedStudent;
use App\Models\Grade;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;
use SplFileObject;

/**
 * Imports the student whitelist from a CSV with the header "email,grade,letter".
 * Comma and semicolon delimiters are both accepted (Excel in Bulgaria exports with ";").
 */
class StudentImportService
{
    private const HEADER = ['email', 'grade', 'letter'];

    /**
     * @return array{created: list<array{row: int, email: string}>, skipped: list<array{row: int, email: string, reason: string}>, invalid: list<array{row: int, email: string, reason: string}>}
     *
     * @throws InvalidArgumentException when the file is unreadable or the header is wrong
     */
    public function import(string $path): array
    {
        $rows = $this->readRows($path);

        $report = ['created' => [], 'skipped' => [], 'invalid' => []];

        $classes = SchoolClass::query()->get()->keyBy(fn (SchoolClass $class) => $class->name);
        $emails = array_values(array_filter(array_map(fn ($r) => $r['email'], $rows)));
        $whitelisted = AllowedStudent::whereIn('email', $emails)->pluck('email')->flip();
        $registered = User::whereIn('email', $emails)->pluck('email')->flip();
        $seen = [];

        DB::transaction(function () use ($rows, $classes, $whitelisted, $registered, &$seen, &$report) {
            foreach ($rows as $row) {
                $line = $row['line'];
                $email = $row['email'];

                if ($row['columns'] !== 3) {
                    $report['invalid'][] = $this->entry($line, $email, __('ui.import.wrong_columns'));

                    continue;
                }

                if (Validator::make(['email' => $email], ['email' => 'required|email:rfc|max:255'])->fails()) {
                    $report['invalid'][] = $this->entry($line, $email, __('ui.import.invalid_email'));

                    continue;
                }

                if (! preg_match('/^\d{1,2}$/', $row['grade']) || (int) $row['grade'] < Grade::MIN || (int) $row['grade'] > Grade::MAX) {
                    $report['invalid'][] = $this->entry($line, $email, __('ui.import.invalid_grade'));

                    continue;
                }

                if (! preg_match('/^\p{Cyrillic}$/u', $row['letter'])) {
                    $report['invalid'][] = $this->entry($line, $email, __('ui.import.invalid_letter'));

                    continue;
                }

                $className = (int) $row['grade'].$row['letter'];
                $class = $classes->get($className);

                if (! $class) {
                    $report['invalid'][] = $this->entry($line, $email, __('ui.import.unknown_class', ['class' => $className]));

                    continue;
                }

                if (isset($seen[$email])) {
                    $report['skipped'][] = $this->entry($line, $email, __('ui.import.duplicate_in_file'));

                    continue;
                }
                $seen[$email] = true;

                if (isset($whitelisted[$email])) {
                    $report['skipped'][] = $this->entry($line, $email, __('ui.import.already_whitelisted'));

                    continue;
                }

                if (isset($registered[$email])) {
                    $report['skipped'][] = $this->entry($line, $email, __('ui.import.already_registered'));

                    continue;
                }

                AllowedStudent::create(['email' => $email, 'class_id' => $class->id]);
                $report['created'][] = ['row' => $line, 'email' => $email];
            }
        });

        return $report;
    }

    /**
     * @return list<array{line: int, columns: int, email: string, grade: string, letter: string}>
     */
    private function readRows(string $path): array
    {
        try {
            $file = new SplFileObject($path, 'r');
        } catch (\RuntimeException) {
            throw new InvalidArgumentException(__('ui.import.unreadable'));
        }

        $headerLine = $file->fgets();
        if ($headerLine === false || ! mb_check_encoding($headerLine, 'UTF-8')) {
            throw new InvalidArgumentException(__('ui.import.unreadable'));
        }

        $headerLine = preg_replace('/^\xEF\xBB\xBF/', '', $headerLine);
        $delimiter = substr_count($headerLine, ';') > substr_count($headerLine, ',') ? ';' : ',';
        $header = array_map(fn ($v) => mb_strtolower(trim($v)), str_getcsv($headerLine, $delimiter, '"', ''));

        if ($header !== self::HEADER) {
            throw new InvalidArgumentException(__('ui.import.bad_header'));
        }

        $rows = [];
        $line = 1;

        while (! $file->eof()) {
            $raw = $file->fgets();
            $line++;

            if ($raw === false || trim($raw) === '') {
                continue;
            }

            if (! mb_check_encoding($raw, 'UTF-8')) {
                throw new InvalidArgumentException(__('ui.import.unreadable'));
            }

            $values = array_map('trim', str_getcsv(rtrim($raw, "\r\n"), $delimiter, '"', ''));

            $rows[] = [
                'line' => $line,
                'columns' => count($values),
                'email' => mb_strtolower($values[0] ?? ''),
                'grade' => $values[1] ?? '',
                'letter' => mb_strtoupper($values[2] ?? ''),
            ];
        }

        if ($rows === []) {
            throw new InvalidArgumentException(__('ui.import.empty'));
        }

        return $rows;
    }

    /**
     * @return array{row: int, email: string, reason: string}
     */
    private function entry(int $line, string $email, string $reason): array
    {
        return ['row' => $line, 'email' => $email, 'reason' => $reason];
    }
}
