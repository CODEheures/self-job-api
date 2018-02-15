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
        ]
    ],

];