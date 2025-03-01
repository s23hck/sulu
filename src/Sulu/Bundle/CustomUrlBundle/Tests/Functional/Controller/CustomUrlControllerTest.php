<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CustomUrlBundle\Tests\Functional\Controller;

use Doctrine\Persistence\ObjectRepository;
use Sulu\Bundle\ActivityBundle\Domain\Model\ActivityInterface;
use Sulu\Bundle\DocumentManagerBundle\Slugifier\Urlizer;
use Sulu\Bundle\PageBundle\Document\PageDocument;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Bundle\TrashBundle\Domain\Model\TrashItemInterface;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

class CustomUrlControllerTest extends SuluTestCase
{
    /**
     * @var DocumentManagerInterface
     */
    private $documentManager;

    /**
     * @var ObjectRepository<ActivityInterface>
     */
    private $activityRepository;

    /**
     * @var ObjectRepository<TrashItemInterface>
     */
    private $trashItemRepository;

    /**
     * @var PageDocument
     */
    private $contentDocument;

    /**
     * @var KernelBrowser
     */
    private $client;

    public function setUp(): void
    {
        $this->client = $this->createAuthenticatedClient();
        $this->initPhpcr();
        $this->documentManager = $this->getContainer()->get('sulu_document_manager.document_manager');
        $this->activityRepository = $this->getEntityManager()->getRepository(ActivityInterface::class);
        $this->trashItemRepository = $this->getEntityManager()->getRepository(TrashItemInterface::class);
        $this->contentDocument = $this->documentManager->find('/cmf/sulu_io/contents', 'en');
    }

    public static function postProvider()
    {
        return [
            [
                [
                    'title' => 'Test',
                    'published' => true,
                    'baseDomain' => '*.sulu.io/*',
                    'domainParts' => ['test-1', 'test-2'],
                    'targetDocument' => true,
                    'targetLocale' => 'en',
                    'canonical' => true,
                    'redirect' => true,
                    'noFollow' => true,
                    'noIndex' => true,
                ],
                'test-1.sulu.io/test-2',
            ],
            [
                [
                    'title' => 'Test',
                    'published' => true,
                    'baseDomain' => '*.sulu.io/*',
                    'domainParts' => ['test-1', 'test-2'],
                    'targetLocale' => 'en',
                    'canonical' => true,
                    'redirect' => true,
                    'noFollow' => true,
                    'noIndex' => true,
                ],
                'test-1.sulu.io/test-2',
            ],
            [
                [
                    'title' => 'Test',
                    'published' => true,
                    'baseDomain' => '*.sulu.io',
                    'domainParts' => ['test-1'],
                    'targetDocument' => true,
                    'targetLocale' => 'en',
                    'canonical' => true,
                    'redirect' => true,
                    'noFollow' => true,
                    'noIndex' => true,
                ],
                'test-1.sulu.io',
            ],
            [
                [
                    'title' => 'Test',
                    'published' => true,
                    'baseDomain' => '*.sulu.io/*',
                    'domainParts' => ['test-1'],
                    'targetDocument' => true,
                    'targetLocale' => 'en',
                    'canonical' => true,
                    'redirect' => true,
                    'noFollow' => true,
                    'noIndex' => true,
                ],
                'test-1.sulu.io',
                400,
                9003,
            ],
        ];
    }

    /**
     * @dataProvider postProvider
     */
    public function testPost($data, $url, $statusCode = 200, $restErrorCode = null)
    {
        // content document is not there in provider
        if (\array_key_exists('targetDocument', $data)) {
            $data['targetDocument'] = $this->contentDocument->getUuid();
        }

        $this->client->jsonRequest('POST', '/api/webspaces/sulu_io/custom-urls', $data);

        $response = $this->client->getResponse();
        $responseData = \json_decode($response->getContent(), true);

        $this->assertHttpStatusCode($statusCode, $response);

        if (200 !== $statusCode) {
            $this->assertEquals($restErrorCode, $responseData['code']);

            return;
        }

        foreach ($data as $key => $value) {
            $this->assertEquals($value, $responseData[$key], $key);
        }

        $this->assertArrayHasKey('id', $responseData);
        $this->assertArrayHasKey('creator', $responseData);
        $this->assertArrayHasKey('changer', $responseData);

        $this->assertLessThanOrEqual(new \DateTime(), new \DateTime($responseData['created']));
        $this->assertLessThanOrEqual(new \DateTime(), new \DateTime($responseData['changed']));
        $this->assertEquals(Urlizer::urlize($data['title']), $responseData['nodeName']);
        if (\array_key_exists('targetDocument', $data)) {
            $this->assertEquals('Homepage', $responseData['targetTitle']);
        } else {
            $this->assertArrayNotHasKey('targetTitle', $responseData);
        }
        $this->assertEquals($url, $responseData['customUrl']);

        return $responseData['id'];
    }

