<?php

declare(strict_types=1);

namespace ExampleApiClient\Tests;

use ExampleApiClient\DTO\CommentDTO;
use ExampleApiClient\ExampleClient;
use ExampleApiClient\Exception\ExampleClientException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use JMS\Serializer\SerializerBuilder;
use PHPUnit\Framework\TestCase;

class ExampleApiTest extends TestCase
{
    private const BASE_URI = 'http://example.com/';
    private const HTTP_OK = 200;
    private const HTTP_INTERNAL_SERVER_ERROR = 500;

    private const COMMENT_1 = [
        'id' => 1,
        'name' => 'anton',
        'text' => 'test comment',
    ];
    private const COMMENT_2 = [
        'id' => 2,
        'name' => 'anton',
        'text' => 'test comment 2',
    ];

    public function testGetCommentsResponse(): void
    {
        $mockHandler = new MockHandler([
            new Response(self::HTTP_OK, ['Content-Type' => 'application/json'], json_encode([
                self::COMMENT_1,
                self::COMMENT_2
            ])),
        ]);
        $handlerStack = HandlerStack::create($mockHandler);
        $exampleApiClient = $this->createExampleApiClient($handlerStack);

        $comments = $exampleApiClient->getComments();

        $expected = [
            new CommentDTO(self::COMMENT_1['id'], self::COMMENT_1['name'], self::COMMENT_1['text']),
            new CommentDTO(self::COMMENT_2['id'], self::COMMENT_2['name'], self::COMMENT_2['text']),
        ];

        $this->assertEquals($comments, $expected);
    }

    public function testGetCommentsOnError(): void
    {
        $mockHandler = new MockHandler([
            new ServerException(
                'Error Communicating with Server',
                new Request('GET', 'comments'),
                new Response(self::HTTP_INTERNAL_SERVER_ERROR)
            )
        ]);
        $handlerStack = HandlerStack::create($mockHandler);
        $exampleApiClient = $this->createExampleApiClient($handlerStack);

        try {
            $exampleApiClient->getComments();
        } catch (ExampleClientException $e) {
            $this->assertEquals($e->getMessage(), 'Error on requesting Example API');
            $this->assertEquals($e->getCode(), self::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function testGetCommentsRequest(): void
    {
        $mockHandler = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode([self::COMMENT_1])),
        ]);
        $container = [];
        $history = Middleware::history($container);
        $handlerStack = HandlerStack::create($mockHandler);
        $handlerStack->push($history);
        $exampleApiClient = $this->createExampleApiClient($handlerStack);

        $exampleApiClient->getComments();

        /** @var Request $request */
        $request = $container[0]['request'];

        $this->assertEquals('GET', $request->getMethod());
        $this->assertEquals('/comments', $request->getRequestTarget());
    }

    public function testPostCommentRequest(): void
    {
        $mockHandler = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode(true)),
        ]);

        $container = [];
        $history = Middleware::history($container);
        $handlerStack = HandlerStack::create($mockHandler);
        $handlerStack->push($history);
        $exampleApiClient = $this->createExampleApiClient($handlerStack);

        $exampleApiClient->postComment(self::COMMENT_1['name'], self::COMMENT_1['text']);

        /** @var Request $request */
        $request = $container[0]['request'];

        $this->assertEquals('POST', $request->getMethod());
        $this->assertEquals('/comment', $request->getRequestTarget());
        $this->assertEquals(
            json_encode([
                'name' => self::COMMENT_1['name'],
                'text' => self::COMMENT_1['text'],
            ]),
            $request->getBody()->getContents()
        );
    }

    public function testUpdateCommentRequest(): void
    {
        $mockHandler = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode(true)),
        ]);
        $container = [];
        $history = Middleware::history($container);
        $handlerStack = HandlerStack::create($mockHandler);
        $handlerStack->push($history);
        $exampleApiClient = $this->createExampleApiClient($handlerStack);

        $exampleApiClient->updateComment(self::COMMENT_1['id'], self::COMMENT_2['name'], self::COMMENT_2['text']);

        /** @var Request $request */
        $request = $container[0]['request'];

        $this->assertEquals('PUT', $request->getMethod());
        $this->assertEquals('/comment/' . self::COMMENT_1['id'], $request->getRequestTarget());
        $this->assertEquals(
            json_encode([
                'name' => self::COMMENT_2['name'],
                'text' => self::COMMENT_2['text'],
            ]),
            $request->getBody()->getContents()
        );
    }

    private function createExampleApiClient(HandlerStack $handlerStack): ExampleClient
    {
        $httpClient = new Client([
            'handler' => $handlerStack,
            'base_uri' => self::BASE_URI,
        ]);
        $serializer = SerializerBuilder::create()->build();

        return new ExampleClient($httpClient, $serializer);
    }
}
