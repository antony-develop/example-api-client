<?php

declare(strict_types=1);

namespace ExampleApiClient;

use ExampleApiClient\DTO\CommentDTO;
use ExampleApiClient\Exception\ExampleClientException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use JMS\Serializer\SerializerInterface;
use Psr\Http\Message\ResponseInterface;

class ExampleClient
{
    private Client $httpClient;
    private SerializerInterface $serializer;

    public function __construct(
        Client $httpClient,
        SerializerInterface $serializer
    ) {
        $this->httpClient = $httpClient;
        $this->serializer = $serializer;
    }

    /**
     * @return CommentDTO[]
     *
     * @throws ExampleClientException
     */
    public function getComments(): array
    {
        $methodUri = 'comments';

        $response = $this->request('GET', $methodUri);

        /** @var CommentDTO[] $comments */
        $comments = $this->serializer->deserialize(
            $response->getBody()->getContents(),
            sprintf('array<%s>', CommentDTO::class),
            'json'
        );

        return $comments;
    }

    /**
     * @param string $name
     * @param string $text
     *
     * @throws ExampleClientException
     */
    public function postComment(string $name, string $text): void
    {
        $methodUri = 'comment';
        $options = [
            RequestOptions::JSON => [
                'name' => $name,
                'text' => $text,
            ],
        ];

        $this->request('POST', $methodUri, $options);
    }

    /**
     * @param int $id
     * @param string $name
     * @param string $text
     *
     * @throws ExampleClientException
     */
    public function updateComment(int $id, string $name, string $text): void
    {
        $methodUri = "comment/$id";
        $options = [
            RequestOptions::JSON => [
                'name' => $name,
                'text' => $text,
            ],
        ];

        $this->request('PUT', $methodUri, $options);
    }

    /**
     * @param string $method
     * @param string $uri
     * @param array $options
     *
     * @return ResponseInterface
     *
     * @throws ExampleClientException
     */
    private function request(string $method, string $uri, array $options = []): ResponseInterface
    {
        try {
            return $this->httpClient->request($method, $uri, $options);
        } catch (GuzzleException $e) {
            throw new ExampleClientException('Error on requesting Example API', $e->getCode(), $e);
        }
    }
}