    public static function postMultipleProvider()
    {
        return [
            [
                [
                    'title' => 'Test-1',
                    'published' => true,
                    'baseDomain' => '*.sulu.io/*',
                    'domainParts' => ['test-11', 'test-21'],
                    'targetDocument' => true,
                    'targetLocale' => 'en',
                    'canonical' => true,
                    'redirect' => true,
                    'noFollow' => true,
                    'noIndex' => true,
                ],
                [
                    'title' => 'Test-2',
                    'published' => true,
                    'baseDomain' => '*.sulu.io/*',
                    'domainParts' => ['test-12', 'test-22'],
                    'targetDocument' => true,
                    'targetLocale' => 'en',
                    'canonical' => true,
                    'redirect' => true,
                    'noFollow' => true,
                    'noIndex' => true,
                ],
                'test-11.sulu.io/test-21',
                'test-12.sulu.io/test-22',
            ],
            [
                [
                    'title' => 'Test',
                    'published' => true,
                    'baseDomain' => '*.sulu.io/*',
                    'domainParts' => ['test-11', 'test-21'],
                    'targetDocument' => true,
                    'targetLocale' => 'en',
                    'canonical' => true,
                    'redirect' => true,
                    'noFollow' => true,
                    'noIndex' => true,
                ],
                [
                    'title' => 'Test',
                    'published' => true,
                    'baseDomain' => '*.sulu.io/*',
                    'domainParts' => ['test-12', 'test-22'],
                    'targetDocument' => true,
                    'targetLocale' => 'en',
                    'canonical' => true,
                    'redirect' => true,
                    'noFollow' => true,
                    'noIndex' => true,
                ],
                'test-11.sulu.io/test-21',
                'test-12.sulu.io/test-22',
                400,
                9001,
            ],
            [
                [
                    'title' => 'Test-1',
                    'published' => true,
                    'baseDomain' => '*.sulu.io/*',
                    'domainParts' => ['test', 'test'],
                    'targetDocument' => true,
                    'targetLocale' => 'en',
                    'canonical' => true,
                    'redirect' => true,
                    'noFollow' => true,
                    'noIndex' => true,
                ],
                [
                    'title' => 'Test-2',
                    'published' => true,
                    'baseDomain' => '*.sulu.io/*',
                    'domainParts' => ['test', 'test'],
                    'targetDocument' => true,
                    'targetLocale' => 'en',
                    'canonical' => true,
                    'redirect' => true,
                    'noFollow' => true,
                    'noIndex' => true,
                ],
                'test.sulu.io/test',
                'test.sulu.io/test',
                409,
                1103,
            ],
        ];
    }

    /**
     * @dataProvider postMultipleProvider
     */
    public function testPostMultiple($before, $data, $beforeUrl, $url, $statusCode = 200, $restErrorCode = null): void
    {
        $this->testPost($before, $beforeUrl);
        $this->testPost($data, $url, $statusCode, $restErrorCode);
    }

