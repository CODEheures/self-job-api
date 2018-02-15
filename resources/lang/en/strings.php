<?php

return [

    'mail' => [
        'teamInvitation' => [
            'subject' => 'Invitation to register on ' . env('APP_NAME'),
            'greeting' => 'Hello dear colleague',
            'action' => 'Register on ' . env('APP_NAME'),
            'line1' => 'I invite you to click below to register on the platform ' . env('APP_NAME') . ' with which we can manage our job offers',
            'line2' => 'After your registration, you will be automatically attached to the team of our company (:companyName).',
            'salutation' => 'Best regards, :username'
        ]
    ],

];