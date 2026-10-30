<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ProductionCheckCommand extends Command
{
    protected $signature = 'app:production-check';

    protected $description = 'Verify the application is properly configured for production';

    public function handle(): int
    {
        $this->info('🔍 Running WadahNgopi Production Readiness Check...');
        $this->newLine();

        $passed = 0;
        $failed = 0;
        $warnings = 0;

        // --- Critical Checks ---
        $this->components->info('Critical Configuration');

        if (config('app.debug') === false) {
            $this->checkPass('APP_DEBUG is disabled');
            $passed++;
        } else {
            $this->checkFail('APP_DEBUG is enabled — MUST be false in production');
            $failed++;
        }

        if (config('app.env') === 'production') {
            $this->checkPass('APP_ENV is production');
            $passed++;
        } else {
            $this->checkWarn('APP_ENV is "' . config('app.env') . '" — should be "production"');
            $warnings++;
        }

        if (!empty(config('app.key'))) {
            $this->checkPass('APP_KEY is set');
            $passed++;
        } else {
            $this->checkFail('APP_KEY is not set');
            $failed++;
        }

        // --- Performance Checks ---
        $this->newLine();
        $this->components->info('Performance Configuration');

        $cacheDriver = config('cache.default');
        if (in_array($cacheDriver, ['redis', 'memcached'])) {
            $this->checkPass("Cache driver: {$cacheDriver} (optimal)");
            $passed++;
        } elseif ($cacheDriver === 'database') {
            $this->checkWarn('Cache driver: database — consider Redis for better performance');
            $warnings++;
        } else {
            $this->checkWarn("Cache driver: {$cacheDriver} — consider Redis");
            $warnings++;
        }

        $sessionDriver = config('session.driver');
        if (in_array($sessionDriver, ['redis', 'memcached', 'database'])) {
            $this->checkPass("Session driver: {$sessionDriver}");
            $passed++;
        } else {
            $this->checkWarn("Session driver: {$sessionDriver} — consider redis or database");
            $warnings++;
        }

        $queueDriver = config('queue.default');
        if ($queueDriver !== 'sync') {
            $this->checkPass("Queue driver: {$queueDriver}");
            $passed++;
        } else {
            $this->checkWarn('Queue driver is sync — jobs will block requests');
            $warnings++;
        }

        // --- Security Checks ---
        $this->newLine();
        $this->components->info('Security Configuration');

        if (config('session.secure')) {
            $this->checkPass('Secure cookies enabled (SESSION_SECURE_COOKIE)');
            $passed++;
        } else {
            $this->checkFail('Secure cookies disabled — MUST be true for HTTPS in production');
            $failed++;
        }

        if (config('session.encrypt')) {
            $this->checkPass('Session encryption enabled');
            $passed++;
        } else {
            $this->checkWarn('Session encryption disabled');
            $warnings++;
        }

        // Database Password Check
        $dbPassword = config('database.connections.' . config('database.default') . '.password');
        if (!empty($dbPassword)) {
            $this->checkPass('Database password is set');
            $passed++;
        } else {
            $this->checkFail('Database password is EMPTY — MUST be set in production');
            $failed++;
        }

        // Directory Permissions (Storage & Cache)
        $paths = [storage_path(), base_path('bootstrap/cache')];
        foreach ($paths as $path) {
            if (is_writable($path)) {
                $this->checkPass("Directory is writable: " . basename($path));
                $passed++;
            } else {
                $this->checkFail("Directory is NOT writable: " . basename($path));
                $failed++;
            }
        }

        // Composer Audit Check (Security)
        if (file_exists(base_path('composer.lock'))) {
            $this->components->task('Checking composer dependencies for vulnerabilities...', function () use (&$passed, &$failed) {
                exec('composer audit --format=json 2>&1', $output, $resultCode);
                if ($resultCode === 0) {
                    return true;
                }
                return false;
            });

            $this->checkWarn('Ensure you run "composer audit" regularly to check for vulnerable dependencies.');
            $warnings++;
        }

        $logLevel = config('logging.channels.stack.level', config('logging.level', 'debug'));
        if (in_array($logLevel, ['warning', 'error', 'critical'])) {
            $this->checkPass("Log level: {$logLevel}");
            $passed++;
        } else {
            $this->checkWarn("Log level: {$logLevel} — consider 'warning' for production");
            $warnings++;
        }

        // --- Summary ---
        $this->newLine();
        $this->components->info('Results');
        $this->line("  ✅ Passed:   {$passed}");
        $this->line("  ⚠️  Warnings: {$warnings}");
        $this->line("  ❌ Failed:   {$failed}");
        $this->newLine();

        if ($failed > 0) {
            $this->components->error('Production check FAILED — fix critical issues before deploying.');

            return self::FAILURE;
        }

        if ($warnings > 0) {
            $this->components->warn('Production check passed with warnings.');

            return self::SUCCESS;
        }

        $this->components->info('🎉 All checks passed! Ready for production.');

        return self::SUCCESS;
    }

    private function checkPass(string $message): void
    {
        $this->line("  <fg=green>✓</> {$message}");
    }

    private function checkFail(string $message): void
    {
        $this->line("  <fg=red>✗</> {$message}");
    }

    private function checkWarn(string $message): void
    {
        $this->line("  <fg=yellow>⚠</> {$message}");
    }
}