    public static function putProvider()
    {
        return [
            [
                [
                    'test-11.sulu.io/test-21' => [
                        'title' => 'Test-1',
                        'published' => true,
                        'baseDomain' => '*.sulu.io/*',
                        'domainParts' => ['test-11', 'test-21'],
                        'targetDocument' => true,
                        'targetLocale' => 'en',
                        'canonical' => true,
                        'redirect' => true,
                        'noFollow' => true,
                        'noIndex' => true,
                    ],
                ],
                [
                    'title' => 'Test-2',
                    'published' => true,
                    'baseDomain' => '*.sulu.io/*',
                    'domainParts' => ['test-12', 'test-22'],
                    'targetDocument' => true,
                    'targetLocale' => 'en',
                    'canonical' => true,
                    'redirect' => true,
                    'noFollow' => true,
                    'noIndex' => true,
                ],
                'test-12.sulu.io/test-22',
            ],
            [
                [
                    'test-11.sulu.io/test-21' => [
                        'title' => 'Test-1',
                        'published' => true,
                        'baseDomain' => '*.sulu.io/*',
                        'domainParts' => ['test-11', 'test-21'],
                        'targetDocument' => true,
                        'targetLocale' => 'de',
                        'canonical' => true,
                        'redirect' => true,
                        'noFollow' => true,
                        'noIndex' => true,
                    ],
                ],
                [
                    'title' => 'Test-2',
                    'published' => true,
                    'baseDomain' => '*.sulu.io/*',
                    'domainParts' => ['test-12', 'test-22'],
                    'targetDocument' => true,
                    'targetLocale' => 'de',
                    'canonical' => true,
                    'redirect' => true,
                    'noFollow' => true,
                    'noIndex' => true,
                ],
                'test-12.sulu.io/test-22',
            ],
            [
                [
                    'test-1.sulu.io/test-1' => [
                        'title' => 'Test-1',
                        'published' => true,
                        'baseDomain' => '*.sulu.io/*',
                        'domainParts' => ['test-1', 'test-1'],
                        'targetDocument' => true,
                        'targetLocale' => 'en',
                        'canonical' => true,
                        'redirect' => true,
                        'noFollow' => true,
                        'noIndex' => true,
                    ],
                ],
                [
                    'title' => 'Test-1',
                    'published' => true,
                    'baseDomain' => '*.sulu.io/*',
                    'domainParts' => ['test-1', 'test-1'],
                    'targetDocument' => true,
                    'targetLocale' => 'en',
                    'canonical' => true,
                    'redirect' => true,
                    'noFollow' => true,
                    'noIndex' => true,
                ],
                'test-1.sulu.io/test-1',
            ],
            [
                [
                    'test-1.sulu.io/test-1' => [
                        'title' => 'Test-1',
                        'published' => true,
                        'baseDomain' => '*.sulu.io/*',
                        'domainParts' => ['test-1', 'test-1'],
                        'targetDocument' => true,
                        'targetLocale' => 'en',
                        'canonical' => true,
                        'redirect' => true,
                        'noFollow' => true,
                        'noIndex' => true,
                    ],
                ],
                [
                    'title' => 'Test-1',
                    'published' => true,
                    'baseDomain' => '*.sulu.io/',
                    'domainParts' => ['test-2'],
                    'targetDocument' => true,
                    'targetLocale' => 'en',
                    'canonical' => true,
                    'redirect' => true,
                    'noFollow' => true,
                    'noIndex' => true,
                ],
                'test-2.sulu.io',
            ],
            [
                [
                    'test-1.sulu.io/test-1' => [
                        'title' => 'Test-1',
                        'published' => true,
                        'baseDomain' => '*.sulu.io/*',
                        'domainParts' => ['test-1', 'test-1'],
                        'targetDocument' => true,
                        'targetLocale' => 'en',
                        'canonical' => true,
                        'redirect' => true,
                        'noFollow' => true,
                        'noIndex' => true,
                    ],
                ],
                [
                    'title' => 'Test-1',
                    'published' => true,
                    'baseDomain' => '*.sulu.io/*',
                    'domainParts' => ['test-1'],
                    'targetDocument' => true,
                    'targetLocale' => 'en',
                    'canonical' => true,
                    'redirect' => true,
                    'noFollow' => true,
                    'noIndex' => true,
                ],
                'test-1.sulu.io',
                400,
                9003,
            ],
            [
                [
                    'test.sulu.io/test' => [
                        'title' => 'Test',
                        'published' => true,
                        'baseDomain' => '*.sulu.io/*',
                        'domainParts' => ['test', 'test'],
                        'targetDocument' => true,
                        'targetLocale' => 'en',
                        'canonical' => true,
                        'redirect' => true,
                        'noFollow' => true,
                        'noIndex' => true,
                    ],
                    'test-1.sulu.io/test-1' => [
                        'title' => 'Test-1',
                        'published' => true,
                        'baseDomain' => '*.sulu.io/*',
                        'domainParts' => ['test-1', 'test-1'],
                        'targetDocument' => true,
                        'targetLocale' => 'en',
                        'canonical' => true,
                        'redirect' => true,
                        'noFollow' => true,
                        'noIndex' => true,
                    ],
                ],
                [
                    'title' => 'Test',
                    'published' => true,
                    'baseDomain' => '*.sulu.io/*',
                    'domainParts' => ['test-1', 'test-1'],
                    'targetDocument' => true,
                    'targetLocale' => 'en',
                    'canonical' => true,
                    'redirect' => true,
                    'noFollow' => true,
                    'noIndex' => true,
                ],
                'test-1.sulu.io/test-1',
                400,
                9001,
            ],
            [
                [
                    'test.sulu.io/test' => [
                        'title' => 'Test',
                        'published' => true,
                        'baseDomain' => '*.sulu.io/*',
                        'domainParts' => ['test', 'test'],
                        'targetDocument' => true,
                        'targetLocale' => 'en',
                        'canonical' => true,
                        'redirect' => true,
                        'noFollow' => true,
                        'noIndex' => true,
                    ],
                    'test-1.sulu.io/test-1' => [
                        'title' => 'Test-1',
                        'published' => true,
                        'baseDomain' => '*.sulu.io/*',
                        'domainParts' => ['test-1', 'test-1'],
                        'targetDocument' => true,
                        'targetLocale' => 'en',
                        'canonical' => true,
                        'redirect' => true,
                        'noFollow' => true,
                        'noIndex' => true,
                    ],
                ],
                [
                    'title' => 'Test-1',
                    'published' => true,
                    'baseDomain' => '*.sulu.io/*',
                    'domainParts' => ['test', 'test'],
                    'targetDocument' => true,
                    'targetLocale' => 'en',
                    'canonical' => true,
                    'redirect' => true,
                    'noFollow' => true,
                    'noIndex' => true,
                ],
                'test-1.sulu.io/test-1',
                409,
                1103,
            ],
        ];
    }

