<?php
// Lethe - action route registration.
declare(strict_types=1);

use Lethe\Controllers\AuthController;
use Lethe\Controllers\DepositController;
use Lethe\Controllers\FileController;
use Lethe\Controllers\SecretController;
use Lethe\Controllers\UserController;
use Lethe\Router;

return function (Router $router): void {
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');

    $auth = new AuthController();
    $files = new FileController();
    $deposits = new DepositController();
    $secrets = new SecretController();
    $users = new UserController();

    // Session & authentication
    $router->map('get_session', fn() => $auth->getSession());
    $router->map('login', fn() => $auth->login());
    $router->map('logout', fn() => $auth->logout());
    $router->map('oidc_init', fn() => $auth->oidcInit());
    $router->map('oidc_callback', fn() => $auth->oidcCallback());

    // File sharing (authenticated)
    $router->map('upload_chunk', fn() => $files->uploadChunk());
    $router->map('finalize_upload', fn() => $files->finalizeUpload());
    $router->map('get_my_files', fn() => $files->getMyFiles());
    $router->map('revoke_file', fn() => $files->revokeFile());
    $router->map('delete_file', fn() => $files->deleteFile());

    // Deposits (authenticated)
    $router->map('get_my_deposits', fn() => $deposits->getMyDeposits());
    $router->map('create_deposit', fn() => $deposits->createDeposit());
    $router->map('toggle_deposit', fn() => $deposits->toggleDeposit());
    $router->map('delete_deposit_file', fn() => $deposits->deleteDepositFile());
    $router->map('download_deposit_file', fn() => $deposits->downloadDepositFile());

    // Deposits (guest, via deposit link)
    $router->map('deposit_upload_chunk', fn() => $deposits->guestUploadChunk());
    $router->map('deposit_finalize', fn() => $deposits->guestFinalize());

    // Secret messages (authenticated)
    $router->map('get_my_secrets', fn() => $secrets->getMySecrets());
    $router->map('create_secret', fn() => $secrets->createSecret());
    $router->map('delete_secret', fn() => $secrets->deleteSecret());

    // User management (admin only)
    $router->map('get_users', fn() => $users->getUsers());
    $router->map('create_user', fn() => $users->createUser());
    $router->map('delete_user', fn() => $users->deleteUser());
    $router->map('reset_user_password', fn() => $users->resetUserPassword());

    // Profile (authenticated)
    $router->map('get_profile', fn() => $users->getProfile());
    $router->map('update_profile', fn() => $users->updateProfile());
};
