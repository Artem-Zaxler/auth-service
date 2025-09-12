<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "OAuth2ConsentDto",
    description: "Data for OAuth2 consent request"
)]
class OAuth2ConsentDto
{
    #[OA\Property(type: "string", example: "client_id_123")]
    #[Assert\NotBlank(message: "Client ID is required")]
    public string $clientId;

    #[OA\Property(type: "string", example: "https://example.com/callback")]
    #[Assert\NotBlank(message: "Redirect URI is required")]
    #[Assert\Url(message: "Redirect URI must be a valid URL")]
    public string $redirectUri;

    #[OA\Property(type: "string", example: "read write")]
    public ?string $scope;

    #[OA\Property(type: "string", example: "state_123")]
    public ?string $state;

    #[OA\Property(type: "string", example: "code")]
    public ?string $responseType;

    #[OA\Property(type: "string", example: "authorization_code")]
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