    /**
     * @dataProvider putProvider
     */
    public function testPut(array $before, $data, $url, $statusCode = 200, $restErrorCode = null)
    {
        foreach ($before as $beforeUrl => $beforeData) {
            $uuid = $this->testPost($beforeData, $beforeUrl);
        }

        // content document is not there in provider
        if (\array_key_exists('targetDocument', $data)) {
            $data['targetDocument'] = $this->contentDocument->getUuid();
        }

        $this->client->jsonRequest('PUT', '/api/webspaces/sulu_io/custom-urls/' . $uuid, $data);

        $response = $this->client->getResponse();
        $responseData = \json_decode($response->getContent(), true);

        $this->assertHttpStatusCode($statusCode, $response);

        if (200 !== $statusCode) {
            $this->assertEquals($restErrorCode, $responseData['code']);

            return;
        }

        foreach ($data as $key => $value) {
            $this->assertEquals($value, $responseData[$key], $key);
        }

        $this->assertEquals($uuid, $responseData['id']);
        $this->assertArrayHasKey('creator', $responseData);
        $this->assertArrayHasKey('changer', $responseData);

        $this->assertLessThanOrEqual(new \DateTime(), new \DateTime($responseData['created']));
        $this->assertLessThanOrEqual(new \DateTime(), new \DateTime($responseData['changed']));
        $this->assertEquals(Urlizer::urlize($data['title']), $responseData['nodeName']);
        if (\array_key_exists('targetDocument', $data)) {
            $this->assertEquals('Homepage', $responseData['targetTitle']);
        } else {
            $this->assertArrayNotHasKey('targetTitle', $responseData);
        }
        $this->assertEquals($url, $responseData['customUrl']);

        return $responseData['id'];
    }

    public static function getProvider()
    {
        return [
            [
                [
                    'title' => 'Test',
                    'published' => true,
                    'baseDomain' => '*.sulu.io/*',
                    'domainParts' => ['test-1', 'test-2'],
                    'targetDocument' => true,
                    'targetLocale' => 'en',
                    'canonical' => true,
                    'redirect' => true,
                    'noFollow' => true,
                    'noIndex' => true,
                ],
                'test-1.sulu.io/test-2',
            ],
        ];
    }

    /**
     * @dataProvider getProvider
     */
    public function testGet($data, $url): void
    {
        // content document is not there in provider
        if (\array_key_exists('targetDocument', $data)) {
            $data['targetDocument'] = $this->contentDocument->getUuid();
        }

        $uuid = $this->testPost($data, $url);

        $dateTime = new \DateTime();

        $this->client->jsonRequest('GET', '/api/webspaces/sulu_io/custom-urls/' . $uuid);

        $response = $this->client->getResponse();
        $responseData = \json_decode($response->getContent(), true);

        $this->assertHttpStatusCode(200, $response);

        foreach ($data as $key => $value) {
            $this->assertEquals($value, $responseData[$key], $key);
        }

        $this->assertEquals($uuid, $responseData['id']);
        $this->assertArrayHasKey('creator', $responseData);
        $this->assertArrayHasKey('changer', $responseData);

        $this->assertGreaterThanOrEqual(new \DateTime($responseData['created']), $dateTime);
        $this->assertGreaterThanOrEqual(new \DateTime($responseData['changed']), $dateTime);
        $this->assertEquals(Urlizer::urlize($data['title']), $responseData['nodeName']);
        if (\array_key_exists('targetDocument', $data)) {
            $this->assertEquals('Homepage', $responseData['targetTitle']);
        } else {
            $this->assertArrayNotHasKey('targetTitle', $responseData);
        }
        $this->assertEquals($url, $responseData['customUrl']);
        $this->assertEquals('sulu_io', $responseData['webspace']);
    }

