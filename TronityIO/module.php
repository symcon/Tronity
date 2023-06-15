<?php

declare(strict_types=1);
    class IO extends IPSModule
    {
        public function Create()
        {
            //Never delete this line!
            parent::Create();

            //Properties für ClientID und ClientSecret
            $this->RegisterPropertyString('ClientID', '');
            $this->RegisterPropertyString('ClientSecret', '');
        }

        public function Destroy()
        {
            //Never delete this line!
            parent::Destroy();
        }

        public function ApplyChanges()
        {
            //Never delete this line!
            parent::ApplyChanges();

            if ($this->ReadPropertyString('ClientID') == '' || $this->ReadPropertyString('ClientSecret') == '') {
                $this->SetStatus(200);
                return;
            }

            if (!$this->isAuthTokenAvailable()) {
				IPS_LogMessage('Buffer Token', $this->GetBuffer('authToken'));
                $this->SetStatus(201);
                return;
            }
            $this->SetStatus(102);
        }

        public function ForwardData($JSONString)
        {
            /**
             * Data Structure should be:
             * RequestMethode -> GET or POST
             * RequestURL -> URL for the request
             * RequestedData -> Payload for the request
             */
            $data = json_decode($JSONString, true);
            $data = json_decode($data['Buffer'], true);

            if (!$this->isAuthTokenAvailable()) {
                return '[]';
            }

            $context = [
                'http' => [
                    'method'        => $data['RequestMethod'],
                    'content'       => $data['RequestData'],
                    'header'        => "Content-Type: application/json\r\nAuthorization: bearer " . $this->GetBuffer('authToken'),
                    'ignore_errors' => true,
                ]
            ];
            $response = $this->requestData($data['RequestURL'], $context);
            $this->SendDebug('Response as json', json_encode($response), 0);

            return json_encode($response);
        }

        public function AuthenticateUser(string $clientID, string $clientSecret): int
        {
			
            if ($clientID == '' || $clientSecret = '') {
                $clientID = $this->ReadPropertyString('ClientID');
                $clientSecret = $this->ReadPropertyString('ClientSecret');
            }
			IPS_LogMessage('ClientID', $clientID);

            //Get the Access Token from the Tronity Platform
            $data = ['client_id' => $clientID, 'client_secret' => $clientSecret, 'grant_type' => 'app'];

            $context = [
                'http' => [
                    'method'        => 'POST',
                    'content'       => json_encode($data),
                    'header'        => "Content-Type: application/json\r\n",
                    'ignore_errors' => true,
                ]
            ];
            $response = $this->requestData('https://api.tronity.tech/authentication', $context);

            //Evaluation of the response
            if (array_key_exists('statusCode', $response)) {
                switch ($response['statusCode']) {
                case 401:
                        $this->UpdateFormField('AuthStatus', 'caption', $this->Translate('The provided credential are incorrect'));
                    return 201;
                    break;
                case 500:
                        $this->UpdateFormField('AuthStatus', 'caption', $this->Translate('The server experienced an unexpected error'));
                    return 203;
                    break;
                default:
                        $this->UpdateFormField('AuthStatus', 'caption', sprintf($this->Translate('Status code: %u is not supported. The Message is: %s .'), $response['statusCode'], $response['message']));
                    return 200;
                    break;
                }
            } else {
                $this->UpdateFormField('AuthStatus', 'caption', $this->Translate('The authentication is succeed'));
            }

            $this->SetBuffer('authToken', $response['access_token']);
            $this->SetBuffer('expiresAt', time() + $response['expires_in']);
            return 102;
        }

        private function isAuthTokenAvailable(): bool
        {
            if ($this->GetBuffer('expiresAt') == '' || time() > $this->GetBuffer('expiresAt')) {
                $status = $this->AuthenticateUser('', '');
                if ($status != 102) {
                    $this->SetStatus($status);
                    return false;
                }
            }
            return true;
        }

        private function requestData(string $url, array $context)
        {
            $context = stream_context_create($context);
            $response = file_get_contents($url, false, $context);
            IPS_LogMessage('Response', $response);
            return json_decode($response, true);
        }
    }