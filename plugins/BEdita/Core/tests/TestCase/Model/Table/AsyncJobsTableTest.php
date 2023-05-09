<?php
namespace BEdita\Core\Test\TestCase\Model\Table;

use BEdita\Core\Model\Entity\AsyncJob;
use BEdita\Core\Model\Table\AsyncJobsTable;
use Cake\Datasource\ConnectionManager;
use Cake\ORM\TableRegistry;
use Cake\Queue\QueueManager;
use Cake\TestSuite\TestCase;

/**
 * @coversDefaultClass \BEdita\Core\Model\Table\AsyncJobsTable
 */
class AsyncJobsTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \BEdita\Core\Model\Table\AsyncJobsTable
     */
    public $AsyncJobs;

    /**
     * Fixtures
     *
     * @var array
     */
    protected $fixtures = [
        'plugin.BEdita/Core.AsyncJobs',
    ];

    /**
     * Async job connection config.
     *
     * @var array
     */
    protected $connection;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->AsyncJobs = TableRegistry::getTableLocator()->get('AsyncJobs');

        if (in_array('async_jobs', ConnectionManager::configured())) {
            $this->connection = ConnectionManager::getConfig('async_jobs');
            ConnectionManager::drop('async_jobs');
        }
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->AsyncJobs);

        if (in_array('async_jobs', ConnectionManager::configured())) {
            ConnectionManager::drop('async_jobs');
        }
        if (!empty($this->connection)) {
            ConnectionManager::setConfig('async_jobs', $this->connection);
        }

        parent::tearDown();
    }

    /**
     * Test default connection name.
     *
     * @return void
     * @covers ::defaultConnectionName()
     */
    public function testDefaultConnectionName()
    {
        $connectionName = AsyncJobsTable::defaultConnectionName();
        static::assertEquals('default', $connectionName);

        ConnectionManager::setConfig('async_jobs', ConnectionManager::getConfig('default'));

        $connectionName = AsyncJobsTable::defaultConnectionName();
        static::assertEquals('async_jobs', $connectionName);
    }

    /**
     * Test locking.
     *
     * @return void
     * @covers ::lock()
     */
    public function testLock()
    {
        $uuid = 'd6bb8c84-6b29-432e-bb84-c3c4b2c1b99c';

        $eventDispatched = 0;
        $this->AsyncJobs->getEventManager()->on('AsyncJob.lock', function () use (&$eventDispatched) {
            $eventDispatched++;

            /** @var \Cake\Database\Connection $connection */
            $connection = ConnectionManager::get('default');

            static::assertInstanceOf(AsyncJob::class, func_get_arg(1));
            static::assertIsInt(func_get_arg(2));
            static::assertTrue($connection->inTransaction());
        });

        $entity = $this->AsyncJobs->lock($uuid);

        static::assertSame(1, $eventDispatched);
        static::assertInstanceOf(AsyncJob::class, $entity);

        $entity = $this->AsyncJobs->get($uuid);
        static::assertNotNull($entity->locked_until);
        static::assertSame(0, $entity->max_attempts);
    }

    /**
     * Test locking a job that is not pending.
     *
     * @return void
     * @covers ::lock()
     */
    public function testLockNotPending()
    {
        $this->expectException(\Cake\Datasource\Exception\RecordNotFoundException::class);
        $this->AsyncJobs->lock('6407afa6-96a3-4aeb-90c1-1541756efdef');
    }

    /**
     * Test unlocking a job after successful execution.
     *
     * @return void
     * @covers ::unlock()
     */
    public function testUnlockSuccess()
    {
        $uuid = 'd6bb8c84-6b29-432e-bb84-c3c4b2c1b99c';
        $success = 'Job completed successfully thanks to Gustavo Supporto!';

        $eventDispatched = 0;
        $this->AsyncJobs->getEventManager()->on('AsyncJob.complete', function () use (&$eventDispatched, $success) {
            $eventDispatched++;

            /** @var \Cake\Database\Connection $connection */
            $connection = ConnectionManager::get('default');

            static::assertInstanceOf(AsyncJob::class, func_get_arg(1));
            static::assertSame($success, func_get_arg(2));
            static::assertTrue($connection->inTransaction());
        });
        $this->AsyncJobs->getEventManager()->on('AsyncJob.fail', function () {
            static::fail('Wrong event dispatched');
        });

        $this->AsyncJobs->unlock($uuid, $success);

        static::assertSame(1, $eventDispatched);

        $entity = $this->AsyncJobs->get($uuid);
        static::assertNull($entity->locked_until);
        static::assertNotNull($entity->completed);
    }

    /**
     * Test unlocking a job after failed execution.
     *
     * @return void
     * @covers ::unlock()
     */
    public function testUnlockFail()
    {
        $uuid = 'd6bb8c84-6b29-432e-bb84-c3c4b2c1b99c';
        $success = false;

        $eventDispatched = 0;
        $this->AsyncJobs->getEventManager()->on('AsyncJob.fail', function () use (&$eventDispatched, $success) {
            $eventDispatched++;

            /** @var \Cake\Database\Connection $connection */
            $connection = ConnectionManager::get('default');

            static::assertInstanceOf(AsyncJob::class, func_get_arg(1));
            static::assertSame($success, func_get_arg(2));
            static::assertTrue($connection->inTransaction());
        });
        $this->AsyncJobs->getEventManager()->on('AsyncJob.complete', function () {
            static::fail('Wrong event dispatched');
        });

        $this->AsyncJobs->unlock($uuid, $success);

        static::assertSame(1, $eventDispatched);

        $entity = $this->AsyncJobs->get($uuid);
        static::assertNull($entity->locked_until);
        static::assertNull($entity->completed);
    }

    /**
     * Test finder for pending jobs.
     *
     * @return void
     * @covers ::findPending()
     */
    public function testFindPending()
    {
        $expected = [
            '427ece75-71fb-4aca-bfab-1214cd98495a' => [
                'user_id' => '99999',
            ],
            'd6bb8c84-6b29-432e-bb84-c3c4b2c1b99c' => [
                'key' => 'value',
            ],
            'e533e1cf-b12c-4dbe-8fb7-b25fafbd2f76' => [
                'key' => 'value',
            ],
        ];
        ksort($expected);

        $actual = $this->AsyncJobs->find('pending')->find('list')->toArray();
        ksort($actual);

        static::assertSame($expected, $actual);
    }

    /**
     * Test finder for failed jobs.
     *
     * @return void
     * @covers ::findFailed()
     */
    public function testFindFailed()
    {
        $expected = [
            '40e22034-213f-4028-9930-81c0ed79c5a6' => [
                'key' => 'value',
            ],
            '0c833458-dff1-4fbb-bbf6-a30818b60616' => [
                'key' => 'value',
            ],
        ];
        ksort($expected);

        $actual = $this->AsyncJobs->find('failed')->find('list')->toArray();
        ksort($actual);

        static::assertSame($expected, $actual);
    }

    /**
     * Test finder for completed jobs.
     *
     * @return void
     * @covers ::findCompleted()
     */
    public function testFindCompleted()
    {
        $expected = [
            '1e2d1c66-c0bb-47d7-be5a-5bc92202333e' => [
                'key' => 'value',
            ],
        ];

        $actual = $this->AsyncJobs->find('completed')->find('list')->toArray();

        static::assertSame($expected, $actual);
    }

    /**
     * Test finder for incomplete jobs.
     *
     * @return void
     * @covers ::findIncomplete()
     */
    public function testFindIncomplete()
    {
        $expected = [
            'd6bb8c84-6b29-432e-bb84-c3c4b2c1b99c' => [
                'key' => 'value',
            ],
            'e533e1cf-b12c-4dbe-8fb7-b25fafbd2f76' => [
                'key' => 'value',
            ],
            '66594f3c-995f-49d2-9192-382baf1a12b3' => [
                'key' => 'value',
            ],
            '6407afa6-96a3-4aeb-90c1-1541756efdef' => [
                'key' => 'value',
            ],
            '40e22034-213f-4028-9930-81c0ed79c5a6' => [
                'key' => 'value',
            ],
            '0c833458-dff1-4fbb-bbf6-a30818b60616' => [
                'key' => 'value',
            ],
            '427ece75-71fb-4aca-bfab-1214cd98495a' => [
                'user_id' => '99999',
            ],
        ];

        ksort($expected);

        $actual = $this->AsyncJobs->find('incomplete')->find('list')->toArray();
        ksort($actual);

        static::assertSame($expected, $actual);
    }

    /**
     * Test finder for pending jobs sorted by priority.
     *
     * @return void
     * @covers ::findPriority()
     */
    public function testFindPriority()
    {
        $expected = [
            '427ece75-71fb-4aca-bfab-1214cd98495a' => [
                'user_id' => '99999',
            ],
            'e533e1cf-b12c-4dbe-8fb7-b25fafbd2f76' => [
                'key' => 'value',
            ],
            'd6bb8c84-6b29-432e-bb84-c3c4b2c1b99c' => [
                'key' => 'value',
            ],
        ];

        $actual = $this->AsyncJobs->find('priority')->find('list')->toArray();

        static::assertSame($expected, $actual);
    }

    /**
     * Test finder for pending jobs sorted by priority and filtering by service type.
     *
     * @return void
     * @covers ::findPriority()
     */
    public function testFindPriorityFilterService()
    {
        $expected = [
            'e533e1cf-b12c-4dbe-8fb7-b25fafbd2f76' => [
                'key' => 'value',
            ],
        ];

        $actual = $this->AsyncJobs->find('priority', ['service' => 'example2'])->find('list')->toArray();

        static::assertSame($expected, $actual);
    }

    /**
     * Test finder for pending jobs sorted by priority and filtering by priority.
     *
     * @return void
     * @covers ::findPriority()
     */
    public function testFindPriorityFilterPriority()
    {
        $expected = [
            '427ece75-71fb-4aca-bfab-1214cd98495a' => [
                'user_id' => '99999',
            ],
            'e533e1cf-b12c-4dbe-8fb7-b25fafbd2f76' => [
                'key' => 'value',
            ],
        ];

        $actual = $this->AsyncJobs->find('priority', ['priority' => 5])->find('list')->toArray();

        static::assertSame($expected, $actual);
    }

    /**
     * Test `afterSave` method.
     *
     * @return void
     * @covers ::afterSave()
     */
    public function testAfterSave(): void
    {
        $fsQueueFile = $this->getFsQueueUrl() . DS . 'enqueue.app.test';
        if (file_exists($fsQueueFile)) {
            unlink($fsQueueFile);
        }
        QueueManager::drop('default');
        $entity = $this->AsyncJobs->newEntity(['service' => 'example']);
        $entity = $this->AsyncJobs->saveOrFail($entity);
        static::assertFileDoesNotExist($fsQueueFile);

        QueueManager::setConfig('default', [
            'url' => $this->getFsQueueUrl(),
            'queue' => 'test',
        ]);

        $entity = $this->AsyncJobs->newEntity(['service' => 'example']);
        $entity = $this->AsyncJobs->saveOrFail($entity);
        $this->assertFileExists($fsQueueFile);
        $this->assertStringContainsString($entity->get('uuid'), file_get_contents($fsQueueFile));
        QueueManager::drop('default');
    }

    /**
     * Test Filesystem queue URL
     *
     * @return string
     */
    private function getFsQueueUrl(): string
    {
        return 'file:///' . TMP . DS . 'queue';
    }

    /**
     * Data provider for testUpdateResults.
     *
     * @return array
     */
    public function updateResultsProvider(): array
    {
        return [
            'success false, some message, no attempt' => [
                false,
                'some dummy message 1',
                -1,
                [
                    [
                        'data' => [
                            'message' => 'some dummy message 1',
                        ],
                        'success' => false,
                    ],
                ],
            ],
            'success true, some message, no attempt' => [
                true,
                'some dummy message 2',
                -1,
                [
                    [
                        'data' => [
                            'message' => 'some dummy message 2',
                        ],
                        'success' => true,
                    ],
                ],
            ],
            'success true, some message, and attempt' => [
                true,
                'some dummy message 3',
                2,
                [
                    [
                        'data' => [
                            'message' => 'some dummy message 3',
                        ],
                        'success' => true,
                        'attempt_number' => 2,
                    ],
                ],
            ],
        ];
    }

    /**
     * Test `updateResults`.
     *
     * @return void
     * @covers ::updateResults()
     * @dataProvider updateResultsProvider()
     */
    public function testUpdateResults(bool $success, string $message, int $attempt, array $expected): void
    {
        $entity = $this->AsyncJobs->newEntity(['service' => 'example2']);
        $entity = $this->AsyncJobs->saveOrFail($entity);
        if ($attempt > 0) {
            $this->AsyncJobs->updateResults($entity, $success, $message, $attempt);
        } else {
            $this->AsyncJobs->updateResults($entity, $success, $message);
        }
        $actual = $entity->get('results');
        static::assertSame($expected, $actual);
    }
}
