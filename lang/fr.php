<?php
// Lethe - French catalog.
// Same keys as lang/en.php (the source of truth). Any key missing here
// falls back to the English translation.
declare(strict_types=1);

return [
    // Marque
    'brand.tagline' => 'Partage',

    // Barre latérale / navigation (menus)
    'nav.dashboard' => 'Accueil',
    'nav.send_file' => 'Envoyer un fichier',
    'nav.my_files' => 'Mes fichiers',
    'nav.my_deposits' => 'Mes dépôts',
    'nav.secret' => 'Message secret',
    'nav.my_secrets' => 'Mes messages secrets',
    'nav.profile' => 'Profil',
    'nav.users' => 'Utilisateurs',
    'nav.login' => 'Connexion',
    'nav.logout' => 'Déconnexion',

    // Connexion
    'login.title' => 'Connexion',
    'login.submit' => 'Se connecter',
    'login.oidc' => 'Se connecter avec OIDC',

    // Libellés, boutons et statuts communs
    'common.username' => "Nom d'utilisateur",
    'common.password' => 'Mot de passe',
    'common.confirm' => 'Confirmer',
    'common.cancel' => 'Annuler',
    'common.delete' => 'Supprimer',
    'common.copy' => 'Copier',
    'common.copied' => 'Copié !',
    'common.failed' => 'Échec',
    'common.create' => 'Créer',
    'common.download' => 'Télécharger',
    'common.unlock' => 'Déverrouiller',
    'common.actions' => 'Actions',
    'common.status' => 'Statut',
    'common.name' => 'Nom',
    'common.size' => 'Taille',
    'common.expiration' => 'Expiration',
    'common.downloads' => 'Téléchargements',
    'common.role' => 'Rôle',
    'common.created' => 'Créé le',
    'common.consumed_at' => 'Consulté le',
    'common.depositor' => 'Déposant',
    'common.received_at' => 'Reçu le',
    'common.you' => 'Vous',
    'common.admin' => 'Admin',
    'common.user' => 'Utilisateur',

    'status.active' => 'Actif',
    'status.expired' => 'Expiré',
    'status.revoked' => 'Révoqué',
    'status.protected' => 'Protégé',
    'status.disabled' => 'Désactivé',
    'status.consumed' => 'Consulté',

    // Zone de dépôt de fichier partagée
    'dropzone.text' => 'Glissez-déposez un fichier ici, ou cliquez pour le sélectionner',

    // Tableau de bord
    'dashboard.welcome' => 'Bienvenue, :name',
    'dashboard.subtitle' => 'Que souhaitez-vous faire ?',
    'dashboard.tile_send_file' => 'Partager un fichier volumineux via un lien de téléchargement, avec mot de passe optionnel et expiration.',
    'dashboard.tile_my_files' => 'Retrouver les fichiers déjà envoyés : lien, statut, révocation ou suppression.',
    'dashboard.tile_my_deposits' => "Créer un lien de dépôt pour recevoir des fichiers d'un invité, sans qu'il ait besoin de compte.",
    'dashboard.tile_secret' => 'Partager un texte confidentiel via un lien chiffré, consultable une seule fois.',
    'dashboard.tile_my_secrets' => 'Suivre le statut des messages secrets envoyés (actif, consulté, expiré).',
    'dashboard.tile_users' => "Gérer les comptes utilisateurs de l'application.",

    // Écran d'accueil non connecté
    'loggedout.welcome' => 'Bienvenue sur :app',
    'loggedout.subtitle' => 'Connectez-vous pour envoyer des fichiers, créer des liens de dépôt et partager des messages secrets.',

    // Envoyer un fichier (SPA)
    'sendfile.max_size' => 'Taille maximale : :size',
    'sendfile.password' => 'Mot de passe (optionnel)',
    'sendfile.password_placeholder' => 'Laisser vide = pas de protection',
    'sendfile.expiration' => 'Expiration (jours)',
    'sendfile.expiration_hint' => 'Entre :min et :max jours.',
    'sendfile.share_mode' => 'Mode de partage',
    'sendfile.method_link' => 'Générer un lien',
    'sendfile.method_email' => 'Envoyer par e-mail',
    'sendfile.recipient_email' => 'E-mail du destinataire',
    'sendfile.custom_message' => 'Message personnalisé (optionnel)',
    'sendfile.custom_message_placeholder' => 'Laisser vide pour utiliser le message par défaut. Placeholders : {LINK} {EXPIRATION} {PASSWORD_NOTICE} {SENDER}',
    'sendfile.submit' => 'Envoyer',
    'sendfile.file_selected' => 'Fichier sélectionné : :name (:size)',
    'sendfile.success' => 'Fichier envoyé avec succès !',
    'sendfile.success_note' => 'Transmettez ce lien au destinataire. Il expirera automatiquement.',
    'sendfile.success_email' => 'Fichier envoyé par e-mail',
    'sendfile.success_email_note' => 'Le lien de téléchargement a été envoyé au destinataire. Vous pouvez aussi le retrouver dans Mes fichiers.',

    // Mes fichiers (SPA)
    'myfiles.empty' => 'Aucun fichier envoyé pour le moment.',

    // Mes dépôts (SPA)
    'mydeposits.create_title' => 'Créer un nouveau lien de dépôt',
    'mydeposits.label' => 'Nom / libellé',
    'mydeposits.label_placeholder' => 'Ex : Dépôt client Dupont',
    'mydeposits.expiration' => 'Expiration du lien (jours)',
    'mydeposits.empty' => 'Aucun dépôt créé pour le moment.',
    'mydeposits.expires_on' => 'Expire le :date',
    'mydeposits.files_received' => 'Fichiers reçus (:count)',
    'mydeposits.no_files' => "Aucun fichier reçu pour l'instant.",

    // Message secret (SPA)
    'secret.title' => 'Partager un message secret',
    'secret.description' => "Le message est chiffré en base de données et n'est consultable qu'une seule fois via le lien généré : dès qu'il est ouvert et révélé, il est définitivement supprimé, même si le lien n'a pas encore expiré.",
    'secret.message' => 'Message',
    'secret.message_placeholder' => 'Saisissez ici le texte à partager (mot de passe, clé, information confidentielle...)',
    'secret.max_chars' => ':count caractères maximum.',
    'secret.validity' => 'Durée de validité',
    'secret.day_one' => '1 jour',
    'secret.day_many' => ':days jours',
    'secret.validity_hint' => "Le lien expire automatiquement passé ce délai, s'il n'a pas déjà été consulté.",
    'secret.generate' => 'Générer le lien',
    'secret.result_note' => "Transmettez ce lien par un canal de confiance : il ne s'affichera nulle part ailleurs et ne sera plus consultable une fois ouvert et révélé par le destinataire.",

    // Mes messages secrets (SPA)
    'mysecrets.empty' => 'Aucun message secret créé pour le moment.',

    // Utilisateurs (SPA, admin)
    'users.create_title' => 'Créer un utilisateur',
    'users.username_col' => 'Utilisateur',
    'users.admin' => 'Administrateur',
    'users.new_password' => 'Nouveau mdp',
    'users.reset' => 'Réinitialiser',
    'users.oidc_account' => 'Compte OIDC',

    // Profil (SPA)
    'profile.title' => 'Votre profil',
    'profile.description' => 'Définissez votre adresse e-mail. Elle est utilisée pour vous notifier quand quelqu\'un dépose un fichier sur un de vos liens de dépôt.',
    'profile.email_label' => 'Adresse e-mail',
    'profile.email_placeholder' => 'nom@exemple.com',
    'profile.save' => 'Sauvegarder',
    'profile.oidc_info' => 'Votre e-mail est automatiquement synchronisé depuis votre fournisseur OIDC.',

    // Infobulles des boutons-icônes
    'action.copy_link' => 'Copier le lien',
    'action.revoke_link' => 'Révoquer le lien',
    'action.disable' => 'Désactiver',
    'action.enable' => 'Réactiver',

    // Boîtes de confirmation
    'confirm.revoke.title' => 'Révoquer le lien',
    'confirm.revoke.message' => 'Le destinataire ne pourra plus télécharger ce fichier. Cette action est irréversible.',
    'confirm.revoke.label' => 'Révoquer',
    'confirm.delete_file.title' => 'Supprimer le fichier',
    'confirm.delete_file.message' => 'Le fichier et son lien seront définitivement supprimés du serveur.',
    'confirm.delete_deposit_file.title' => 'Supprimer le fichier reçu',
    'confirm.delete_deposit_file.message' => 'Ce fichier sera définitivement supprimé du serveur.',
    'confirm.delete_secret.title' => 'Supprimer le message secret',
    'confirm.delete_secret.message' => "Le lien sera immédiatement inutilisable, même si le message n'a pas encore été consulté.",
    'confirm.delete_user.title' => "Supprimer l'utilisateur",
    'confirm.delete_user.message' => 'Le compte sera définitivement supprimé.',

    // Erreurs (côté client et pages publiques)
    'error.file_too_large' => 'Le fichier dépasse la taille maximale autorisée.',
    'error.finalize' => 'Erreur lors de la finalisation.',
    'error.chunk_send_failed' => "Erreur pendant l'envoi du fragment.",
    'error.server_status' => 'Erreur serveur (:status)',
    'error.server' => 'Erreur serveur.',
    'error.page_title' => 'Une erreur est survenue',
    'error.page_hint' => 'Consultez data/php-error.log pour le détail.',
    'error.tmp_not_writable' => "Le dossier de stockage temporaire n'est pas accessible en écriture. Vérifiez les droits (chmod/chown) sur uploads/tmp.",
    'error.chunk_save_failed' => 'Impossible d\'enregistrer le fragment :index.',
    'error.chunk_invalid' => 'Fragment :index invalide ou trop volumineux (vérifiez upload_max_filesize/post_max_size côté PHP).',
    'error.final_file_failed' => 'Impossible de créer le fichier final.',
    'error.link_not_found' => "Ce lien n'existe pas ou a été supprimé.",
    'error.secret_not_found' => "Ce lien n'existe pas ou le message a déjà été consulté puis supprimé.",
    'error.link_revoked' => 'Ce lien a été révoqué par son propriétaire.',
    'error.link_expired' => 'Ce lien a expiré.',
    'error.file_unavailable' => "Le fichier n'est plus disponible sur le serveur.",
    'error.password_incorrect' => 'Mot de passe incorrect.',
    'error.password_required' => 'Mot de passe requis.',
    'error.session_expired' => 'Session expirée, merci de recharger la page et réessayer.',
    'error.secret_consumed' => "Ce message a déjà été consulté. Pour des raisons de confidentialité, il n'est lisible qu'une seule fois et a été supprimé.",
    'error.secret_just_consumed' => "Ce message vient d'être consulté (peut-être depuis un autre onglet) et n'est plus disponible.",
    'error.deposit_not_found' => 'Lien de dépôt introuvable.',
    'error.deposit_disabled' => 'Ce lien de dépôt a été désactivé.',
    'error.deposit_expired' => 'Ce lien de dépôt a expiré.',

    // Page publique de téléchargement
    'public.download.title' => 'Téléchargement - :app',
    'public.download.unavailable' => 'Téléchargement indisponible',
    'public.download.protected' => 'Fichier protégé',
    'public.download.ready' => 'Fichier prêt à télécharger',
    'public.download.meta' => ':size · expire le :date',

    // Page publique de dépôt
    'public.deposit.title' => 'Dépôt de fichier - :app',
    'public.deposit.unavailable' => 'Dépôt indisponible',
    'public.deposit.protected' => 'Dépôt protégé : :label',
    'public.deposit.upload_title' => 'Déposer un fichier — :label',
    'public.deposit.info' => 'Taille maximale : :size. Ce lien de dépôt expire le :date.',
    'public.deposit.your_name' => 'Votre nom (optionnel)',
    'public.deposit.name_placeholder' => 'Pour vous identifier auprès du destinataire',
    'public.deposit.submit' => 'Envoyer le fichier',
    'public.deposit.success' => 'Fichier envoyé avec succès !',

    // Page publique de message secret
    'public.secret.title' => 'Message secret - :app',
    'public.secret.unavailable' => 'Message indisponible',
    'public.secret.received' => "Un message secret t'a été partagé",
    'public.secret.received_note' => 'Ce message ne peut être consulté qu\'une seule fois. Une fois révélé, il sera définitivement supprimé du serveur.',
    'public.secret.reveal' => 'Révéler le message',
    'public.secret.revealed' => 'Voici le message',
    'public.secret.deleted_note' => 'Ce message a maintenant été supprimé du serveur : gardez-le précieusement si vous en avez besoin, il ne sera plus consultable via ce lien.',

    // Messages de l'API (réponses JSON affichées en toasts / alertes)
    'api.invalid_request' => 'Requête invalide.',
    'api.max_size_exceeded' => 'Taille maximale dépassée.',
    'api.expiration_range' => "La durée d'expiration doit être comprise entre :min et :max jours.",
    'api.invalid_email' => "L'adresse e-mail du destinataire est invalide.",
    'api.email_failed' => "Le fichier a été enregistré mais l'envoi de l'e-mail a échoué. Le lien reste disponible dans Mes fichiers.",
    'api.file_not_found' => 'Fichier introuvable.',
    'api.file_not_found_or_revoked' => 'Fichier introuvable ou déjà révoqué.',
    'api.link_revoked_msg' => 'Le lien a été révoqué.',
    'api.file_deleted' => 'Le fichier a été supprimé.',
    'api.deposit_default_label' => 'Dépôt sans nom',
    'api.deposit_created' => 'Le dépôt a été créé.',
    'api.deposit_not_found' => 'Dépôt introuvable.',
    'api.deposit_status_updated' => 'Statut du dépôt mis à jour.',
    'api.deposit_file_deleted' => 'Fichier reçu supprimé.',
    'api.deposit_link_not_found' => 'Lien de dépôt introuvable.',
    'api.file_sent' => 'Fichier envoyé avec succès.',
    'api.message_empty' => 'Le message ne peut pas être vide.',
    'api.message_too_long' => 'Le message dépasse la longueur maximale autorisée (:count caractères).',
    'api.invalid_validity' => 'Durée de validité invalide.',
    'api.secret_created' => 'Message secret créé.',
    'api.secret_not_found' => 'Message introuvable.',
    'api.secret_deleted' => 'Le message secret a été supprimé.',
    'api.user_credentials_required' => "Nom d'utilisateur et mot de passe (4 caractères min.) requis.",
    'api.user_created' => 'Utilisateur créé.',
    'api.user_exists' => "Ce nom d'utilisateur existe déjà.",
    'api.cannot_delete_self' => 'Impossible de supprimer votre propre compte.',
    'api.user_not_found' => 'Utilisateur introuvable.',
    'api.user_deleted' => 'Utilisateur supprimé.',
    'api.password_too_short' => 'Le mot de passe doit contenir au moins 4 caractères.',
    'api.password_reset' => 'Mot de passe réinitialisé.',
    'api.profile_updated' => 'Profil mis à jour.',
    'api.login_required' => 'Non authentifié.',
    'api.admin_only' => 'Accès réservé aux administrateurs.',
    'api.unknown_action' => 'Action inconnue.',
    'api.too_many_attempts' => 'Trop de tentatives. Réessayez dans :seconds seconde(s).',
    'api.account_locked' => 'Trop de tentatives. Compte verrouillé pendant :seconds secondes.',
    'api.bad_credentials' => 'Identifiants incorrects.',
    'api.credentials_required' => 'Identifiants requis.',
    'api.csrf_invalid' => 'Jeton CSRF invalide ou expiré.',

    // Helpers
    'helpers.default_filename' => 'fichier',

    // E-mails (sujet/corps par défaut, dans la langue de l'expéditeur ;
    // surchargeables dans config.php via MAIL_SUBJECT / MAIL_BODY_TEMPLATE)
    'mail.subject' => 'Un fichier a été partagé avec vous',
    'mail.body' => "Bonjour,\n\n{SENDER} vous a partagé un fichier via Lethe.\n\nLien de téléchargement :\n{LINK}\n\nCe lien expirera le {EXPIRATION}.\n{PASSWORD_NOTICE}\n\nCordialement.",
    'mail.password_notice' => 'Ce fichier est protégé par un mot de passe qui vous a été communiqué séparément.',

    // Notification de dépôt (envoyée au propriétaire du dépôt quand un fichier est déposé)
    'mail.deposit.subject' => 'Un fichier a été déposé sur votre lien : :label',
    'mail.deposit.body' => "Bonjour,\n\n{UPLOADER_NAME} a déposé un fichier sur votre lien de dépôt « {DEPOSIT_LABEL} ».\n\nLien de dépôt :\n{DEPOSIT_LINK}\n\nCordialement.",
    'mail.deposit.anonymous' => 'Un utilisateur anonyme',
];
