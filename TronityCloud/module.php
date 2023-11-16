<?php

declare(strict_types=1);
class TronityCloud extends IPSModule
{
    private static $endpoint = 'https://api.tronity.tech';

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

        if (!$this->ReadPropertyString('ClientID') || !$this->ReadPropertyString('ClientSecret')) {
            $this->SetStatus(200);
            return;
        }

        $this->SetStatus(IS_ACTIVE);
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

        if (!$this->updateAuthenticationToken()) {
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

        return json_encode($this->requestData($data['RequestURL'], $context));
    }

    public function TestAuthentication(): void
    {
        try {
            $this->Authenticate();
            echo $this->Translate('OK');
        } catch (Exception $e) {
            echo $e;
        }
    }

    private function Authenticate(): void
    {
        //Get the Access Token from the Tronity Platform
        $data = [
            'client_id'     => $this->ReadPropertyString('ClientID'),
            'client_secret' => $this->ReadPropertyString('ClientSecret'),
            'grant_type'    => 'app',
        ];

        $context = [
            'http' => [
                'method'        => 'POST',
                'content'       => json_encode($data),
                'header'        => "Content-Type: application/json\r\n",
                'ignore_errors' => true,
            ]
        ];
        $response = $this->requestData('/authentication', $context);

        // Throw exception if we have an error
        if (array_key_exists('statusCode', $response)) {
            throw new Exception($response['message']);
        }

        $this->SetBuffer('authToken', $response['access_token']);
        $this->SetBuffer('expiresAt', time() + $response['expires_in']);
    }

    private function updateAuthenticationToken(): bool
    {
        if (!$this->GetBuffer('expiresAt') || time() > $this->GetBuffer('expiresAt')) {
            try {
                $this->Authenticate();
                $this->SetStatus(IS_ACTIVE);
            } catch (Exception $e) {
                $this->SetStatus(IS_EBASE);
                echo $e;
                return false;
            }
        }
        return true;
    }

    private function requestData(string $url, array $context)
    {
        $context = stream_context_create($context);
        $response = file_get_contents(self::$endpoint . $url, false, $context);

        $this->SendDebug(self::$endpoint . $url, $response, 0);

        return json_decode($response, true);
    }
}