<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication Language Lines
    |--------------------------------------------------------------------------
    */

    'failed' => 'These credentials do not match our records.',
    'password' => 'The provided password is incorrect.',
    'throttle' => 'Too many login attempts. Your access is locked; please try again in :minutes minute(s).',
    'inactive' => 'Your account is inactive. Please contact the administrator.',
    'timeout' => 'You were signed out after a period of inactivity.',
    'password_changed' => 'Your password has been changed.',

    'login' => [
        'title' => 'Login',
        'welcome' => 'Welcome Back!',
        'subtitle' => 'Sign in to continue to :app.',
        'email' => 'Email',
        'email_placeholder' => 'Enter email',
        'password' => 'Password',
        'password_placeholder' => 'Enter password',
        'remember' => 'Remember me',
        'forgot' => 'Forgot your password?',
        'submit' => 'Log In',
        'demo' => [
            'title' => 'Demo account',
            'fill' => 'Use demo account',
        ],
    ],

    'logout' => 'Logout',

    'forgot' => [
        'title' => 'Reset Password',
        'subtitle' => 'Reset your password for :app.',
        'hint' => 'Enter your <b>Email</b> and instructions will be sent to you!',
        'submit' => 'Send Email',
        'back' => 'Back to login',
    ],

    'reset' => [
        'title' => 'Choose a new password',
        'subtitle' => 'Enter your email and a new password.',
        'password' => 'New password',
        'password_confirmation' => 'Confirm new password',
        'submit' => 'Reset Password',
    ],

    'force' => [
        'title' => 'Change your password',
        'subtitle' => 'You must choose a new password before continuing.',
        'current_password' => 'Current password',
        'submit' => 'Change Password',
    ],

    'password_rules' => 'At least 8 characters, with upper and lower case letters and a number.',

    'two_factor' => [
        'title' => 'Verification code',
        'subtitle' => 'We emailed you a 6-digit code. Enter it to continue.',
        'code' => 'Code',
        'submit' => 'Verify',
        'resend' => 'Resend code',
        'sent' => 'A new code has been sent to your email.',
        'invalid' => 'The code is invalid or has expired.',
        'mail_subject' => 'Your login verification code',
        'mail_line' => 'Use the following code to complete your sign in:',
        'mail_expiry' => 'The code expires in :minutes minutes.',
    ],

];
