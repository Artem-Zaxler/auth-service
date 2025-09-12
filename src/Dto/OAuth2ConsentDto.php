<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class OAuth2ConsentDto
{
    #[Assert\NotBlank(message: "Client ID is required")]
    public string $clientId;

    #[Assert\NotBlank(message: "Redirect URI is required")]
    #[Assert\Url(message: "Redirect URI must be a valid URL")]
    public string $redirectUri;

    public ?string $scope;

    public ?string $state;

    public ?string $responseType;

    public ?string $grantType;

    public function __construct(array $data)
    {
        $this->clientId = $data['client_id'] ?? '';
        $this->redirectUri = $data['redirect_uri'] ?? '';
        $this->scope = $data['scope'] ?? null;
        $this->state = $data['state'] ?? null;
        $this->responseType = $data['response_type'] ?? null;
        $this->grantType = $data['grant_type'] ?? null;
    }
}
