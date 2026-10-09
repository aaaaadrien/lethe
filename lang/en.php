<?php
// Lethe - English catalog (source of truth and fallback language).
// Flat "key => string" map. Placeholders use the ":name" syntax, substituted
// by Language::t(). Other languages (lang/fr.php) must define the same keys.
declare(strict_types=1);

return [
    // Brand
    'brand.tagline' => 'Sharing',

    // Sidebar / navigation (menus)
    'nav.dashboard' => 'Home',
    'nav.send_file' => 'Send a file',
    'nav.my_files' => 'My files',
    'nav.my_deposits' => 'My deposits',
    'nav.secret' => 'Secret message',
    'nav.my_secrets' => 'My secret messages',
    'nav.profile' => 'Profile',
    'nav.users' => 'Users',
    'nav.login' => 'Log in',
    'nav.logout' => 'Log out',

    // Login
    'login.title' => 'Log in',
    'login.submit' => 'Sign in',
    'login.oidc' => 'Sign in with OIDC',

    // Common labels, buttons and statuses
    'common.username' => 'Username',
    'common.password' => 'Password',
    'common.confirm' => 'Confirm',
    'common.cancel' => 'Cancel',
    'common.delete' => 'Delete',
    'common.copy' => 'Copy',
    'common.copied' => 'Copied!',
    'common.failed' => 'Failed',
    'common.create' => 'Create',
    'common.download' => 'Download',
    'common.unlock' => 'Unlock',
    'common.actions' => 'Actions',
    'common.status' => 'Status',
    'common.name' => 'Name',
    'common.size' => 'Size',
    'common.expiration' => 'Expiration',
    'common.downloads' => 'Downloads',
    'common.role' => 'Role',
    'common.created' => 'Created on',
    'common.consumed_at' => 'Viewed on',
    'common.depositor' => 'Depositor',
    'common.received_at' => 'Received on',
    'common.you' => 'You',
    'common.admin' => 'Admin',
    'common.user' => 'User',

    'status.active' => 'Active',
    'status.expired' => 'Expired',
    'status.revoked' => 'Revoked',
    'status.protected' => 'Protected',
    'status.disabled' => 'Disabled',
    'status.consumed' => 'Viewed',

    // Shared drop zone
    'dropzone.text' => 'Drag and drop a file here, or click to select it',

    // Dashboard
    'dashboard.welcome' => 'Welcome, :name',
    'dashboard.subtitle' => 'What would you like to do?',
    'dashboard.tile_send_file' => 'Share a large file through a download link, with an optional password and expiration.',
    'dashboard.tile_my_files' => 'Find the files you already sent: link, status, revoke or delete.',
    'dashboard.tile_my_deposits' => 'Create a deposit link to receive files from a guest, without them needing an account.',
    'dashboard.tile_secret' => 'Share a confidential text through an encrypted link, viewable only once.',
    'dashboard.tile_my_secrets' => 'Track the status of the secret messages you sent (active, viewed, expired).',
    'dashboard.tile_users' => "Manage the application's user accounts.",

    // Logged-out landing screen
    'loggedout.welcome' => 'Welcome to :app',
    'loggedout.subtitle' => 'Sign in to send files, create deposit links and share secret messages.',

    // Send a file (SPA)
    'sendfile.max_size' => 'Maximum size: :size',
    'sendfile.password' => 'Password (optional)',
    'sendfile.password_placeholder' => 'Leave empty = no protection',
    'sendfile.expiration' => 'Expiration (days)',
    'sendfile.expiration_hint' => 'Between :min and :max days.',
    'sendfile.share_mode' => 'Sharing method',
    'sendfile.method_link' => 'Generate a link',
    'sendfile.method_email' => 'Send by e-mail',
    'sendfile.recipient_email' => 'Recipient e-mail',
    'sendfile.custom_message' => 'Custom message (optional)',
    'sendfile.custom_message_placeholder' => 'Leave empty to use the default message. Placeholders: {LINK} {EXPIRATION} {PASSWORD_NOTICE} {SENDER}',
    'sendfile.submit' => 'Send',
    'sendfile.file_selected' => 'File selected: :name (:size)',
    'sendfile.success' => 'File sent successfully!',
    'sendfile.success_note' => 'Pass this link to the recipient. It will expire automatically.',
    'sendfile.success_email' => 'File sent by e-mail',
    'sendfile.success_email_note' => 'The download link has been sent to the recipient. You can also find it in My files.',

    // My files (SPA)
    'myfiles.empty' => 'No files sent yet.',

    // My deposits (SPA)
    'mydeposits.create_title' => 'Create a new deposit link',
    'mydeposits.label' => 'Name / label',
    'mydeposits.label_placeholder' => 'E.g. Deposit for client Dupont',
    'mydeposits.expiration' => 'Link expiration (days)',
    'mydeposits.empty' => 'No deposits created yet.',
    'mydeposits.expires_on' => 'Expires on :date',
    'mydeposits.files_received' => 'Received files (:count)',
    'mydeposits.no_files' => 'No files received yet.',

    // Secret message (SPA)
    'secret.title' => 'Share a secret message',
    'secret.description' => 'The message is encrypted in the database and can be viewed only once through the generated link: as soon as it is opened and revealed, it is permanently deleted, even if the link has not expired yet.',
    'secret.message' => 'Message',
    'secret.message_placeholder' => 'Type the text to share here (password, key, confidential information...)',
    'secret.max_chars' => ':count characters maximum.',
    'secret.validity' => 'Validity period',
    'secret.day_one' => '1 day',
    'secret.day_many' => ':days days',
    'secret.validity_hint' => 'The link expires automatically after this period, if it has not been viewed yet.',
    'secret.generate' => 'Generate the link',
    'secret.result_note' => 'Pass this link through a trusted channel: it will not be displayed anywhere else and will no longer be viewable once it has been opened and revealed by the recipient.',

    // My secret messages (SPA)
    'mysecrets.empty' => 'No secret messages created yet.',

    // Users (SPA, admin)
    'users.create_title' => 'Create a user',
    'users.username_col' => 'User',
    'users.admin' => 'Administrator',
    'users.new_password' => 'New password',
    'users.reset' => 'Reset',
    'users.oidc_account' => 'OIDC account',

    // Profile (SPA)
    'profile.title' => 'Your profile',
    'profile.description' => 'Set your e-mail address. It is used to notify you when someone uploads a file to one of your deposit links.',
    'profile.email_label' => 'E-mail address',
    'profile.email_placeholder' => 'name@example.com',
    'profile.save' => 'Save',
    'profile.oidc_info' => 'Your e-mail is automatically synced from your OIDC provider.',

    // Icon button tooltips
    'action.copy_link' => 'Copy link',
    'action.revoke_link' => 'Revoke link',
    'action.disable' => 'Disable',
    'action.enable' => 'Enable',

    // Confirmation dialogs
    'confirm.revoke.title' => 'Revoke the link',
    'confirm.revoke.message' => 'The recipient will no longer be able to download this file. This action is irreversible.',
    'confirm.revoke.label' => 'Revoke',
    'confirm.delete_file.title' => 'Delete the file',
    'confirm.delete_file.message' => 'The file and its link will be permanently deleted from the server.',
    'confirm.delete_deposit_file.title' => 'Delete the received file',
    'confirm.delete_deposit_file.message' => 'This file will be permanently deleted from the server.',
    'confirm.delete_secret.title' => 'Delete the secret message',
    'confirm.delete_secret.message' => 'The link will become unusable immediately, even if the message has not been viewed yet.',
    'confirm.delete_user.title' => 'Delete the user',
    'confirm.delete_user.message' => 'The account will be permanently deleted.',

    // Errors (client-side and public pages)
    'error.file_too_large' => 'The file exceeds the maximum allowed size.',
    'error.finalize' => 'Error while finalizing the upload.',
    'error.chunk_send_failed' => "Error while sending the chunk.",
    'error.server_status' => 'Server error (:status)',
    'error.server' => 'Server error.',
    'error.page_title' => 'An error occurred',
    'error.page_hint' => 'See data/php-error.log for details.',
    'error.tmp_not_writable' => 'The temporary storage folder is not writable. Check the permissions (chmod/chown) on uploads/tmp.',
    'error.chunk_save_failed' => 'Unable to save chunk :index.',
    'error.chunk_invalid' => 'Chunk :index is invalid or too large (check upload_max_filesize/post_max_size in your PHP configuration).',
    'error.final_file_failed' => 'Unable to create the final file.',
    'error.link_not_found' => 'This link does not exist or has been deleted.',
    'error.secret_not_found' => 'This link does not exist, or the message has already been viewed and deleted.',
    'error.link_revoked' => 'This link has been revoked by its owner.',
    'error.link_expired' => 'This link has expired.',
    'error.file_unavailable' => 'The file is no longer available on the server.',
    'error.password_incorrect' => 'Incorrect password.',
    'error.password_required' => 'Password required.',
    'error.session_expired' => 'Session expired, please reload the page and try again.',
    'error.secret_consumed' => 'This message has already been viewed. For privacy reasons, it can only be read once and has been deleted.',
    'error.secret_just_consumed' => 'This message was just viewed (perhaps from another tab) and is no longer available.',
    'error.deposit_not_found' => 'Deposit link not found.',
    'error.deposit_disabled' => 'This deposit link has been disabled.',
    'error.deposit_expired' => 'This deposit link has expired.',

    // Public download page
    'public.download.title' => 'Download - :app',
    'public.download.unavailable' => 'Download unavailable',
    'public.download.protected' => 'Protected file',
    'public.download.ready' => 'File ready to download',
    'public.download.meta' => ':size · expires on :date',

    // Public deposit page
    'public.deposit.title' => 'File deposit - :app',
    'public.deposit.unavailable' => 'Deposit unavailable',
    'public.deposit.protected' => 'Protected deposit: :label',
    'public.deposit.upload_title' => 'Upload a file — :label',
    'public.deposit.info' => 'Maximum size: :size. This deposit link expires on :date.',
    'public.deposit.your_name' => 'Your name (optional)',
    'public.deposit.name_placeholder' => 'To identify yourself to the recipient',
    'public.deposit.submit' => 'Send the file',
    'public.deposit.success' => 'File sent successfully!',

    // Public secret message page
    'public.secret.title' => 'Secret message - :app',
    'public.secret.unavailable' => 'Message unavailable',
    'public.secret.received' => 'A secret message has been shared with you',
    'public.secret.received_note' => 'This message can be viewed only once. Once revealed, it will be permanently deleted from the server.',
    'public.secret.reveal' => 'Reveal the message',
    'public.secret.revealed' => 'Here is the message',
    'public.secret.deleted_note' => 'This message has now been deleted from the server: keep it safely if you need it, it will no longer be viewable through this link.',

    // API messages (JSON responses shown in toasts / alerts)
    'api.invalid_request' => 'Invalid request.',
    'api.max_size_exceeded' => 'Maximum size exceeded.',
    'api.expiration_range' => 'The expiration must be between :min and :max days.',
    'api.invalid_email' => 'The recipient e-mail address is invalid.',
    'api.email_failed' => 'The file was saved but the e-mail could not be sent. The link remains available in My files.',
    'api.file_not_found' => 'File not found.',
    'api.file_not_found_or_revoked' => 'File not found or already revoked.',
    'api.link_revoked_msg' => 'The link has been revoked.',
    'api.file_deleted' => 'The file has been deleted.',
    'api.deposit_default_label' => 'Untitled deposit',
    'api.deposit_created' => 'The deposit has been created.',
    'api.deposit_not_found' => 'Deposit not found.',
    'api.deposit_status_updated' => 'Deposit status updated.',
    'api.deposit_file_deleted' => 'Received file deleted.',
    'api.deposit_link_not_found' => 'Deposit link not found.',
    'api.file_sent' => 'File sent successfully.',
    'api.message_empty' => 'The message cannot be empty.',
    'api.message_too_long' => 'The message exceeds the maximum allowed length (:count characters).',
    'api.invalid_validity' => 'Invalid validity period.',
    'api.secret_created' => 'Secret message created.',
    'api.secret_not_found' => 'Message not found.',
    'api.secret_deleted' => 'The secret message has been deleted.',
    'api.user_credentials_required' => 'Username and password (min. 4 characters) required.',
    'api.user_created' => 'User created.',
    'api.user_exists' => 'This username already exists.',
    'api.cannot_delete_self' => 'You cannot delete your own account.',
    'api.user_not_found' => 'User not found.',
    'api.user_deleted' => 'User deleted.',
    'api.password_too_short' => 'The password must contain at least 4 characters.',
    'api.password_reset' => 'Password reset.',
    'api.profile_updated' => 'Profile updated.',
    'api.login_required' => 'Not authenticated.',
    'api.admin_only' => 'Access restricted to administrators.',
    'api.unknown_action' => 'Unknown action.',
    'api.too_many_attempts' => 'Too many attempts. Try again in :seconds second(s).',
    'api.account_locked' => 'Too many attempts. Account locked for :seconds seconds.',
    'api.bad_credentials' => 'Incorrect credentials.',
    'api.credentials_required' => 'Credentials required.',
    'api.csrf_invalid' => 'Invalid or expired CSRF token.',

    // Helpers
    'helpers.default_filename' => 'file',

    // E-mail (default subject/body, in the sender's language; overridable
    // in config.php via MAIL_SUBJECT / MAIL_BODY_TEMPLATE)
    'mail.subject' => 'A file has been shared with you',
    'mail.body' => "Hello,\n\n{SENDER} has shared a file with you via Lethe.\n\nDownload link:\n{LINK}\n\nThis link will expire on {EXPIRATION}.\n{PASSWORD_NOTICE}\n\nBest regards.",
    'mail.password_notice' => 'This file is protected by a password that has been communicated to you separately.',

    // Deposit notification (sent to the deposit owner when a file is uploaded)
    'mail.deposit.subject' => 'A file has been deposited on your link: :label',
    'mail.deposit.body' => "Hello,\n\n{UPLOADER_NAME} has uploaded a file to your deposit link \"{DEPOSIT_LABEL}\".\n\nDeposit link:\n{DEPOSIT_LINK}\n\nBest regards.",
    'mail.deposit.anonymous' => 'An anonymous user',
];
