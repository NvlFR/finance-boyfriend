<?php

use Symfony\Component\Process\Process;

test('deployment failures leave maintenance enabled without contacting production', function (string $failedStep) {
    $script = 'source '.escapeshellarg(base_path('deploy-production.sh'))."\n".<<<'BASH'
validate_environment() { :; }
read_local_version() { echo 1.1.8; }
read_remote_version() { echo 1.1.7; }
validate_release() { :; }
validate_git_state() { :; }
run_local_checks() { :; }
run_remote_preflight() { :; }
confirm_deployment() { :; }
acquire_deploy_lock() { :; }
enable_remote_maintenance() { echo TEST_MAINTENANCE; }
create_remote_backups() { echo TEST_BACKUP; }
sync_application_source() { echo TEST_SYNC; [[ "$FAILED_STEP" != sync ]]; }
upload_built_assets() { echo TEST_ASSETS; }
finalize_remote_deployment() { echo TEST_FINALIZE; [[ "$FAILED_STEP" != finalize ]]; }
run_health_checks() { echo TEST_HEALTH; [[ "$FAILED_STEP" != health ]]; }
ssh() {
    local payload
    payload="$(cat)"
    [[ "$payload" == *'php artisan down'* ]] && echo TEST_RESTORE_MAINTENANCE
    return 0
}
main
BASH;
    $process = new Process(['bash', '-c', $script], base_path(), ['FAILED_STEP' => $failedStep]);
    $process->run();

    expect($process->isSuccessful())->toBeFalse()
        ->and($process->getOutput())->toContain("TEST_MAINTENANCE\n", "TEST_BACKUP\n", "TEST_SYNC\n", 'TEST_RESTORE_MAINTENANCE')
        ->and(strpos($process->getOutput(), 'TEST_MAINTENANCE'))->toBeLessThan(strpos($process->getOutput(), 'TEST_BACKUP'))
        ->and(strpos($process->getOutput(), 'TEST_BACKUP'))->toBeLessThan(strpos($process->getOutput(), 'TEST_SYNC'))
        ->and($process->getOutput())->not->toContain('Deployment versi 1.1.8 berhasil.');
})->with(['sync', 'finalize', 'health']);
