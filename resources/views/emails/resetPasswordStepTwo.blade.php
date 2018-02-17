@component('mail::message')
# {{ trans('strings.mail.resetPasswordStepTwo.subject') }}

{{ trans('strings.mail.resetPasswordStepTwo.line1', ['newpassword' => $newPassword]) }}<br /><br />
{{ trans('strings.mail.resetPasswordStepTwo.salutation') }}
@endcomponent
