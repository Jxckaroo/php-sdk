<?php declare(strict_types=1);

namespace JustSteveKing\PhpSdk\Tests;

use DI\Container;
use RuntimeException;
use PHPUnit\Framework\TestCase;
use JustSteveKing\PhpSdk\Client;
use JustSteveKing\UriBuilder\Uri;
use Psr\Container\ContainerInterface;
use JustSteveKing\HttpSlim\HttpClient;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpClient\Psr18Client;
use JustSteveKing\PhpSdk\Resources\AbstractResource;

class ClientTest extends TestCase
{
    /**
     * @test
     */
    public function it_will_create_a_client()
    {
        $client = new Client(
            new Container,
            'https://www.test.com'
        );

        $this->assertInstanceOf(
            Client::class,
            $client
        );
    }

    /**
     * @test
     */
    public function it_will_throw_an_exception_if_uri_is_not_a_uri()
    {
        $this->expectException(RuntimeException::class);
        $client = new Client(
            new Container,
            'definitely.not.a.uri'
        );
    }

    /**
     * @test
     */
    public function it_will_allow_you_to_access_and_use_the_uri()
    {
        $client = new Client(
            new Container,
            'https://www.test.com'
        );

        $this->assertInstanceOf(
            Uri::class,
            $client->uri()
        );

        $this->assertEquals(
            'https://www.test.com',
            $client->uri()->toString()
        );

        $client->uri()->addPath('path');

        $this->assertEquals(
            'https://www.test.com/path',
            $client->uri()->toString()
        );
    }

    /**
     * @test
     */
    public function it_will_let_me_access_the_container()
    {
        $client = new Client(
            new Container,
            'https://www.test.com'
        );

        $this->assertInstanceOf(
            ContainerInterface::class,
            $client->factory()
        );

        $this->assertFalse(
            $client->factory()->has('not-in-container')
        );
    }

    /**
     * @test
     */
    public function it_will_let_me_register_a_new_resource()
    {
        $client = new Client(
            new Container,
            'https://www.test.com'
        );

        $client->addResource('name', new class extends AbstractResource {});

        $this->assertTrue(
            $client->factory()->has('name')
        );
    }

    /**
     * @test
     */
    public function it_will_let_me_forward_a_call_to_a_resource()
    {
        $client = new Client(
            new Container,
            'https://www.test.com'
        );

        $http = HttpClient::build(
            new Psr18Client(), // http client (psr-18)
            new Psr18Client(), // request factory (psr-17)
            new Psr18Client() // stream factory (psr-17)
        );
        $client->addTransport($http);

        $client->addResource('name', new class extends AbstractResource {
            protected string $path = 'name';
        });

        $this->assertEquals(
            'https://www.test.com/name',
            $client->name->uri()->toString()
        );
    }

    /**
     * @test
     */
    public function it_will_perform_requests_on_the_resource()
    {
        $client = new Client(
            new Container,
            'https://jsonplaceholder.typicode.com'
        );

        $http = HttpClient::build(
            new Psr18Client(), // http client (psr-18)
            new Psr18Client(), // request factory (psr-17)
            new Psr18Client() // stream factory (psr-17)
        );
        $client->addTransport($http);

        $client->addResource('todos', new class extends AbstractResource {
            protected string $path = 'todos';
        });

        $this->assertInstanceOf(
            ResponseInterface::class,
            $client->todos->get()
        );

        $this->assertInstanceOf(
            ResponseInterface::class,
            $client->todos->find(1)
        );
    }
}