<?php

return [

    'mail' => [
        'teamInvitation' => [
            'subject' => 'Invitation à s\'inscrire sur ' . env('APP_NAME'),
            'greeting' => 'Bonjour chère collègue',
            'action' => 'Inscription sur ' . env('APP_NAME'),
            'line1' => 'Je vous invite à cliquer ci-dessous afin de vous inscrire sur la plateforme ' . env('APP_NAME') . ' avec laquelle nous pourrons gerer communément nos offres d\'emploi',
            'line2' => 'Après votre inscription, vous serez automatiquement rattaché à l\'équipe de notre entreprise (:companyName).',
            'salutation' => 'Bien Cordialement, :username'
        ],

        'resetPasswordStepOne' => [
            'subject' => 'Demande de réinitialisation de votre mot de passe ' . env('APP_NAME'),
            'greeting' => 'Bonjour :username',
            'action' => 'Voir mon nouveau mot de passe',
            'line1' => 'Une demande de réinitialisation de votre mot de passe a été faite sur notre application ' . env('APP_NAME') . '. Si vous êtes bien à l\'origine de celle-ci, vous pouvez cliquer le lien ci-dessous pour voir celui-ci.',
            'salutation' => 'Bien Cordialement, l\'équipe ' . env('APP_NAME')
        ],

        'resetPasswordStepTwo' => [
            'subject' => 'Votre demande de réinitialisation de votre mot de passe ' . env('APP_NAME'),
            'line1' => 'Votre nouveau de mot de passe est: :newpassword',
            'salutation' => 'Bien Cordialement, l\'équipe ' . env('APP_NAME')
        ]
    ],

];