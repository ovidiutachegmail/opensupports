<?php
use Respect\Validation\Validator as DataValidator;
DataValidator::with('CustomValidations', true);

class SendRecoverPasswordController extends Controller {
    const PATH = '/send-recover-password';

    private $token;
    private $user;

    public function validations() {
        return [
            'permission' => 'any',
            'requestData' => [
                'email' => [
                    'validation' => DataValidator::email()->userEmail(),
                    'error' => ERRORS::INVALID_EMAIL
                ]
            ]
        ];
    }

    public function handler() {
        if(!Controller::isUserSystemEnabled()) {
            throw new Exception(ERRORS::USER_SYSTEM_DISABLED);
        }
        
        $email = Controller::request('email');
        $this->user = User::getUser($email,'email');
        
        if(!$this->user->isNull()) {
            $this->token = Hashing::generateRandomToken();

            $recoverPassword = new RecoverPassword();
            $recoverPassword->setProperties(array(
                'email' => $email,
                'token' => $this->token
            ));
            $recoverPassword->store();

            $this->sendEmail();

            Response::respondSuccess();
        } else {
            Response::respondError(ERRORS::INVALID_EMAIL);
        }
        
    }

    public function sendEmail() {
        $mailSender = new MailSender();

        $mailSender->setTemplate(MailTemplate::PASSWORD_FORGOT, [
            'to' => $this->user->email,
            'name' => $this->user->name,
            'token' => $this->token
        ]);

        $mailSender->send();
    }
}
