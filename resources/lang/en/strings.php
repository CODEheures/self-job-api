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
        ],

        'resetPasswordStepOne' => [
            'subject' => 'Ask for password reinitialisation on ' . env('APP_NAME'),
            'greeting' => 'Hello :username',
            'action' => 'Get my new password',
            'line1' => 'A request to reset your password has been made on our application ' . env('APP_NAME') . '. If you are at the origin of it, you may click the link below and see this one.',
            'salutation' => 'Best regards, team ' . env('APP_NAME')
        ],

        'resetPasswordStepTwo' => [
            'subject' => 'Your Ask for password reinitialisation on ' . env('APP_NAME'),
            'line1' => 'Your new password is: :newpassword',
            'salutation' => 'Best regards, team ' . env('APP_NAME')
        ]
    ],

];