    public static function cgetProvider()
    {
        return [
            [
                [
                    'test-1.sulu.io/test-1' => [
                        'title' => 'Test-1',
                        'published' => true,
                        'baseDomain' => '*.sulu.io/*',
                        'domainParts' => ['test-1', 'test-1'],
                        'targetDocument' => true,
                        'targetLocale' => 'en',
                        'canonical' => true,
                        'redirect' => true,
                        'noFollow' => true,
                        'noIndex' => true,
                    ],
                    'test-2.sulu.io/test-2' => [
                        'title' => 'Test-2',
                        'published' => true,
                        'baseDomain' => '*.sulu.io/*',
                        'domainParts' => ['test-2', 'test-2'],
                        'targetDocument' => true,
                        'targetLocale' => 'en',
                        'canonical' => true,
                        'redirect' => true,
                        'noFollow' => true,
                        'noIndex' => true,
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider cgetProvider
     */
    public function testCGet($items): void
    {
        foreach ($items as $url => $data) {
            $items[$url]['id'] = $this->testPost($data, $url);
        }

        $this->client->jsonRequest('GET', '/api/webspaces/sulu_io/custom-urls');
        $requestTime = new \DateTime();

        $response = $this->client->getResponse();
        $responseDataComplete = \json_decode($response->getContent(), true);

        $this->assertHttpStatusCode(200, $response);

        foreach ($responseDataComplete['_embedded']['custom_urls'] as $responseData) {
            $data = $items[$responseData['customUrl']];

            foreach (['id', 'title', 'published', 'baseDomain'] as $key) {
                $this->assertEquals($data[$key], $responseData[$key]);
            }

            $this->assertEquals($this->contentDocument->getUuid(), $responseData['targetDocument']);

            $this->assertArrayHasKey('creator', $responseData);
            $this->assertArrayHasKey('changer', $responseData);

            $this->assertLessThanOrEqual($requestTime, new \DateTime($responseData['created']));
            $this->assertLessThanOrEqual($requestTime, new \DateTime($responseData['changed']));
            if (\array_key_exists('targetDocument', $data)) {
                $this->assertEquals('Homepage', $responseData['targetTitle']);
            } else {
                $this->assertArrayNotHasKey('targetTitle', $responseData);
            }
        }
    }

    /**
     * @dataProvider getProvider
     */
    public function testDelete($data, $url): void
    {
        $uuid = $this->testPost($data, $url);

        static::assertNull($this->activityRepository->findOneBy(['resourceId' => $uuid, 'type' => 'removed']));
        static::assertNull($this->trashItemRepository->findOneBy(['resourceId' => $uuid]));

        $this->client->jsonRequest('DELETE', '/api/webspaces/sulu_io/custom-urls/' . $uuid);
        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(204, $response);

        static::assertNotNull($this->activityRepository->findOneBy(['resourceId' => $uuid, 'type' => 'removed']));
        static::assertNotNull($this->trashItemRepository->findOneBy(['resourceId' => $uuid]));

        $this->client->jsonRequest('GET', '/api/webspaces/sulu_io/custom-urls/' . $uuid);
        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(404, $response);
    }

    /**
     * @dataProvider cgetProvider
     */
    public function testCDelete($items): void
    {
        $uuid = $this->testPost(
            [
                'title' => 'Test',
                'published' => true,
                'baseDomain' => '*.sulu.io/*',
                'domainParts' => ['test', 'test'],
                'targetDocument' => true,
                'targetLocale' => 'en',
                'canonical' => true,
                'redirect' => true,
                'noFollow' => true,
                'noIndex' => true,
            ],
            'test.sulu.io/test'
        );

        $uuids = [];
        foreach ($items as $url => $data) {
            $uuids[] = $this->testPost($data, $url);
        }

        $this->client->jsonRequest('DELETE', '/api/webspaces/sulu_io/custom-urls?ids=' . \implode(',', $uuids));

        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(204, $response);

        $this->client->jsonRequest('GET', '/api/webspaces/sulu_io/custom-urls');

        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(200, $response);

        $responseData = \json_decode($response->getContent(), true);
        $this->assertCount(1, $responseData['_embedded']['custom_urls']);
        $this->assertEquals($uuid, $responseData['_embedded']['custom_urls'][0]['id']);
    }
}
